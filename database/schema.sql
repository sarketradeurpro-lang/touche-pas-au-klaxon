-- ============================================================================
-- "Touche pas au klaxon" — Schéma de la base de données (MySQL / MariaDB)
-- ----------------------------------------------------------------------------
-- Ce script crée la base de données ainsi que les trois tables du modèle
-- relationnel (MLD) :
--     EMPLOYE (0,n) -TRAJET- (1,1) AGENCE   (départ)
--     EMPLOYE (0,n) -TRAJET- (1,1) AGENCE   (arrivée)
--
-- Pré-requis  : un serveur MySQL / MariaDB est lancé.
-- Utilisation : mysql -h 127.0.0.1 -P <port> -u <utilisateur> -p < database/schema.sql
-- ============================================================================

-- Suppression propre d'une éventuelle base existante, puis création.
DROP DATABASE IF EXISTS covoiturage;
CREATE DATABASE covoiturage
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE covoiturage;

-- ----------------------------------------------------------------------------
-- Utilisateur applicatif dédié (bonnes pratiques de sécurité) :
-- l'application ne se connecte jamais avec le compte root.
-- ----------------------------------------------------------------------------
CREATE USER IF NOT EXISTS 'covoiturage'@'localhost' IDENTIFIED BY 'Covoiturage_2024!';
CREATE USER IF NOT EXISTS 'covoiturage'@'127.0.0.1' IDENTIFIED BY 'Covoiturage_2024!';
GRANT SELECT, INSERT, UPDATE, DELETE ON covoiturage.* TO 'covoiturage'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON covoiturage.* TO 'covoiturage'@'127.0.0.1';
FLUSH PRIVILEGES;

-- ----------------------------------------------------------------------------
-- Table AGENCE
-- ----------------------------------------------------------------------------
CREATE TABLE agences (
    id      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    ville   VARCHAR(80)      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_agence_ville (ville)
) ENGINE = InnoDB;

-- ----------------------------------------------------------------------------
-- Table EMPLOYE
-- ----------------------------------------------------------------------------
-- Les employés proviennent du système RH (fichier « users.txt »). Ils ne sont
-- pas modifiables depuis l'interface : aucun CRUD employé côté applicatif.
CREATE TABLE employes (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    nom             VARCHAR(80)      NOT NULL,
    prenom          VARCHAR(80)      NOT NULL,
    email           VARCHAR(160)     NOT NULL,
    telephone       VARCHAR(20)      NULL,
    role            VARCHAR(20)      NOT NULL DEFAULT 'ROLE_USER', -- ROLE_USER | ROLE_ADMIN
    mot_de_passe    VARCHAR(255)     NOT NULL,                     -- hash (password_hash)
    PRIMARY KEY (id),
    UNIQUE KEY uq_employe_email (email)
) ENGINE = InnoDB;

-- ----------------------------------------------------------------------------
-- Table TRAJET
-- ----------------------------------------------------------------------------
-- Un trajet relie une agence de départ à une agence d'arrivée et est proposé
-- par un employé (auteur / personne à contacter).
CREATE TABLE trajets (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    auteur_id               INT UNSIGNED NOT NULL,          -- employé proposeur
    agence_depart_id        INT UNSIGNED NOT NULL,
    agence_arrivee_id       INT UNSIGNED NOT NULL,
    date_depart             DATETIME     NOT NULL,          -- GDV départ (date + heure)
    date_arrivee            DATETIME     NOT NULL,          -- GDV arrivée (date + heure)
    nb_places_total         TINYINT UNSIGNED NOT NULL,
    nb_places_disponibles   TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_trajet_auteur   (auteur_id),
    KEY idx_trajet_depart   (agence_depart_id),
    KEY idx_trajet_arrivee  (agence_arrivee_id),
    CONSTRAINT fk_trajet_auteur
        FOREIGN KEY (auteur_id) REFERENCES employes (id),
    CONSTRAINT fk_trajet_depart
        FOREIGN KEY (agence_depart_id) REFERENCES agences (id),
    CONSTRAINT fk_trajet_arrivee
        FOREIGN KEY (agence_arrivee_id) REFERENCES agences (id),
    -- Cohérence métier : départ et arrivée distincts, arrivée après départ.
    CONSTRAINT ck_trajet_agences_distinctes
        CHECK (agence_depart_id <> agence_arrivee_id),
    CONSTRAINT ck_trajet_dates_coherentes
        CHECK (date_arrivee > date_depart),
    CONSTRAINT ck_trajet_places_total
        CHECK (nb_places_total > 0),
    CONSTRAINT ck_trajet_places_disponibles
        CHECK (nb_places_disponibles >= 0 AND nb_places_disponibles <= nb_places_total)
) ENGINE = InnoDB;