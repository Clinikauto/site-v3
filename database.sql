-- Base de données pour Clinik Auto
-- À exécuter dans phpMyAdmin de Webator

-- Table des rendez-vous
CREATE TABLE IF NOT EXISTS rendez_vous (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(30),
    address_line VARCHAR(255) NOT NULL DEFAULT '',
    postal_code VARCHAR(10) NOT NULL DEFAULT '',
    city VARCHAR(160) NOT NULL DEFAULT '',
    date DATE NOT NULL,
    heure VARCHAR(10),
    service VARCHAR(50) NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'En attente',
    reminder_sent_at DATETIME NULL,
    reminder_status VARCHAR(40) DEFAULT 'pending'
);

-- Table des véhicules (catalogue)
CREATE TABLE IF NOT EXISTS voitures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marque VARCHAR(50) NOT NULL,
    modele VARCHAR(50) NOT NULL,
    annee INT,
    prix INT NOT NULL,
    kilometrage INT,
    description TEXT,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Catalogue dynamique : annonces véhicules et pièces
CREATE TABLE IF NOT EXISTS catalog_annonces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('vehicle', 'part') NOT NULL,
    titre VARCHAR(190) NOT NULL,
    sous_titre VARCHAR(255) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    resume_court TEXT NOT NULL,
    description_longue MEDIUMTEXT NOT NULL,
    renseignements MEDIUMTEXT NOT NULL,
    statut ENUM('available', 'reserved') DEFAULT 'available',
    acompte_confirme BOOLEAN DEFAULT FALSE,
    current_vehicle_request_id VARCHAR(80) NULL,
    current_part_request_id VARCHAR(80) NULL,
    transaction_in_progress BOOLEAN DEFAULT FALSE,
    transaction_started_at DATETIME NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS catalog_annonce_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    annonce_id INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    image_blob LONGBLOB NOT NULL,
    ordre_affichage INT DEFAULT 0,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_catalog_images_annonce
        FOREIGN KEY (annonce_id) REFERENCES catalog_annonces(id)
        ON DELETE CASCADE
);

    CREATE TABLE IF NOT EXISTS catalog_vehicle_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        annonce_id INT NOT NULL,
        firstname VARCHAR(120) NOT NULL,
        lastname VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(40) NOT NULL,
        desired_date DATE NULL,
        message TEXT,
        request_status ENUM('queued', 'active', 'failed', 'closed') DEFAULT 'queued',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_catalog_vehicle_requests_annonce
        FOREIGN KEY (annonce_id) REFERENCES catalog_annonces(id)
        ON DELETE CASCADE
    );

CREATE TABLE IF NOT EXISTS catalog_part_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    annonce_id INT NOT NULL,
    firstname VARCHAR(120) NOT NULL,
    lastname VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    message TEXT,
    request_status ENUM('queued', 'active', 'failed', 'closed') DEFAULT 'queued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_catalog_part_requests_annonce
        FOREIGN KEY (annonce_id) REFERENCES catalog_annonces(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS customer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_type VARCHAR(20) NOT NULL DEFAULT 'individual',
    firstname VARCHAR(120) NOT NULL,
    lastname VARCHAR(120) NOT NULL,
    address_line VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL DEFAULT '',
    city VARCHAR(160) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    registration VARCHAR(40) NOT NULL,
    last_source VARCHAR(40) DEFAULT 'contact',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_customer_profiles_email (email),
    KEY idx_customer_profiles_phone (phone),
    KEY idx_customer_profiles_registration (registration)
);

CREATE TABLE IF NOT EXISTS catalog_transaction_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_type ENUM('vehicle', 'part') NOT NULL,
    item_id INT NOT NULL,
    event_name VARCHAR(80) NOT NULL,
    outcome ENUM('concluded', 'failed', 'pending') DEFAULT 'pending',
    metadata TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_catalog_transaction_outcome (outcome),
    KEY idx_catalog_transaction_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS site_visit_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    path_key VARCHAR(120) NOT NULL,
    visit_date DATE NOT NULL,
    hits INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_site_visit_path_date (path_key, visit_date)
);

-- Table des messages de contact
CREATE TABLE IF NOT EXISTS messages_contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    sujet VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lu BOOLEAN DEFAULT FALSE
);

-- Table des demandes de devis
CREATE TABLE IF NOT EXISTS demandes_devis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_type VARCHAR(20) NOT NULL DEFAULT 'individual',
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL DEFAULT '',
    city VARCHAR(160) NOT NULL DEFAULT '',
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(30) NOT NULL,
    immatriculation VARCHAR(30) NOT NULL,
    sujet VARCHAR(200) NOT NULL,
    prestations TEXT NOT NULL,
    message TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut VARCHAR(20) DEFAULT 'Nouveau'
);

CREATE TABLE IF NOT EXISTS postal_code_reference (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insee_code VARCHAR(10) NOT NULL DEFAULT '',
    commune_name VARCHAR(191) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    routing_label VARCHAR(191) NOT NULL DEFAULT '',
    line5 VARCHAR(191) NOT NULL DEFAULT '',
    city_name VARCHAR(191) NOT NULL,
    UNIQUE KEY uq_postal_reference (postal_code, city_name),
    KEY idx_postal_reference_code (postal_code),
    KEY idx_postal_reference_city (city_name)
);

CREATE TABLE IF NOT EXISTS postal_code_reference_meta (
    id TINYINT PRIMARY KEY,
    source_name VARCHAR(255) NOT NULL DEFAULT '',
    source_mtime BIGINT NOT NULL DEFAULT 0,
    imported_at DATETIME NULL,
    row_count INT NOT NULL DEFAULT 0
);

-- Insérer quelques véhicules d'exemple
INSERT INTO voitures (marque, modele, prix, kilometrage) VALUES 
('Renault', 'Clio', 8500, 120000),
('Peugeot', '208', 9500, 95000),
('Citroën', 'C3', 7800, 145000);