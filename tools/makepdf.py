#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
MakerPDF — générateur PDF autonome (bibliothèque standard uniquement).

Produit le dossier de livraison « Touche pas au klaxon » sans Composer et sans
module Python tiers (reportlab n'est pas requis).

Fonctionnalités :
  - pages A4, pagination automatique avec pied de page numéroté ;
  - polices base 14 (Helvetica / Helvetica-Bold), encodage WinAnsi (accents) ;
  - titres, paragraphes justifiés à gauche, listes à puces, blocs de code,
    tableaux avec en-tête coloré ;
  - insertion d'images JPEG (MCD) redimensionnées dans la largeur utile.

Usage :
    python tools/makepdf.py tools/content.json Nom_Prenom_Titre_devoir.pdf
"""

import json
import os
import struct
import sys
import zlib
from datetime import datetime

# ---------------------------------------------------------------------------
# Encodage du texte : Unicode -> WinAnsi (cp1252), comme les polices base 14
# ---------------------------------------------------------------------------

WINANSI_SPECIAL = {
    0x20AC: 0x80,  # €
    0x201A: 0x82, 0x0192: 0x83, 0x201E: 0x84, 0x2026: 0x85,
    0x2020: 0x86, 0x2021: 0x87, 0x02C6: 0x88, 0x2030: 0x89,
    0x0160: 0x8A, 0x2039: 0x8B, 0x0152: 0x8C, 0x017D: 0x8E,
    0x2018: 0x91, 0x2019: 0x92, 0x201C: 0x93, 0x201D: 0x94,
    0x2022: 0x95, 0x2013: 0x96, 0x2014: 0x97, 0x02DC: 0x98,
    0x2122: 0x99, 0x0161: 0x9A, 0x203A: 0x9B, 0x0153: 0x9C,
    0x017E: 0x9E, 0x0178: 0x9F,
}


def to_winansi(text):
    """Convertit une chaîne Unicode en octets WinAnsi (cp1252)."""
    out = bytearray()
    for ch in text:
        o = ord(ch)
        if o < 0x80:
            out.append(o)
        elif o in WINANSI_SPECIAL:
            out.append(WINANSI_SPECIAL[o])
        elif 0xA0 <= o <= 0xFF:
            out.append(o)
        else:
            out.append(0x3F)  # '?'
    return bytes(out)


def pdf_escape(raw):
    """Échappe une chaîne d'octets pour un opérateur Tj."""
    out = bytearray()
    for b in raw:
        if b in (0x28, 0x29, 0x5C):     # ( ) \
            out.append(0x5C)
            out.append(b)
        else:
            out.append(b)
    return bytes(out)


# ---------------------------------------------------------------------------
# Structure PDF 1.4
# ---------------------------------------------------------------------------

class PdfWriter(object):
    """Assemble un PDF 1.4 avec une numérotation d'objets fiable."""

    def __init__(self):
        self.objects = []          # index 0 -> objet numéro 1

    def add(self, payload):
        """Ajoute un objet brut (bytes, sans le « N 0 obj »). Renvoie son numéro."""
        self.objects.append(payload)
        return len(self.objects)

    def add_stream(self, raw, extra=""):
        """Ajoute un flux compressé (FlateDecode)."""
        comp = zlib.compress(raw)
        head = ("<< /Length %d /Filter /FlateDecode %s>>\nstream\n"
                % (len(comp), extra)).encode("latin-1")
        return self.add(head + comp + b"\nendstream")

    def write(self, path, catalog_num, pages_num):
        buf = bytearray(b"%PDF-1.4\n%\xe2\xe3\xcf\xd3\n")
        offsets = [0] * (len(self.objects) + 1)
        for i, payload in enumerate(self.objects, start=1):
            offsets[i] = len(buf)
            buf += ("%d 0 obj\n" % i).encode("latin-1")
            buf += payload
            buf += b"\nendobj\n"

        xref_pos = len(buf)
        n = len(self.objects) + 1
        buf += ("xref\n0 %d\n" % n).encode("latin-1")
        buf += b"0000000000 65535 f \n"
        for i in range(1, n):
            buf += ("%010d 00000 n \n" % offsets[i]).encode("latin-1")
        buf += ("trailer\n<< /Size %d /Root %d 0 R >>\nstartxref\n%d\n%%%%EOF\n"
                % (n, catalog_num, xref_pos)).encode("latin-1")

        with open(path, "wb") as fh:
            fh.write(buf)
        return len(buf)


def jpeg_size(raw):
    """Lit les dimensions d'un flux JPEG."""
    i = 2
    while i < len(raw) - 9:
        if raw[i] != 0xFF:
            i += 1
            continue
        marker = raw[i + 1]
        if marker in (0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7,
                      0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF):
            h, w = struct.unpack(">HH", raw[i + 5:i + 9])
            comps = raw[i + 9]
            return w, h, comps
        seg = struct.unpack(">H", raw[i + 2:i + 4])[0]
        i += 2 + seg
    raise ValueError("dimensions JPEG introuvables")


# ---------------------------------------------------------------------------
# Mise en page
# ---------------------------------------------------------------------------

class PDF(object):
    """Couche de dessin haut niveau (A4 portrait, marges de 54 pt)."""

    W = 595.28
    H = 841.89
    MARGIN = 54.0

    # couleurs de la charte imposée
    PRIMARY = (0.0, 0.454, 0.780)       # #0074C7
    PRIMARY_DARK = (0.004, 0.282, 0.486)  # #01487C
    DARK = (0.224, 0.247, 0.310)        # #393F4F
    GREY = (0.494, 0.510, 0.553)        # #7E828D
    LIGHT = (0.941, 0.973, 0.984)       # #F0F8FB
    BORDER = (0.855, 0.886, 0.902)      # #DAE2E6
    DANGER = (0.800, 0.173, 0.180)      # #CC2C2E

    def __init__(self, title="", author="Touche pas au klaxon"):
        self.title = title
        self.author = author
        self.pages = []          # list[list[bytes commands]]
        self.cur = []            # commandes de la page courante
        self.y = 0.0             # distance depuis le haut de la zone utile
        self.images = {}         # nom -> (num, w, h)
        self._w = PdfWriter()
        self._font_regular = self._w.add(
            b"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica "
            b"/Encoding /WinAnsiEncoding >>")
        self._font_bold = self._w.add(
            b"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold "
            b"/Encoding /WinAnsiEncoding >>")

    # -- bas niveau -------------------------------------------------------
    def _emit(self, s):
        self.cur.append(s.encode("latin-1", "replace"))

    def _rgb(self, color, stroke=False):
        return "%.3f %.3f %.3f %s" % (color[0], color[1], color[2], "RG" if stroke else "rg")

    def text(self, txt, x, y_top, size=11, bold=False, color=(0, 0, 0)):
        font = "/F2" if bold else "/F1"
        self._emit("BT")
        self._emit(self._rgb(color))
        self._emit("%s %.2f Tf" % (font, size))
        self._emit("1 0 0 1 %.2f %.2f Tm" % (x, self.H - y_top - size * 0.8))
        self._emit("(%s) Tj" % pdf_escape(to_winansi(txt)).decode("latin-1"))
        self._emit("ET")

    def line(self, x1, y1, x2, y2, color=None, width=0.5):
        self._emit("%.2f w" % width)
        self._emit(self._rgb(color or self.BORDER, stroke=True))
        self._emit("%.2f %.2f m %.2f %.2f l S" % (x1, self.H - y1, x2, self.H - y2))

    def rect(self, x, y_top, w, h, color, fill=True):
        if fill:
            self._emit(self._rgb(color))
            self._emit("%.2f %.2f %.2f %.2f re f" % (x, self.H - y_top - h, w, h))
        else:
            self._emit(self._rgb(color, stroke=True))
            self._emit("%.2f %.2f %.2f %.2f re S" % (x, self.H - y_top - h, w, h))

    def image(self, name, x, y_top, w, h):
        num = self.images[name][0]
        self._emit("q")
        self._emit("%.2f 0 0 %.2f %.2f %.2f cm" % (w, h, x, self.H - y_top - h))
        self._emit("/Im%d Do" % num)
        self._emit("Q")

    # -- pagination -------------------------------------------------------
    def page_break(self):
        self.pages.append(self.cur)
        self.cur = []
        self.y = self.MARGIN

    def _ensure(self, needed):
        if self.y + needed > self.H - self.MARGIN - 24:
            self.page_break()

    def spacer(self, n=8):
        self.y += n

    # -- contenu ----------------------------------------------------------
    def cover(self, meta):
        self.y = 0.0
        self.rect(0, 0, self.W, 150, self.PRIMARY)
        self.text("TOUCHE PAS AU KLAXON", self.MARGIN, 52, size=27, bold=True,
                  color=(1, 1, 1))
        self.text("Application MVC de covoiturage entre agences",
                  self.MARGIN, 92, size=13, color=(1, 1, 1))

        self.y = 200
        self.text("DOSSIER DE LIVRAISON", self.MARGIN, self.y, size=12, bold=True,
                  color=self.PRIMARY_DARK)
        self.y += 26
        for label, value in meta:
            self.text(label, self.MARGIN, self.y, size=10, bold=True, color=self.GREY)
            self.text(value, self.MARGIN + 130, self.y, size=10, color=self.DARK)
            self.line(self.MARGIN, self.y + 15, self.W - self.MARGIN, self.y + 15)
            self.y += 22

        self.y += 14
        self.text("Sommaire", self.MARGIN, self.y, size=12, bold=True,
                  color=self.PRIMARY_DARK)
        self.y += 20
        for i, (title, _) in enumerate(self.sections(), start=1):
            self.text("%d.  %s" % (i, title), self.MARGIN + 8, self.y, size=10,
                      color=self.DARK)
            self.y += 16
        self.page_break()

    def heading(self, txt):
        self._ensure(46)
        self.y += 8
        self.text(txt, self.MARGIN, self.y, size=14, bold=True, color=self.PRIMARY_DARK)
        self.y += 21
        self.line(self.MARGIN, self.y, self.W - self.MARGIN, self.y,
                  color=self.PRIMARY, width=1.1)
        self.y += 10

    def sub(self, txt):
        self._ensure(26)
        self.text(txt, self.MARGIN, self.y, size=10.5, bold=True, color=self.DARK)
        self.y += 16

    def wrap(self, txt, size, width):
        cpl = max(10, int(width / (size * 0.50)))
        lines, cur = [], ""
        for word in txt.split(" "):
            if cur and len(cur) + 1 + len(word) > cpl:
                lines.append(cur)
                cur = word
            else:
                cur = (cur + " " + word).strip()
        if cur:
            lines.append(cur)
        return lines or [""]

    def para(self, txt, size=10.5, indent=0, color=None):
        width = self.W - 2 * self.MARGIN - indent
        for ln in self.wrap(txt, size, width):
            self._ensure(size * 1.5)
            self.text(ln, self.MARGIN + indent, self.y, size=size, color=color or self.DARK)
            self.y += size * 1.45
        self.y += 3

    def bullet(self, txt, size=10.5):
        width = self.W - 2 * self.MARGIN - 16
        for i, ln in enumerate(self.wrap(txt, size, width)):
            self._ensure(size * 1.5)
            if i == 0:
                self.text("-  " + ln, self.MARGIN + 4, self.y, size=size, color=self.DARK)
            else:
                self.text(ln, self.MARGIN + 16, self.y, size=size, color=self.DARK)
            self.y += size * 1.45
        self.y += 2

    def code(self, block, size=8.6):
        for ln in block.split("\n"):
            self._ensure(size + 6)
            self.rect(self.MARGIN, self.y - 1, self.W - 2 * self.MARGIN, size + 5,
                      self.LIGHT)
            self.text(ln if ln else " ", self.MARGIN + 6, self.y + 1, size=size,
                      color=self.DARK)
            self.y += size + 4
        self.y += 6

    def table(self, headers, rows, widths, size=9.2):
        total = sum(widths)
        avail = self.W - 2 * self.MARGIN
        if abs(total - avail) > 0.5:
            widths = [w * avail / total for w in widths]
        row_h = size + 7

        def header():
            self._ensure(row_h)
            self.rect(self.MARGIN, self.y, avail, row_h, self.PRIMARY)
            cx = self.MARGIN
            for i, h in enumerate(headers):
                self.text(h, cx + 4, self.y + 4, size=size, bold=True, color=(1, 1, 1))
                cx += widths[i]
            self.y += row_h

        header()
        for row in rows:
            self._ensure(row_h)
            cx = self.MARGIN
            for i, cell in enumerate(row):
                self.text(str(cell), cx + 4, self.y + 4, size=size, color=self.DARK)
                cx += widths[i]
            self.y += row_h
            self.line(self.MARGIN, self.y, self.W - self.MARGIN, self.y)
        self.y += 8

    def mcd_image(self, cfg):
        """Insère l'image du MCD si elle est fournie et compilable en JPEG."""
        path = cfg.get("mcd_jpeg")
        if not path or not os.path.exists(path):
            return False
        raw = open(path, "rb").read()
        w, h, comps = jpeg_size(raw)
        cs = "/DeviceGray" if comps == 1 else "/DeviceRGB"
        num = self._w.add(
            ("<< /Type /XObject /Subtype /Image /Width %d /Height %d "
             "/ColorSpace %s /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n"
             % (w, h, cs, len(raw))).encode("latin-1") + raw + b"\nendstream")
        self.images["mcd"] = (num, w, h)

        avail = self.W - 2 * self.MARGIN
        scale = min(avail / float(w), 1.0)
        dw, dh = w * scale, h * scale
        self._ensure(dh + 10)
        self.image("mcd", self.MARGIN, self.y, dw, dh)
        self.y += dh + 8
        return True

    # -- finalisation -----------------------------------------------------
    def save(self, path):
        # clôturer la page en cours
        if self.cur:
            self.pages.append(self.cur)
            self.cur = []

        # pied de page sur chaque page (une seule fois par page)
        total = len(self.pages)
        for idx, cmds in enumerate(self.pages, start=1):
            self.cur = cmds
            self._emit("%.2f w 0.855 0.886 0.902 RG" % 0.5)
            self._emit("54 %.2f m 541 %.2f l S" % (self.H - 812, self.H - 812))
            self.text("Touche pas au klaxon - dossier de livraison", self.MARGIN,
                      815, size=8, color=self.GREY)
            self.text("page %d / %d" % (idx, total), self.W - self.MARGIN - 52, 815,
                      size=8, color=self.GREY)
        self.cur = []

        pages = self.pages

        # objets pages + contenus
        pages_obj_num = len(self._w.objects) + 1 + len(pages) * 2
        page_nums = []
        content_nums = []
        for cmds in pages:
            page_nums.append(len(self._w.objects) + 1)
            self._w.add(b"PLACEHOLDER")          # réservé pour l'objet /Page
            content_nums.append(self._w.add_stream(b"\n".join(cmds)))

        # ressources (polices + images)
        xobj = " ".join("/Im%d %d 0 R" % (num, num) for num, _, _ in self.images.values())
        res = ("/Font << /F1 %d 0 R /F2 %d 0 R >>" %
               (self._font_regular, self._font_bold))
        if xobj:
            res += " /XObject << %s >>" % xobj
        resources = "<< %s >>" % res

        for i, page_num in enumerate(page_nums):
            self._w.objects[page_num - 1] = (
                "<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2f %.2f] "
                "/Resources %s /Contents %d 0 R >>"
                % (pages_obj_num, self.W, self.H, resources, content_nums[i])
            ).encode("latin-1")

        pages_obj = self._w.add(
            ("<< /Type /Pages /Kids [%s] /Count %d >>"
             % (" ".join("%d 0 R" % n for n in page_nums), len(page_nums))).encode("latin-1"))
        catalog_num = self._w.add(
            ("<< /Type /Catalog /Pages %d 0 R /Title (%s) /Author (%s) "
             "/Producer (MakerPDF) >>"
             % (pages_obj,
                pdf_escape(to_winansi(self.title)).decode("latin-1"),
                pdf_escape(to_winansi(self.author)).decode("latin-1"))).encode("latin-1"))

        return self._w.write(path, catalog_num, pages_obj)

    # -- plan du rapport --------------------------------------------------
    def sections(self):
        return [
            ("Présentation du projet", self.s_presentation),
            ("Fonctionnalités", self.s_features),
            ("Architecture et organisation du code", self.s_architecture),
            ("Modèle de données", self.s_data),
            ("MCD (modèle conceptuel de données)", self.s_mcd),
            ("MLD (modèle logique de données)", self.s_mld),
            ("Charte graphique", self.s_theme),
            ("Installation et lancement", self.s_install),
            ("Comptes de démonstration", self.s_accounts),
            ("Tests et analyse statique", self.s_tests),
            ("Limites connues de cette version", self.s_limits),
        ]

    # -- sections ---------------------------------------------------------
    def s_presentation(self, cfg):
        self.para(cfg["presentation"])

    def s_features(self, cfg):
        for f in cfg["fonctionnalites"]:
            self.bullet(f)

    def s_architecture(self, cfg):
        self.para("Le projet suit une architecture MVC (modèle-vue-contrôleur) "
                  "sans framework : un contrôleur frontal reçoit toutes les requêtes, "
                  "un routeur les dirige vers un contrôleur, lequel interroge les "
                  "modèles (PDO) et rend une vue.")
        self.table(["Couche", "Emplacement", "Rôle"],
                   cfg["architecture"], [90, 140, 130])

    def s_data(self, cfg):
        self.para("La base covoiturage comporte trois tables (moteur InnoDB, "
                  "jeu de caractères utf8mb4).")
        self.table(["Table", "Clé primaire", "Contenu"], cfg["tables"], [80, 90, 190])

    def s_mcd(self, cfg):
        self.para("Le MCD ci-dessous décrit les entités EMPLOYE, AGENCE et TRAJET "
                  "ainsi que leurs relations et cardinalités. Il est également livré "
                  "en image : docs/MCD.png.")
        self.mcd_image(cfg)

    def s_mld(self, cfg):
        self.para("Le MLD complet est également livré au format textuel : docs/MLD.txt.")
        self.code(cfg["mld"])

    def s_theme(self, cfg):
        self.para("La palette est imposée par le cahier des charges et centralisée "
                  "dans un seul fichier Sass : public/assets/sass/_variables.scss. "
                  "Aucune couleur n'est écrite directement dans les vues : les "
                  "composants consomment les variables --tpk-*.")
        self.table(["Rôle", "Variable Sass", "Valeur"], cfg["palette"], [110, 130, 120])
        self.para("La feuille compilée public/assets/css/app.css est produite à "
                  "partir de public/assets/sass/app.scss (commande : sass "
                  "public/assets/sass/app.scss public/assets/css/app.css).")

    def s_install(self, cfg):
        self.code(cfg["installation"])
        self.para("L'application fonctionne sans Composer : un autoload PSR-4 "
                  "maison (autoload.php) et des outils maison (bin/phpunit, "
                  "bin/phpstan-check) sont fournis.")

    def s_accounts(self, cfg):
        self.table(["Rôle", "Adresse email", "Mot de passe"], cfg["comptes"],
                   [80, 160, 120])

    def s_tests(self, cfg):
        self.table(["Outil", "Commande", "Résultat"], cfg["tests"], [55, 150, 155])

    def s_limits(self, cfg):
        for item in cfg["limites"]:
            self.bullet(item)


# ---------------------------------------------------------------------------
# Point d'entrée
# ---------------------------------------------------------------------------

def build_pdf(cfg, out_path):
    pdf = PDF(title="Touche pas au klaxon - dossier de livraison")
    pdf.cover(cfg["meta"])
    for title, fn in pdf.sections():
        pdf.heading(title)
        fn(cfg)
    pdf.save(out_path)
    return out_path


def main():
    if len(sys.argv) < 3:
        print("Usage: python tools/makepdf.py content.json sortie.pdf")
        sys.exit(2)
    cfg_path, out_path = sys.argv[1], sys.argv[2]
    with open(cfg_path, "r", encoding="utf-8") as fh:
        cfg = json.load(fh)
    build_pdf(cfg, out_path)
    print("PDF généré : %s (%d octets)" % (out_path, os.path.getsize(out_path)))


if __name__ == "__main__":
    main()
