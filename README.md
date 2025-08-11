# woocommerce-amazon-desc-importer
# WooCommerce Amazon Description Importer

Importer la **description produit** depuis Amazon vers des fiches produits WooCommerce, à partir d’un **ASIN** ou d’une **URL Amazon**.  
Idéal pour accélérer la création de fiches tout en gardant la main sur l’édition finale (SEO, mise en forme, médias).

## Sommaire
- [Fonctionnalités](#fonctionnalités)
- [Compatibilité](#compatibilité)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Sécurité & permissions](#sécurité--permissions)
- [FAQ](#faq)
- [Développement](#développement)
- [Changelog](#changelog)
- [Licence](#licence)

## Fonctionnalités
- Import de la **description longue** Amazon vers `post_content` (et option pour `excerpt`).
- Détection par **ASIN** (recommandé) ou **URL** Amazon.
- Option de **nettoyage/normalisation HTML** (balises basiques autorisées).
- Journalisation minimale des erreurs (mode debug).
- Interface d’admin simple (page Réglages + page Import).

> Ce plugin **n’importe pas** les prix, stocks, images ou variations. Il se concentre sur **la description**.

## Compatibilité
- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+
- Optionnel : Amazon Product Advertising API (PA-API) si vous choisissez ce mode d’extraction.

## Installation
1. Téléchargez l’archive ZIP du plugin (Release GitHub conseillée).
2. Dans WordPress → **Extensions** → **Ajouter** → **Téléverser une extension**, uploadez le ZIP.
3. Activez le plugin.

**Via Git (dev)**  
```bash
git clone https://github.com/<user>/woocommerce-amazon-desc-importer.git
cd woocommerce-amazon-desc-importer
# Zip si nécessaire :
zip -r woocommerce-amazon-desc-importer.zip . -x ".git*" ".github/*" "tests/*"
