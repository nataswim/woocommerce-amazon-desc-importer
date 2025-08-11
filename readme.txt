=== Woo Amazon Description Importer ===
Contributors: mycreanet
Tags: woocommerce, amazon, asin, product description, pa-api, importer
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Importe la description Amazon (À propos de cet article + Description du fabricant + Description du produit) dans la description longue des produits WooCommerce, à partir de l’ASIN (stocké dans le SKU), via l’API Amazon Product Advertising v5.

== Description ==

Ce plugin ajoute un **bouton dans l’éditeur de produit WooCommerce** pour récupérer la description Amazon à partir de l’**ASIN** (renseigné dans le **SKU** du produit).  
Les 3 sections suivantes sont importées et concaténées :
- *À propos de cet article* (Features)
- *Description du fabricant* (EditorialReview Source=Manufacturer)
- *Description du produit* (autre EditorialReview)

Le contenu est inséré **directement dans l’éditeur de la description longue** (Gutenberg ou Classic), sans écraser le contenu existant.

**Aucune dépendance externe**. Signature **AWS SigV4** incluse.  
Utilise l’API officielle **Amazon Product Advertising v5 (PA-API)**.

== Installation ==

1. Téléversez le dossier `woocommerce-amazon-desc-importer` dans `/wp-content/plugins/` ou installez l’archive ZIP.
2. Activez le plugin via **Extensions**.
3. Allez dans **Réglages > Amazon PA-API** et saisissez :
   - Access Key
   - Secret Key
   - Partner Tag
   - Region (ex: eu-west-1)
   - Host (ex: webservices.amazon.fr)
   - Marketplace (ex: www.amazon.fr)
4. Dans un produit WooCommerce, renseignez le **SKU = ASIN** (10 caractères).
5. Cliquez sur **Importer la description Amazon (ASIN depuis SKU)** au-dessus de l’éditeur.

== FAQ ==

= Est-ce conforme aux CGU ? =
Oui, le plugin utilise l’API officielle PA-API v5. Pas de scraping.

= Et si l’ASIN est invalide ? =
Le plugin vérifie le format `^[A-Z0-9]{10}$` et affiche une erreur.

= Gutenberg et Classic ? =
Les deux sont gérés. Le contenu est ajouté à la suite de la description existante.

= Quotas et limites ? =
La PA-API a des quotas. En cas d’erreurs 429/5xx, réessayez plus tard.

== Changelog ==

= 1.0.0 =
* Version initiale : bouton éditeur, requête PA-API v5, insertion des 3 sections.

== Credits ==
Développé par Hassan EL HAOUAT (MYCREANET / nataswim / SPORTNETSYST) – https://mycreanet.fr
