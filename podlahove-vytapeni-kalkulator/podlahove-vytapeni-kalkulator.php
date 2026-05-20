<?php

/**
 * Plugin Name: Kalkulátor podlahového vytápění
 * Plugin URI: https://allimedia.cz/
 * Description: Plugin pro výpočet nákladů na realizaci podlahového vytápění s administračním rozhraním a pokročilou font customizací včetně stylů písma.
 * Version: 1.9.0
 * Author: Allimedia.cz
 * Author URI: https://allimedia.cz/
 * Text Domain: podlahove-vytapeni
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 8.0
 */

// Zabránit přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definice konstant
define('PV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PV_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PV_VERSION', '1.8.9');

class PodlahoveVytapeniKalkulator
{

    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('podlahove_vytapeni_kalkulator', array($this, 'render_calculator'));

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('wp_loaded', array($this, 'create_font_upload_dir'));

        // Email odesílatele
        add_filter('wp_mail_from', array($this, 'custom_mail_from'));
        add_filter('wp_mail_from_name', array($this, 'custom_mail_from_name'));
    }

    public function init()
    {
        load_plugin_textdomain('podlahove-vytapeni', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // AJAX akce
        add_action('wp_ajax_calculate_heating', array($this, 'ajax_calculate_heating'));
        add_action('wp_ajax_nopriv_calculate_heating', array($this, 'ajax_calculate_heating'));
        add_action('wp_ajax_send_calculation_email', array($this, 'ajax_send_calculation_email'));
        add_action('wp_ajax_nopriv_send_calculation_email', array($this, 'ajax_send_calculation_email'));

        // AJAX akce pro fonty
        add_action('wp_ajax_pv_delete_font', array($this, 'ajax_delete_font'));
        add_action('wp_ajax_pv_preview_font', array($this, 'ajax_preview_font'));

        // Hook pro generování custom CSS
        add_action('wp_head', array($this, 'output_custom_css'));
    }

    public function activate()
    {
        // Vytvoření default nastavení s rozšířenými font možnostmi včetně stylů
        $default_settings = array(
            'tacker_system_price' => 200,
            'system_board_price' => 450,
            'pipe_17x2_increase' => 6,
            'pipe_18x2_increase' => 12,
            'pipe_alu_16x2_increase' => 5,
            'pipe_alu_18x2_increase' => 15,
            'low_temp_source_price' => 5000,
            'high_temp_source_price' => 12000,
            'radiator_combo_price' => 12000,
            'decimal_places' => 0,
            'max_floors' => 5,
            'admin_email' => get_option('admin_email'),
            'company_name' => get_option('blogname'),
            'primary_color' => '#0073aa',
            'button_color' => '#00a32a',
            'button_text_color' => '#ffffff',
            'button_hover_color' => '#008a2e',
            'button_hover_text_color' => '#ffffff',
            'hover_background' => '#f0f8ff',

            // Font nastavení
            'uploaded_fonts' => array(),
            'selected_font' => 'default',

            // Font velikosti a váhy
            'heading_font_size' => 20,
            'heading_font_weight' => 600,
            'label_font_size' => 14,
            'label_font_weight' => 600,
            'button_font_size' => 16,
            'button_font_weight' => 600,

            // NOVÉ: Font styly a transformace
            'heading_font_style' => 'normal',
            'heading_text_transform' => 'none',
            'label_font_style' => 'normal',
            'label_text_transform' => 'none',
            'button_font_style' => 'normal',
            'button_text_transform' => 'none'
        );

        add_option('pv_settings', $default_settings);
        $this->create_font_upload_dir();
    }

    public function deactivate()
    {
        // Cleanup pokud je potřeba
    }

    public function create_font_upload_dir()
    {
        $upload_dir = wp_upload_dir();
        $font_dir = $upload_dir['basedir'] . '/pv-fonts';

        if (!file_exists($font_dir)) {
            wp_mkdir_p($font_dir);

            // Vytvoření .htaccess pro bezpečnost (volitelné)
            $htaccess_content = "# Povolit pouze font soubory\n";
            $htaccess_content .= "<FilesMatch \"\\.(woff|woff2|ttf|otf)$\">\n";
            $htaccess_content .= "    Allow from all\n";
            $htaccess_content .= "</FilesMatch>\n";
            $htaccess_content .= "<FilesMatch \"\\.(php|js|html)$\">\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</FilesMatch>\n";

            file_put_contents($font_dir . '/.htaccess', $htaccess_content);
        }
    }

    /**
     * Vlastní email odesílatele - UNIVERZÁLNÍ
     */
    public function custom_mail_from($original_email_address)
    {
        // Automaticky vezme doménu z WordPress instalace
        $domain = parse_url(home_url(), PHP_URL_HOST);
        // Odstraní www. pokud existuje
        $domain = preg_replace('/^www\./', '', $domain);
        return 'noreply@' . $domain;
    }

    /**
     * Vlastní jméno odesílatele
     */
    public function custom_mail_from_name($original_email_from)
    {
        $settings = get_option('pv_settings');
        // Použije název firmy z nastavení
        return $settings['company_name'] ?? get_option('blogname');
    }

    public function enqueue_scripts()
    {
        if (is_admin()) return;

        wp_enqueue_style('pv-calculator-style', PV_PLUGIN_URL . 'assets/css/calculator.css', array(), PV_VERSION);
        wp_enqueue_script('pv-calculator-script', PV_PLUGIN_URL . 'assets/js/calculator.js', array('jquery'), PV_VERSION, true);

        $settings = get_option('pv_settings');

        wp_localize_script('pv-calculator-script', 'pv_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pv_calculator_nonce'),
            'decimal_places' => intval($settings['decimal_places'] ?? 0)
        ));
    }

    public function admin_enqueue_scripts($hook)
    {
        if ($hook !== 'toplevel_page_podlahove-vytapeni-settings') return;

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('pv-admin-style', PV_PLUGIN_URL . 'assets/css/admin.css', array(), PV_VERSION);
        wp_enqueue_script('pv-admin-script', PV_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), PV_VERSION, true);

        // Localize script pro font handling
        wp_localize_script('pv-admin-script', 'pv_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pv_admin_nonce'),
        ));
    }

    public function admin_menu()
    {
        add_menu_page(
            'Kalkulátor podlahového vytápění',
            'Podlahové vytápění',
            'manage_options',
            'podlahove-vytapeni-settings',
            array($this, 'admin_page'),
            'dashicons-admin-tools',
            30
        );
    }

    public function admin_page()
    {
        if (isset($_POST['submit'])) {
            check_admin_referer('pv_settings_nonce');
            $this->save_settings();
        }

        $settings = get_option('pv_settings');
        include PV_PLUGIN_PATH . 'templates/admin-page.php';
    }

    private function save_settings()
    {
        $settings = array(
            'tacker_system_price' => floatval($_POST['tacker_system_price']),
            'system_board_price' => floatval($_POST['system_board_price']),
            'pipe_17x2_increase' => floatval($_POST['pipe_17x2_increase']),
            'pipe_18x2_increase' => floatval($_POST['pipe_18x2_increase']),
            'pipe_alu_16x2_increase' => floatval($_POST['pipe_alu_16x2_increase']),
            'pipe_alu_18x2_increase' => floatval($_POST['pipe_alu_18x2_increase']),
            'low_temp_source_price' => floatval($_POST['low_temp_source_price']),
            'high_temp_source_price' => floatval($_POST['high_temp_source_price']),
            'radiator_combo_price' => floatval($_POST['radiator_combo_price']),
            'decimal_places' => intval($_POST['decimal_places']),
            'max_floors' => intval($_POST['max_floors']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'primary_color' => sanitize_hex_color($_POST['primary_color']),
            'button_color' => sanitize_hex_color($_POST['button_color']),
            'button_text_color' => sanitize_hex_color($_POST['button_text_color']),
            'button_hover_color' => sanitize_hex_color($_POST['button_hover_color']),
            'button_hover_text_color' => sanitize_hex_color($_POST['button_hover_text_color']),
            'hover_background' => sanitize_hex_color($_POST['hover_background']),
            'selected_font' => sanitize_text_field($_POST['selected_font']),

            // Font velikosti a váhy
            'heading_font_size' => intval($_POST['heading_font_size']),
            'heading_font_weight' => intval($_POST['heading_font_weight']),
            'label_font_size' => intval($_POST['label_font_size']),
            'label_font_weight' => intval($_POST['label_font_weight']),
            'button_font_size' => intval($_POST['button_font_size']),
            'button_font_weight' => intval($_POST['button_font_weight']),

            // NOVÉ: Font styly a transformace
            'heading_font_style' => $this->sanitize_font_style($_POST['heading_font_style']),
            'heading_text_transform' => $this->sanitize_text_transform($_POST['heading_text_transform']),
            'label_font_style' => $this->sanitize_font_style($_POST['label_font_style']),
            'label_text_transform' => $this->sanitize_text_transform($_POST['label_text_transform']),
            'button_font_style' => $this->sanitize_font_style($_POST['button_font_style']),
            'button_text_transform' => $this->sanitize_text_transform($_POST['button_text_transform'])
        );

        // Zachovat existující fonty
        $old_settings = get_option('pv_settings');
        $settings['uploaded_fonts'] = $old_settings['uploaded_fonts'] ?? array();

        // Handling file upload
        if (!empty($_FILES['custom_font_upload']['name'])) {
            $uploaded_font = $this->handle_font_upload($_FILES['custom_font_upload']);
            if ($uploaded_font) {
                $font_key = sanitize_title(pathinfo($_FILES['custom_font_upload']['name'], PATHINFO_FILENAME));
                $font_key = $font_key . '_' . time(); // Přidat timestamp pro unikátnost

                $settings['uploaded_fonts'][$font_key] = $uploaded_font;
            }
        }

        update_option('pv_settings', $settings);
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>Nastavení bylo úspěšně uloženo!</p></div>';
        });
    }

    /**
     * Sanitizace font-style hodnot
     */
    private function sanitize_font_style($value)
    {
        $allowed_styles = array('normal', 'italic');
        return in_array($value, $allowed_styles) ? $value : 'normal';
    }

    /**
     * Sanitizace text-transform hodnot
     */
    private function sanitize_text_transform($value)
    {
        $allowed_transforms = array('none', 'uppercase', 'lowercase', 'capitalize');
        return in_array($value, $allowed_transforms) ? $value : 'none';
    }

    private function handle_font_upload($file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>Chyba při nahrávání fontu.</p></div>';
            });
            return false;
        }

        // Validace typu souboru
        $allowed_types = array('woff', 'woff2', 'ttf', 'otf');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_types)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>Nepodporovaný formát fontu. Použijte WOFF, WOFF2, TTF nebo OTF.</p></div>';
            });
            return false;
        }

        // Validace velikosti (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>Font je příliš velký. Maximální velikost je 2MB.</p></div>';
            });
            return false;
        }

        // Přesun souboru do upload složky
        $upload_dir = wp_upload_dir();
        $font_dir = $upload_dir['basedir'] . '/pv-fonts';

        $sanitized_filename = sanitize_file_name($file['name']);
        $target_path = $font_dir . '/' . $sanitized_filename;

        // Pokud soubor existuje, přidat timestamp
        if (file_exists($target_path)) {
            $pathinfo = pathinfo($sanitized_filename);
            $sanitized_filename = $pathinfo['filename'] . '_' . time() . '.' . $pathinfo['extension'];
            $target_path = $font_dir . '/' . $sanitized_filename;
        }

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return array(
                'name' => pathinfo($file['name'], PATHINFO_FILENAME),
                'filename' => $sanitized_filename,
                'url' => $upload_dir['baseurl'] . '/pv-fonts/' . $sanitized_filename,
                'path' => $target_path,
                'format' => $this->get_font_format($file_extension)
            );
        }

        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p>Nepodařilo se nahrát font.</p></div>';
        });
        return false;
    }

    private function get_font_format($extension)
    {
        $formats = array(
            'woff2' => 'woff2',
            'woff' => 'woff',
            'ttf' => 'truetype',
            'otf' => 'opentype'
        );
        return $formats[$extension] ?? 'truetype';
    }

    public function ajax_delete_font()
    {
        check_ajax_referer('pv_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nemáte oprávnění');
        }

        $font_key = sanitize_text_field($_POST['font_key']);
        $settings = get_option('pv_settings');

        if (isset($settings['uploaded_fonts'][$font_key])) {
            $font_info = $settings['uploaded_fonts'][$font_key];

            // Smazat soubor z disku
            if (file_exists($font_info['path'])) {
                unlink($font_info['path']);
            }

            // Odebrat z nastavení
            unset($settings['uploaded_fonts'][$font_key]);

            // Pokud byl odstraněný font aktivní, nastavit default
            if ($settings['selected_font'] === $font_key) {
                $settings['selected_font'] = 'default';
            }

            update_option('pv_settings', $settings);

            wp_send_json_success('Font byl úspěšně odstraněn');
        }

        wp_send_json_error('Font nebyl nalezen');
    }

    public function output_custom_css()
    {
        $settings = get_option('pv_settings');

        echo '<style id="pv-custom-styles">';

        // Font faces
        if (!empty($settings['uploaded_fonts']) && $settings['selected_font'] !== 'default') {
            foreach ($settings['uploaded_fonts'] as $font_key => $font_info) {
                echo "@font-face {\n";
                echo "    font-family: 'pv-custom-{$font_key}';\n";
                echo "    src: url('{$font_info['url']}') format('{$font_info['format']}');\n";
                echo "    font-display: swap;\n";
                echo "}\n";
            }

            // Aplikovat vybraný font
            if (isset($settings['uploaded_fonts'][$settings['selected_font']])) {
                echo ".pv-calculator {\n";
                echo "    font-family: 'pv-custom-{$settings['selected_font']}', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;\n";
                echo "}\n";
                echo ".pv-calculator * {\n";
                echo "    font-family: inherit !important;\n";
                echo "}\n";
            }
        }

        // Font velikosti, váhy a NOVĚ styly - opravené selektory
        if (isset($settings['heading_font_size'])) {
            echo ".pv-floor-header h3 {\n";
            echo "    font-size: {$settings['heading_font_size']}px !important;\n";
            echo "    font-weight: {$settings['heading_font_weight']} !important;\n";
            echo "    font-style: {$settings['heading_font_style']} !important;\n";
            echo "    text-transform: {$settings['heading_text_transform']} !important;\n";
            echo "}\n";
        }

        if (isset($settings['label_font_size'])) {
            // Pouze labely formulářových skupin, ne text v dlaždicích
            echo ".pv-form-group > label {\n";
            echo "    font-size: {$settings['label_font_size']}px !important;\n";
            echo "    font-weight: {$settings['label_font_weight']} !important;\n";
            echo "    font-style: {$settings['label_font_style']} !important;\n";
            echo "    text-transform: {$settings['label_text_transform']} !important;\n";
            echo "}\n";
        }

        if (isset($settings['button_font_size'])) {
            echo ".pv-btn {\n";
            echo "    font-size: {$settings['button_font_size']}px !important;\n";
            echo "    font-weight: {$settings['button_font_weight']} !important;\n";
            echo "    font-style: {$settings['button_font_style']} !important;\n";
            echo "    text-transform: {$settings['button_text_transform']} !important;\n";
            echo "}\n";
        }

        echo '</style>';
    }

    public function render_calculator($atts)
    {
        $atts = shortcode_atts(array(
            'title' => 'Kalkulátor podlahového vytápění'
        ), $atts);

        $settings = get_option('pv_settings');

        ob_start();
        include PV_PLUGIN_PATH . 'templates/calculator.php';
        return ob_get_clean();
    }

    public function ajax_calculate_heating()
    {
        check_ajax_referer('pv_calculator_nonce', 'nonce');

        $floors = json_decode(stripslashes($_POST['floors']), true);
        $settings = get_option('pv_settings');
        $decimal_places = intval($settings['decimal_places'] ?? 0);

        $total_cost = 0;
        $calculation_details = array();
        $first_floor_heat_source_price = 0; // Uložíme si cenu ze prvního patra

        foreach ($floors as $index => $floor) {
            $floor_cost = 0;
            $area = floatval($floor['area']);

            // Základní cena podle typu instalace
            if ($floor['installation_type'] === 'tacker') {
                $base_cost = $area * $settings['tacker_system_price'];
            } else {
                $base_cost = $area * $settings['system_board_price'];
            }

            $floor_cost = $base_cost;

            // Příplatek za typ potrubí
            switch ($floor['pipe_type']) {
                case 'pe_17x2':
                    $floor_cost *= (1 + $settings['pipe_17x2_increase'] / 100);
                    break;
                case 'pe_18x2':
                    $floor_cost *= (1 + $settings['pipe_18x2_increase'] / 100);
                    break;
                case 'alu_16x2':
                    $floor_cost *= (1 + $settings['pipe_alu_16x2_increase'] / 100);
                    break;
                case 'alu_18x2':
                    $floor_cost *= (1 + $settings['pipe_alu_18x2_increase'] / 100);
                    break;
            }

            // Příplatek za zdroj tepla (pouze pro první podlaží)
            if ($index === 0) {
                switch ($floor['heat_source']) {
                    case 'low_temp':
                        $first_floor_heat_source_price = $settings['low_temp_source_price'];
                        $floor_cost += $first_floor_heat_source_price;
                        break;
                    case 'high_temp':
                        $first_floor_heat_source_price = $settings['high_temp_source_price'];
                        $floor_cost += $first_floor_heat_source_price;
                        break;
                    case 'radiator_combo':
                        $first_floor_heat_source_price = $settings['radiator_combo_price'];
                        $floor_cost += $first_floor_heat_source_price;
                        break;
                }
            }

            // Příplatek za rozdělovač pro druhé a další podlaží = cena zdroje tepla z prvního patra
            if ($index > 0 && $first_floor_heat_source_price > 0) {
                $floor_cost += $first_floor_heat_source_price;
            }

            $calculation_details[] = array(
                'floor' => $index + 1,
                'area' => $area,
                'cost' => round($floor_cost, $decimal_places)
            );

            $total_cost += $floor_cost;
        }

        wp_send_json_success(array(
            'total_cost' => round($total_cost, $decimal_places),
            'details' => $calculation_details
        ));
    }

    /**
     * NOVÁ FUNKCE: Převede kódové hodnoty na čitelný text
     */
    private function get_readable_parameter($type, $value)
    {
        $labels = array(
            'installation_type' => array(
                'tacker' => 'Tacker systém (instalace na folii)',
                'system_board' => 'Systémová deska (s výstupky)'
            ),
            'pipe_type' => array(
                'pe_16x2' => '16x2 polyethylenová trubka (bez příplatku)',
                'pe_17x2' => '17x2 polyethylenová trubka',
                'pe_18x2' => '18x2 polyethylenová trubka',
                'alu_16x2' => '16x2 plastohliníková trubka',
                'alu_18x2' => '18x2 plastohliníková trubka',
                'advice' => 'Nevím - nechám si poradit'
            ),
            'heat_source' => array(
                'low_temp' => 'Nízkoteplotní (TČ, kondenzační kotel, elektrokotel)',
                'high_temp' => 'Vysokoteplotní (tuhá paliva, akumulační zásobníky)',
                'radiator_combo' => 'Kombinace s radiátory',
                '' => 'Použije se dle 1. podlaží'
            )
        );

        return $labels[$type][$value] ?? $value;
    }

    public function ajax_send_calculation_email()
    {
        check_ajax_referer('pv_calculator_nonce', 'nonce');

        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $total_cost = floatval($_POST['total_cost']);
        $contact_support = ($_POST['contact_support'] ?? '0') === '1';
        $calculation_details = json_decode(stripslashes($_POST['calculation_details']), true);

        // NOVÉ: Zpracování kompletních dat o podlažích
        $floors_data = json_decode(stripslashes($_POST['floors_data']), true);

        $settings = get_option('pv_settings');

        // Email zákazníkovi - NOVÝ DETAILNÍ FORMÁT
        $subject = 'Výpočet nákladů na podlahové vytápění';
        $message = "Dobrý den,\n\n";
        $message .= "děkujeme za využití naší kalkulačky podlahového vytápění.\n\n";

        $message .= "═══════════════════════════════════════════════\n";
        $message .= "VAŠE KALKULACE - DETAILNÍ PŘEHLED\n";
        $message .= "═══════════════════════════════════════════════\n\n";

        // Detaily každého podlaží pro zákazníka
        foreach ($calculation_details as $index => $detail) {
            $floor_num = $detail['floor'];

            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "PODLAŽÍ {$floor_num}\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📐 Plocha: {$detail['area']} m²\n";

            // Přidání detailů z floors_data
            if (isset($floors_data[$index])) {
                $floor_info = $floors_data[$index];

                $message .= "🔧 Instalace: " . $this->get_readable_parameter('installation_type', $floor_info['installation_type']) . "\n";
                $message .= "🔩 Potrubí: " . $this->get_readable_parameter('pipe_type', $floor_info['pipe_type']) . "\n";

                // Zdroj tepla pouze pro první podlaží nebo pokud je vyplněný
                if ($index === 0 || !empty($floor_info['heat_source'])) {
                    $message .= "🔥 Zdroj tepla: " . $this->get_readable_parameter('heat_source', $floor_info['heat_source']) . "\n";
                }
            }

            $message .= "\n💰 Cena podlaží: " . number_format($detail['cost'], 2, ',', ' ') . " Kč\n\n";
        }

        $message .= "═══════════════════════════════════════════════\n";
        $message .= "✨ CELKOVÁ ORIENTAČNÍ CENA: " . number_format($total_cost, 2, ',', ' ') . " Kč\n";
        $message .= "═══════════════════════════════════════════════\n\n";

        $message .= "ℹ️  Jedná se o orientační cenovou kalkulaci. Přesná cena\n";
        $message .= "   bude stanovena po nezávazné konzultaci.\n\n";

        if ($contact_support) {
            $message .= "✅ POŽADAVEK NA KONTAKTOVÁNÍ\n";
            $message .= "───────────────────────────────────────────────\n";
            $message .= "Požádali jste o kontaktování naší technické podpory\n";
            $message .= "pro upřesnění a zaslání detailního výpisu prvků.\n";
            $message .= "Budeme vás kontaktovat v nejbližší pracovní době.\n\n";
            $message .= "Vaše kontaktní údaje:\n";
            $message .= "📧 Email: {$email}\n";
            if ($phone) {
                $message .= "📞 Telefon: {$phone}\n";
            }
            $message .= "\n";
        }

        $message .= "Máte dotazy? Neváhejte nás kontaktovat!\n\n";
        $message .= "S pozdravem,\n";
        $message .= $settings['company_name'];

        wp_mail($email, $subject, $message);

        // Email administrátorovi - NOVÝ ROZŠÍŘENÝ FORMÁT
        if ($contact_support) {
            $admin_subject = 'Nová žádost o kontaktování - Kalkulátor podlahového vytápění';
            $admin_message = "Nová žádost o kontaktování:\n\n";
            $admin_message .= "══════════════════════════════════════════════\n";
            $admin_message .= "KONTAKTNÍ ÚDAJE\n";
            $admin_message .= "══════════════════════════════════════════════\n";
            $admin_message .= "Email: {$email}\n";
            $admin_message .= "Telefon: " . ($phone ? $phone : 'neuvedeno') . "\n";
            $admin_message .= "Datum žádosti: " . date('d.m.Y H:i:s') . "\n\n";

            $admin_message .= "══════════════════════════════════════════════\n";
            $admin_message .= "SOUHRN KALKULACE\n";
            $admin_message .= "══════════════════════════════════════════════\n";
            $admin_message .= "Celková orientační cena: " . number_format($total_cost, 2, ',', ' ') . " Kč\n";
            $admin_message .= "Počet podlaží: " . count($calculation_details) . "\n\n";

            // NOVÉ: Detailní informace o každém podlaží
            $admin_message .= "══════════════════════════════════════════════\n";
            $admin_message .= "DETAILY PODLAŽÍ\n";
            $admin_message .= "══════════════════════════════════════════════\n\n";

            foreach ($calculation_details as $index => $detail) {
                $floor_num = $detail['floor'];
                $admin_message .= "─────────────────────────────────────────────\n";
                $admin_message .= "PODLAŽÍ {$floor_num}\n";
                $admin_message .= "─────────────────────────────────────────────\n";
                $admin_message .= "Plocha: {$detail['area']} m²\n";

                // Přidání detailů z floors_data
                if (isset($floors_data[$index])) {
                    $floor_info = $floors_data[$index];

                    $admin_message .= "Varianta instalace: " . $this->get_readable_parameter('installation_type', $floor_info['installation_type']) . "\n";
                    $admin_message .= "Typ potrubí: " . $this->get_readable_parameter('pipe_type', $floor_info['pipe_type']) . "\n";

                    // Zdroj tepla pouze pro první podlaží nebo pokud je vyplněný
                    if ($index === 0 || !empty($floor_info['heat_source'])) {
                        $admin_message .= "Zdroj tepla: " . $this->get_readable_parameter('heat_source', $floor_info['heat_source']) . "\n";
                    }
                }

                $admin_message .= "Cena podlaží: " . number_format($detail['cost'], 2, ',', ' ') . " Kč\n";
                $admin_message .= "\n";
            }

            $admin_message .= "══════════════════════════════════════════════\n\n";
            $admin_message .= "Pro odpověď zákazníkovi použijte email: {$email}\n";
            if ($phone) {
                $admin_message .= "Pro telefonický kontakt: {$phone}\n";
            }
            $admin_message .= "\n---\n";
            $admin_message .= "Tato zpráva byla vygenerována automaticky kalkulátorem podlahového vytápění.\n";

            wp_mail($settings['admin_email'], $admin_subject, $admin_message);
        }

        wp_send_json_success(array('message' => 'Email byl úspěšně odeslán!'));
    }
}

// Inicializace pluginu
new PodlahoveVytapeniKalkulator();
