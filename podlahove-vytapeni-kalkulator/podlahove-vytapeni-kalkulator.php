<?php

/**
 * Plugin Name: Kalkulátor podlahového vytápění
 * Plugin URI: https://allimedia.cz/
 * Description: Plugin pro výpočet nákladů na realizaci podlahového vytápění s administračním rozhraním.
 * Version: 1.6.2
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
define('PV_VERSION', '1.6.0');

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

        // Vytvoření upload složky pro fonty při aktivaci
        add_action('wp_loaded', array($this, 'create_font_upload_dir'));
    }

    public function init()
    {
        load_plugin_textdomain('podlahove-vytapeni', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // AJAX akce
        add_action('wp_ajax_calculate_heating', array($this, 'ajax_calculate_heating'));
        add_action('wp_ajax_nopriv_calculate_heating', array($this, 'ajax_calculate_heating'));
        add_action('wp_ajax_send_calculation_email', array($this, 'ajax_send_calculation_email'));
        add_action('wp_ajax_nopriv_send_calculation_email', array($this, 'ajax_send_calculation_email'));
        
        // Nové AJAX akce pro fonty
        add_action('wp_ajax_pv_delete_font', array($this, 'ajax_delete_font'));
        add_action('wp_ajax_pv_preview_font', array($this, 'ajax_preview_font'));

        // Hook pro generování custom CSS
        add_action('wp_head', array($this, 'output_custom_css'));
    }

    public function activate()
    {
        // Vytvoření default nastavení
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
            'uploaded_fonts' => array(),
            'selected_font' => 'default'
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
            
            /*
            // Vytvoření .htaccess pro bezpečnost
            $htaccess_content = "# Povolit pouze font soubory\n";
            $htaccess_content .= "<FilesMatch \"\\.(woff|woff2|ttf|otf)$\">\n";
            $htaccess_content .= "    Allow from all\n";
            $htaccess_content .= "</FilesMatch>\n";
            $htaccess_content .= "<FilesMatch \"\\.(php|js|html)$\">\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</FilesMatch>\n";
            
            file_put_contents($font_dir . '/.htaccess', $htaccess_content);
            */
        }
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
            'selected_font' => sanitize_text_field($_POST['selected_font'])
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
        
        // Font styles
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

            // OPRAVA: Příplatek za rozdělovač pro druhé a další podlaží = cena zdroje tepla z prvního patra
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

    public function ajax_send_calculation_email()
    {
        check_ajax_referer('pv_calculator_nonce', 'nonce');

        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $total_cost = floatval($_POST['total_cost']);
        $contact_support = ($_POST['contact_support'] ?? '0') === '1';
        $calculation_details = json_decode(stripslashes($_POST['calculation_details']), true);

        $settings = get_option('pv_settings');

        // Email zákazníkovi
        $subject = 'Výpočet nákladů na podlahové vytápění';
        $message = "Dobrý den,\n\n";
        $message .= "děkujeme za využití naší kalkulačky podlahového vytápění.\n\n";
        $message .= "Vaše kalkulace:\n";

        foreach ($calculation_details as $detail) {
            $message .= "Podlaží {$detail['floor']}: {$detail['area']} m² = " . number_format($detail['cost'], 2, ',', ' ') . " Kč\n";
        }

        $message .= "\nCelková orientační cena: " . number_format($total_cost, 2, ',', ' ') . " Kč\n\n";

        if ($contact_support) {
            $message .= "Požádali jste o kontaktování naší technické podpory. Budeme vás kontaktovat v nejbližší době.\n\n";
        }

        $message .= "S pozdravem,\n" . $settings['company_name'];

        wp_mail($email, $subject, $message);

        // Email administrátorovi
        if ($contact_support) {
            $admin_subject = 'Nová žádost o kontaktování - Kalkulátor podlahového vytápění';
            $admin_message = "Nová žádost o kontaktování:\n\n";
            $admin_message .= "Email: {$email}\n";
            $admin_message .= "Telefon: {$phone}\n";
            $admin_message .= "Celková cena: " . number_format($total_cost, 2, ',', ' ') . " Kč\n\n";
            $admin_message .= "Detaily kalkulace:\n";

            foreach ($calculation_details as $detail) {
                $admin_message .= "Podlaží {$detail['floor']}: {$detail['area']} m² = " . number_format($detail['cost'], 2, ',', ' ') . " Kč\n";
            }

            wp_mail($settings['admin_email'], $admin_subject, $admin_message);
        }

        wp_send_json_success(array('message' => 'Email byl úspěšně odeslán!'));
    }
}

// Inicializace pluginu
new PodlahoveVytapeniKalkulator();