<?php

/**
 * Plugin Name: Kalkulátor podlahového vytápění
 * Plugin URI: https://allimedia.cz/
 * Description: Plugin pro výpočet nákladů na realizaci podlahového vytápění s administračním rozhraním.
 * Version: 1.5.9
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
define('PV_VERSION', '1.0.0');

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
    }

    public function init()
    {
        load_plugin_textdomain('podlahove-vytapeni', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // AJAX akce
        add_action('wp_ajax_calculate_heating', array($this, 'ajax_calculate_heating'));
        add_action('wp_ajax_nopriv_calculate_heating', array($this, 'ajax_calculate_heating'));
        add_action('wp_ajax_send_calculation_email', array($this, 'ajax_send_calculation_email'));
        add_action('wp_ajax_nopriv_send_calculation_email', array($this, 'ajax_send_calculation_email'));
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
            'button_color' => '#00a32a'
        );

        add_option('pv_settings', $default_settings);
    }

    public function deactivate()
    {
        // Cleanup pokud je potřeba
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
            'hover_background' => sanitize_hex_color($_POST['hover_background'])
        );

        update_option('pv_settings', $settings);
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>Nastavení bylo úspěšně uloženo!</p></div>';
        });
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
