<?php
/**
 * Plugin Name: Woo Amazon Description Importer
 * Plugin URI:  https://mycreanet.fr
 * Description: Ajoute un bouton dans l’éditeur de produit WooCommerce pour importer la description Amazon (À propos de cet article + Description du fabricant + Description du produit) à partir de l’ASIN (stocké dans le SKU), via l’API Amazon Product Advertising (PA-API v5).
 * Version:     1.0.0
 * Author:      Hassan EL HAOUAT (MYCREANET / nataswim / SPORTNETSYST)
 * Author URI:  https://mycreanet.fr
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-amz-desc
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

class WADI_Plugin {
    const OPTION = 'wadi_settings';
    private static $instance = null;

    public static function instance(){
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct(){
        // Settings
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_settings_page']);

        // Admin UI: button + scripts
        add_action('edit_form_after_title', [$this, 'render_editor_button']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX
        add_action('wp_ajax_wadi_import_description_editor', [$this, 'ajax_import_description']);
    }

    public static function get_settings(){
        $defaults = [
            'access_key'  => '',
            'secret_key'  => '',
            'partner_tag' => '',
            'region'      => 'eu-west-1',
            'host'        => 'webservices.amazon.fr',
            'marketplace' => 'www.amazon.fr',
        ];
        $opt = get_option(self::OPTION, []);
        return wp_parse_args((array)$opt, $defaults);
    }

    public function register_settings(){
        register_setting('wadi_settings_group', self::OPTION, function($input){
            $out = [];
            $out['access_key']  = sanitize_text_field($input['access_key'] ?? '');
            $out['secret_key']  = sanitize_text_field($input['secret_key'] ?? '');
            $out['partner_tag'] = sanitize_text_field($input['partner_tag'] ?? '');
            $out['region']      = sanitize_text_field($input['region'] ?? 'eu-west-1');
            $out['host']        = sanitize_text_field($input['host'] ?? 'webservices.amazon.fr');
            $out['marketplace'] = sanitize_text_field($input['marketplace'] ?? 'www.amazon.fr');
            return $out;
        });

        add_settings_section('wadi_main', __('Clés PA-API v5', 'woo-amz-desc'), function(){
            echo '<p>'.esc_html__('Renseignez vos identifiants Amazon Product Advertising API v5.', 'woo-amz-desc').'</p>';
        }, 'wadi_settings');

        add_settings_field('access_key', __('Access Key', 'woo-amz-desc'), [$this,'field_text'], 'wadi_settings','wadi_main', ['name'=>'access_key']);
        add_settings_field('secret_key', __('Secret Key', 'woo-amz-desc'), [$this,'field_text'], 'wadi_settings','wadi_main', ['name'=>'secret_key']);
        add_settings_field('partner_tag', __('Partner Tag', 'woo-amz-desc'), [$this,'field_text'], 'wadi_settings','wadi_main', ['name'=>'partner_tag']);
        add_settings_field('region', __('Region', 'woo-amz-desc'), [$this,'field_text'], 'wadi_settings','wadi_main', ['name'=>'region', 'placeholder'=>'eu-west-1']);
        add_settings_field('host', __('Host', 'woo-amz-desc'), [$this,'field_text'], 'wadi_settings','wadi_main', ['name'=>'host', 'placeholder'=>'webservices.amazon.fr']);
        add_settings_field('marketplace', __('Marketplace', 'woo-amz-desc'), [$this,'field_text'], 'wadi_settings','wadi_main', ['name'=>'marketplace', 'placeholder'=>'www.amazon.fr']);
    }

    public function field_text($args){
        $settings = self::get_settings();
        $name = esc_attr($args['name']);
        $val  = esc_attr($settings[$name] ?? '');
        $ph   = isset($args['placeholder']) ? ' placeholder="'.esc_attr($args['placeholder']).'"' : '';
        printf('<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s"%4$s />', self::OPTION, $name, $val, $ph);
    }

    public function add_settings_page(){
        add_options_page(
            __('Amazon PA-API', 'woo-amz-desc'),
            __('Amazon PA-API', 'woo-amz-desc'),
            'manage_options',
            'wadi-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page(){
        ?>
        <div class="wrap">
          <h1><?php echo esc_html__('Amazon PA-API v5 – Paramètres', 'woo-amz-desc'); ?></h1>
          <form method="post" action="options.php">
            <?php
              settings_fields('wadi_settings_group');
              do_settings_sections('wadi_settings');
              submit_button();
            ?>
          </form>
        </div>
        <?php
    }

    public function render_editor_button($post){
        if ($post->post_type !== 'product') return;
        echo '<div id="amz-import-desc-toolbar" style="margin:8px 0;">
                <button type="button" class="button button-primary" id="amz-import-desc-editor-btn">
                  '.esc_html__('Importer la description Amazon (ASIN depuis SKU)', 'woo-amz-desc').'
                </button>
                <span id="amz-import-desc-status" style="margin-left:8px;"></span>
              </div>';
    }

    public function enqueue_assets($hook){
        if (!in_array($hook, ['post.php','post-new.php'], true)) return;
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product') return;

        wp_enqueue_script('wadi-amz-importer-editor',
            plugins_url('assets/js/amz-importer-editor.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('wadi-amz-importer-editor', 'WADI_IMPORTER', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wadi_import_desc_editor'),
        ]);
    }

    public function ajax_import_description(){
        if (!current_user_can('edit_products')) {
            wp_send_json_error(['message'=>'Permission refusée'], 403);
        }
        check_ajax_referer('wadi_import_desc_editor', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $asin    = sanitize_text_field($_POST['asin'] ?? '');
        if (!$post_id || !$asin) {
            wp_send_json_error(['message'=>'Paramètres manquants'], 400);
        }
        if (!$this->is_valid_asin($asin)) {
            wp_send_json_error(['message'=>'ASIN invalide'], 400);
        }

        $html = $this->build_full_description($asin);
        if (!$html) {
            wp_send_json_error(['message'=>'Aucune description trouvée pour cet ASIN'], 404);
        }

        wp_send_json_success(['html'=>$html]);
    }

    private function is_valid_asin($asin){
        return (bool) preg_match('/^[A-Z0-9]{10}$/i', $asin);
    }

    private function build_full_description($asin){
        $settings = self::get_settings();
        foreach (['access_key','secret_key','partner_tag','region','host','marketplace'] as $key){
            if (empty($settings[$key])) return '';
        }

        $payload = [
            "ItemIds"   => [$asin],
            "Resources" => [
                "ItemInfo.Title",
                "ItemInfo.Features",
                "EditorialReviews.EditorialReview.Text",
                "EditorialReviews.EditorialReview.Source"
            ],
            "PartnerTag"  => $settings['partner_tag'],
            "PartnerType" => "Associates",
            "Marketplace" => $settings['marketplace']
        ];
        $data = $this->paapi_request('/paapi5/getitems', $payload, $settings);
        if (!$data) return '';

        $item = $data['ItemsResult']['Items'][0] ?? null;
        if (!$item) return '';

        $features = $item['ItemInfo']['Features']['DisplayValues'] ?? [];
        $editorials = $item['EditorialReviews']['EditorialReview'] ?? [];
        if (isset($editorials['Text'])) { $editorials = [$editorials]; }

        $manufacturer_desc = '';
        $product_desc = '';
        foreach($editorials as $rev){
            $src = $rev['Source'] ?? '';
            $txt = $rev['Text'] ?? '';
            if (!$txt) continue;
            if (strtolower($src) === 'manufacturer' && !$manufacturer_desc){
                $manufacturer_desc = $txt;
            } else {
                if (!$product_desc) $product_desc = $txt;
            }
        }

        $html = '';
        if (!empty($features)) {
            $html .= '<h2>'.esc_html__('À propos de cet article', 'woo-amz-desc').'</h2><ul>';
            foreach($features as $f){
                $html .= '<li>' . esc_html($f) . '</li>';
            }
            $html .= '</ul>';
        }

        if (!empty($manufacturer_desc)) {
            $html .= '<h2>'.esc_html__('Description du fabricant', 'woo-amz-desc').'</h2>';
            $html .= wp_kses_post($manufacturer_desc);
        }

        if (!empty($product_desc)) {
            $html .= '<h2>'.esc_html__('Description du produit', 'woo-amz-desc').'</h2>';
            $html .= wp_kses_post($product_desc);
        }

        if (trim(wp_strip_all_tags($html)) === '') {
            $title = $item['ItemInfo']['Title']['DisplayValue'] ?? '';
            $html = '<p>' . esc_html($title) . '</p>';
        }

        return $html;
    }

    private function paapi_request($uri, array $payload, array $settings){
        $host   = $settings['host'];
        $region = $settings['region'];
        $service = 'ProductAdvertisingAPI';
        $content_type = 'application/json; charset=UTF-8';

        $json = wp_json_encode($payload);
        $amz_date   = gmdate('Ymd\THis\Z');
        $date_stamp = gmdate('Ymd');

        $canonical_headers = "content-encoding:\ncontent-type:$content_type\nhost:$host\nx-amz-date:$amz_date\nx-amz-target:com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems\n";
        $signed_headers    = 'content-encoding;content-type;host;x-amz-date;x-amz-target';
        $payload_hash      = hash('sha256', $json);
        $canonical_request = implode("\n", [
            'POST', $uri, '',
            $canonical_headers,
            $signed_headers,
            $payload_hash
        ]);

        $algorithm        = 'AWS4-HMAC-SHA256';
        $credential_scope = "$date_stamp/$region/$service/aws4_request";
        $string_to_sign   = implode("\n", [
            $algorithm, $amz_date, $credential_scope, hash('sha256', $canonical_request)
        ]);

        $kSecret  = 'AWS4' . $settings['secret_key'];
        $kDate    = hash_hmac('sha256', $date_stamp, $kSecret, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $string_to_sign, $kSigning);

        $auth = $algorithm . ' ' . sprintf(
            'Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $settings['access_key'],
            $credential_scope,
            $signed_headers,
            $signature
        );

        $headers = [
            'Content-Encoding' => '',
            'Content-Type'     => $content_type,
            'Host'             => $host,
            'X-Amz-Date'       => $amz_date,
            'X-Amz-Target'     => 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems',
            'Authorization'    => $auth,
        ];

        $url  = 'https://' . $host . $uri;
        $args = ['headers'=>$headers, 'body'=>$json, 'timeout'=>20];
        $res  = wp_remote_post($url, $args);
        if (is_wp_error($res)) return null;

        $code = wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);
        if ($code !== 200 || empty($body)) return null;

        return json_decode($body, true);
    }
}

WADI_Plugin::instance();
