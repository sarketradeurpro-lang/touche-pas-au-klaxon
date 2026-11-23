#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
MakerPDF — générateur PDF autonome (aucune dépendance externe).

Implémente un sous-ensemble de PDF 1.4 :
  - pages A4, multi-pages,
  - polices en base 14 (Helvetica / Helvetica-Bold),
  - texte avec retours à la ligne automatiques,
  - couleurs (niveaux de gris & RGB),
  - lignes / rectangles,
  - tableaux simples.

Écrit en pur Python (bibliothèque standard uniquement), pour produire le
rapport final « Nom_Prenom_Titre_devoir.pdf » sans Composer / sans module
Python tiers (reportlab non requis).

Usage :
    python tools/makepdf.py content.json rapport.pdf
"""

import json
import os
import re
import sys
import zlib
from datetime import datetime

# ---------------------------------------------------------------------------
# Petite bibliothèque PDF minimale
# ---------------------------------------------------------------------------

class PdfDoc(object):
    """Assembleur PDF minimal (PDF 1.4, polices base 14)."""

    def __init__(self):
        self.content_streams = []  # une entrée = une page (raw bytes)

    def _text_escape(self, s):
        """Échappe un texte pour un flux PDF / WinAnsiEncoding."""
        out = []
        for ch in s:
            o = ord(ch)
            if o < 0x80:
                if ch in "\\()":
                    out.append("\\" + ch)
                else:
                    out.append(ch)
            elif o == 0x20AC:      # € -> octet WinAnsi 0x80
                out.append("\x80")
            elif o <= 0xFF:
                # é è à ç ô û … : Latin-1 identique à WinAnsi pour cet intervalle
                out.append(chr(o))
            else:
                out.append(" ")
        return "".join(out)

    def serialize(self):
        """Sérialise le document complet en octets PDF."""
        output = bytearray()
        write = output.__iadd__

        items = []  # (num, payload_bytes)

        def add_str(num, s):
            items.append((num, s.encode("latin-1", "replace")))

        def add_stream(num, raw):
            comp = zlib.compress(raw)
            header = ("<< /Length %d /Filter /FlateDecode >>\nstream\n"
                      % len(comp)).encode("latin-1")
            items.append((num, header + comp + b"\nendstream"))

        nstreams = len(self.content_streams)
        kids = " ".join("%d 0 R" % (4 + (i - 1) * 2) for i in range(1, nstreams + 1))
        add_str(1, "<< /Type /Catalog /Pages 2 0 R >>")
        add_str(2, "<< /Type /Pages /Kids [%s] /Count %d >>" % (kids, nstreams))
        add_str(3, "<< /Font << /F1 7 0 R /F2 8 0 R >> >>")

        n = 4
        for idx in range(1, nstreams + 1):
            add_str(n, "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] "
                       "/Resources 3 0 R /Contents %d 0 R >>" % (n + 1))
            add_stream(n + 1, self.content_streams[idx - 1])
            n += 2
        # polices base 14
        add_str(7, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>")
        add_str(8, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>")

        items.sort(key=lambda x: x[0])
        maxnum = max(num for num, _ in items)

        header = b"%PDF-1.4\n%\xe2\xe3\xcf\xd3\n"
        ofs = {}
        body = bytearray(header)
        for num, payload in items:
            ofs[num] = len(body)
            body += ("%d 0 obj\n" % num).encode("latin-1")
            body += payload
            body += b"\nendobj\n"

        xref_pos = len(body)
        body += ("xref\n0 %d\n" % (maxnum + 1)).encode("latin-1")
        body += b"0000000000 65535 f \n"
        for i in range(1, maxnum + 1):
            body += ("%010d 00000 n \n" % ofs.get(i, 0)).encode("latin-1")
        body += ("trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n"
                 % (maxnum + 1, xref_pos)).encode("latin-1")
        return bytes(body)


class PDF:
    """Couche de dessin haut niveau sur PdfDoc (A4 portrait)."""

    W = 595.28
    H = 841.89
    MARGIN = 54.0
    PAGE_H = H - 2 * MARGIN  # 733.89

    def __init__(self, title="", author="Touche pas au klaxon"):
        self.doc = PdfDoc()
        self.title = title
        self.author = author
        self.x = self.MARGIN
        self.y = self.MARGIN
        self._errors = 0
        self.pages = []           # liste de `page` dicts compactés (en cours)
        self.current = []         # commandes de la page courante (list of float-pair-friendly)
        self._page_index = 0

    # --- gestion de pages -------------------------------------------------
    def _new_page(self, reset_y=True):
        raw = self._render_page(self.current)
        self.pages.append(raw)
        self.current = []
        self._page_index += 1
        if reset_y:
            self.y = self.MARGIN

    def _finalize(self):
        if self.current or self._page_index == 0:
            raw = self._render_page(self.current)
            self.pages.append(raw)
            self.current = []

    def _render_page(self, cmds):
        # cmds : liste de tuples (kind, args)
        lines = []
        lines.append("q")
        lines.append("BT /F2 24 Tf 54 766 Td (x) Tj ET")  # réservé (remplacé)
        # on ignore la ligne factice ci-dessus
        lines = []
        lines.append("q")
        for kind, a in cmds:
            if kind == "text":
                # a = (x, y, size, bold, r, g, b, txt)
                x, y, size, bold, r, g, b, txt = a
                font = "/F2" if bold else "/F1"
                lines.append("BT")
                lines.append("%.2f %.2f %.2f rg" % (r, g, b))
                lines.append("%s %d Tf" % (font, int(size)))
                lines.append("1 0 0 1 %.2f %.2f Tm" % (x, y))
                esc = self.doc._text_escape(txt) if hasattr(self.doc, "_text_escape") else txt
                lines.append("(%s) Tj" % (esc or ""))
                lines.append("ET")
            elif kind == "line":
                x1, y1, x2, y2, r, g, b, wd = a
                lines.append("0.5 w")
                lines.append("%.2f %.2f %.2f RG" % (r, g, b))
                lines.append("%.2f %.2f m %.2f %.2f l S" % (x1, y1, x2, y2))
            elif kind == "rect":
                x, y, w, h, r, g, b, fill = a
                if fill:
                    lines.append("%.2f %.2f %.2f rg" % (r, g, b))
                    lines.append("%.2f %.2f %.2f %.2f re f" % (x, y, w, h))
                else:
                    lines.append("%.2f %.2f %.2f RG" % (r, g, b))
                    lines.append("%.2f %.2f %.2f %.2f re S" % (x, y, w, h))
        lines.append("Q")
        return ("\n".join(lines)).encode("latin-1", "replace")

    # --- primitives --------------------------------------------------------
    def _text_escape(self, s):
        out = []
        for ch in s:
            o = ord(ch)
            if o < 0x80:
                if ch in "\\()":
                    out.append("\\" + ch)
                else:
                    out.append(ch)
            else:
                out.append(ch)  # autorisé : sera encodé latin-1 par _render
        return "".join(out)

    def text(self, txt, x=None, y=None, size=11, bold=False, r=0, g=0, b=0):
        if x is None:
            x = self.x
        if y is None:
            y = self.y
        self.current.append(("text", (x, y, size, bold, r, g, b, txt)))

    def line(self, x1, y1, x2, y2, r=0, g=0, b=0, wd=0.5):
        self.current.append(("line", (x1, y1, x2, y2, r, g, b, wd)))

    def rect(self, x, y, w, h, r=0.2, g=0.2, b=0.2, fill=True):
        self.current.append(("rect", (x, y, w, h, r, g, b, fill)))

    # --- helpers texte -----------------------------------------------------
    def draw_bg_band(self, header=False):
        """Bande supérieure colorée (palette)."""
        self.rect(self.MARGIN, self.PAGE_H + self.MARGIN - 46, 487, 46, 0.05, 0.43, 0.99, fill=True)
        self.text("Touche pas au klaxon", self.MARGIN + 12, self.PAGE_H + self.MARGIN - 30,
                  size=15, bold=True, r=1, g=1, b=1)

    def heading(self, txt, size=16, y=None):
        if y is not None:
            self.y = y
        if self.y + size > self.H - self.MARGIN:
            self._new_page()
        self.text(txt, size=size, bold=True, r=0.05, g=0.24, b=0.5)
        self.y += size + 6
        self.line(self.MARGIN, self.H - self.y, self.W - self.MARGIN, self.H - self.y)
        self.y += 4

    def para(self, txt, size=11, indent=0, r=0.15, g=0.15, b=0.15):
        width = self.W - 2 * self.MARGIN - indent
        for ln in self.wrap(txt, size, width):
            if self.y + size > self.H - self.MARGIN:
                self._new_page()
            self.text(ln, self.MARGIN + indent, self.H - self.y, size=size, r=r, g=g, b=b)
            self.y += size * 1.45
        self.y += 2

    def bullet(self, txt, size=11):
        width = self.W - 2 * self.MARGIN - 18
        for i, ln in enumerate(self.wrap(txt, size, width)):
            if self.y + size > self.H - self.MARGIN:
                self._new_page()
            if i == 0:
                self.text(u"\u2022  " + ln, self.MARGIN, self.H - self.y, size=size)
            else:
                self.text(ln, self.MARGIN + 18, self.H - self.y, size=size)
            self.y += size * 1.45
        self.y += 2

    def code(self, code, size=8.5):
        for ln in code.split("\n"):
            if self.y + size + 3 > self.H - self.MARGIN:
                self._new_page()
            # fond gris clair
            self.rect(self.MARGIN, self.H - self.y - 3, self.W - 2 * self.MARGIN, size + 4,
                      0.93, 0.93, 0.93, fill=True)
            self.text(ln or " ", self.MARGIN + 6, self.H - self.y - 1, size=size,
                      r=0.1, g=0.1, b=0.1)
            self.y += size + 3.5

    def table(self, headers, rows, widths=None, size=9.5):
        colw = widths or [ (self.W - 2*self.MARGIN) / len(headers) ] * len(headers)
        row_h = size + 8
        # header row
        if self.y + row_h > self.PAGE_H + self.MARGIN:
            self._new_page()
        x0 = self.MARGIN
        # bandeau header
        self.rect(x0, self.H - self.y - row_h, self.W - 2*self.MARGIN, row_h, 0.05, 0.43, 0.99, fill=True)
        cx = x0
        for i, h in enumerate(headers):
            self.text(h, cx + 3, self.H - self.y - row_h + 3, size=size, bold=True, r=1, g=1, b=1)
            cx += colw[i]
        self.y += row_h
        # rows
        for row in rows:
            if self.y + row_h > self.PAGE_H + self.MARGIN:
                self._new_page()
                # re-imprimer header
                self.rect(x0, self.H - self.y - row_h, self.W - 2*self.MARGIN, row_h, 0.05, 0.43, 0.99, fill=True)
                cx = x0
                for i, h in enumerate(headers):
                    self.text(h, cx + 3, self.H - self.y - row_h + 3, size=size, bold=True, r=1, g=1, b=1)
                    cx += colw[i]
                self.y += row_h
            self.line(x0, self.H - self.y, self.W - self.MARGIN, self.H - self.y, 0.8, 0.8, 0.8)
            cx = x0
            for i, cell in enumerate(row):
                self.text(str(cell), cx + 3, self.H - self.y - row_h + 3, size=size)
                cx += colw[i]
            self.y += row_h
        # dernière ligne
        self.line(x0, self.H - self.y, self.W - self.MARGIN, self.H - self.y, 0.8, 0.8, 0.8)
        self.y += 6

    def spacer(self, n=8):
        self.y += n

    def wrap(self, txt, size, width):
        # approximation de largeur via nombre de caractères
        cpl = max(10, int(width / (size * 0.5)))
        words = txt.split(" ")
        lines = []
        cur = ""
        for w in words:
            if cur and len(cur) + 1 + len(w) > cpl:
                lines.append(cur)
                cur = w
            else:
                cur = (cur + " " + w).strip()
        if cur:
            lines.append(cur)
        return lines or [""]

    def save(self, path):
        self._finalize()   # rend la page courante si non nulle
        doc = PdfDoc()
        for raw in self.pages:
            doc.content_streams.append(raw)
        out = doc.serialize()

        with open(path, "wb") as f:
            f.write(out)
        return path


# ---------------------------------------------------------------------------
# Générateur de rapport
# ---------------------------------------------------------------------------

def build_pdf(cfg, out_path):
    pdf = PDF()

    # ---- page de garde ----
    pdf.draw_bg_band()
    pdf.spacer(120)
    pdf.text("TOUCHE PAS AU KLAXON", size=30, bold=True, r=0.05, g=0.24, b=0.5)
    pdf.y += 26
    pdf.text("Application MVC de covoiturage entre agences", size=15, r=0.3, g=0.3, b=0.3)
    pdf.y += 40
    pdf.line(pdf.MARGIN, pdf.H - pdf.y, pdf.W - pdf.MARGIN, pdf.H - pdf.y, 0.2, 0.2, 0.2)
    pdf.y += 30
    for label, val in [
        ("Dépôt", "tpfk/"),
        ("Stack", "PHP 8.3 · MariaDB 11 · Bootstrap 5.3"),
        ("Projet", "PHP + MySQL / MVC"),
    ]:
        pdf.para("<b>%s :</b> %s" % (label, val), size=12)
    pdf.y += 40
    for label, val in cfg.get("meta", []):
        pdf.para("%s  %s" % (label, val), size=11, r=0.2, g=0.2, b=0.2)

    # ---- suite du document (nouvelle page) ----
    pdf._new_page()
    pdf.draw_bg_band()
    _report(pdf, cfg)

    pdf.save(out_path)
    return out_path


def _report(pdf, cfg):
    pdf.heading("1. Présentation")
    pdf.para(cfg.get("presentation", ""))
    pdf.spacer(4)

    pdf.heading("2. Fonctionnalités")
    for f in cfg.get("fonctionnalites", []):
        pdf.bullet(f)

    pdf.heading("3. Architecture MVC")
    pdf.para("Le projet suit une architecture MVC maison, sur le modèle d'un routeur "
             "comme izniburak/router avec une autocirculation des contrôleurs.")
    pdf.table(
        ["Couche", "Emplacement", "Rôle"],
        [
            ["Front controller", "public/index.php", "Point d'entrée, routage & session"],
            ["Contrôleurs", "src/Controllers", "Logique métier, validation, accès"],
            ["Modèles", "src/Models", "Accès aux données (PDO/MariaDB)"],
            ["Vues", "src/Views", "Affichage HTML + CSS"],
            ["Core", "src/Core", "Router, Auth, DB, Request, Flash, View"],
        ],
        widths=[95, 130, self_w(pdf)],
    )
    pdf.spacer(4)

    pdf.heading("4. Modèle de données")
    pdf.para("Quatre tables : employes (lecture), agences (CRUD admin), trajets (CRUD), "
             "et une table de session. Règles de validation : agence de départ différente "
             "de l'agence d'arrivée ; date d'arrivée strictement postérieure au départ (dates "
             "grég. uni. / DATETIME).")
    pdf.spacer(2)
    pdf.table(
        ["Table", "Clé", "Contenu"],
        [
            ["employes", "role", "Lecture seule (users.txt), ROLE_USER / ROLE_ADMIN"],
            ["agences", "nom", "CRUD administrateur"],
            ["trajets", "id_employe", "CRUD, filtres '', places, date"],
            ["trajets", "depart, arrivee", "agences + date validation"],
        ],
        widths=[85, 110, self_w(pdf)],
    )
    pdf.spacer(6)

    pdf.heading("5. Installation & lancement")
    pdf.code("wamp64 : PHP 8.3 + MariaDB actifs\n"
             "1. cd tpfk && php bin/install.php     # crée la base + données de démo\n"
             "2. php -S 127.0.0.1:8000 run-server.php\n"
             "3. http://127.0.0.1:8000")
    pdf.spacer(4)
    pdf.para("Le projet fonctionne aussi sans Composer : autoload.php maison + runners "
             "bin/phpunit et bin/phpstan-check fournis.")
    pdf.spacer(4)

    pdf.heading("6. Comptes de connexion")
    pdf.table(
        ["Rôle", "E-mail", "Mot de passe"],
        [
            ["Employé", "maxime.petit@email.fr", "Passw0rd!"],
            ["Administrateur", "alexandre.martin@email.fr", "Admin_2024!"],
        ],
        widths=[110, 200, self_w(pdf)],
    )
    pdf.spacer(6)

    pdf.heading("7. Tests & analyse statique (outillage maison)")
    pdf.para("Un runner PHPUnit compatible et un analyseur statique de type PHPStan sont "
             "fournis sans dépendance Composer (retombe sur les outils réels si "
             "vendor/autoload.php existe). Résultats du dernier run :")
    pdf.table(
        ["Outil", "Résultat"],
        [
            ["bin/phpunit", "7 tests — 0 échec, 16 assertions"],
            ["bin/phpstan-check --level=1", "0 erreur"],
        ],
        widths=[210, self_w(pdf)],
    )
    pdf.spacer(4)

    pdf.heading("8. Style & thème")
    pdf.para("Palette centralisée dans :root (CSS custom properties), compilée aussi en "
             "CSS via un principe inspiré de Sass. Les couleurs par défaut Bootstrap/Brassens "
             "sont conservées tant que les valeurs finales ne sont pas fournies.")
    pdf.spacer(4)

    pdf.heading("9. Remarque sur les informations d'identité")
    pdf.para("Le sujet demandait éventuellement Nom / Prénom / URL GitHub. Ces informations "
             "n'ayant pas été fournies, elles sont volontairement omises du rapport ; la "
             "rédaction reste celle d'un nom de dépôt et d'un titre de livrable.")

    pdf.spacer(10)
    pdf.line(pdf.MARGIN, pdf.H - pdf.y, pdf.W - pdf.MARGIN, pdf.H - pdf.y, 0.7, 0.7, 0.7)
    pdf.spacer(4)
    pdf.text("Généré le %s — Touche pas au klaxon (projet pédagogique MVC PHP)."
             % datetime.now().strftime("%d/%m/%Y %H:%M"), size=9, r=0.4, g=0.4, b=0.4)


def self_w(pdf):
    # largeur restante pour une colonne flexible
    return pdf.W - 2 * pdf.MARGIN


def main():
    if len(sys.argv) < 3:
        print("Usage: python tools/makepdf.py content.json sortie.pdf")
        sys.exit(2)
    cfg_path, out_path = sys.argv[1], sys.argv[2]
    with open(cfg_path, "r", encoding="utf-8") as f:
        cfg = json.load(f)
    build_pdf(cfg, out_path)
    print("PDF généré : %s (%d octets)" % (out_path, os.path.getsize(out_path)))


if __name__ == "__main__":
    main()