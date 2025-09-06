<?php
/**
 * Ukázky použití a přizpůsobení pluginu Kalkulátor podlahového vytápění
 * 
 * UPOZORNĚNÍ: Tento soubor slouží pouze jako dokumentace a příklady.
 * Nevkládejte ho do produkčního webu - je určen pouze pro vývojáře.
 */

// Zabránit přímému spuštění
if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// 1. ZÁKLADNÍ POUŽITÍ SHORTCODU
// ============================================================================

/*
Nejjednodušší použití na stránce nebo v příspěvku:
[podlahove_vytapeni_kalkulator]

S vlastním nadpisem:
[podlahove_vytapeni_kalkulator title="Výpočet nákladů na podlahové vytápění"]
*/

// ============================================================================
// 2. PROGRAMOVÉ ZOBRAZENÍ V ŠABLONĚ
// ============================================================================

function display_calculator_in_template() {
    // V šabloně WordPress (např. page.php)
    echo do_shortcode('[podlahove_vytapeni_kalkulator title="Kalkulace vytápění"]');
}

// ============================================================================
// 3. HOOKS A FILTRY PRO PŘIZPŮSOBENÍ
// ============================================================================

// Úprava výpočtu nákladů pro konkrétní podlaží
add_filter('pv_calculate_floor_cost', 'custom_floor_cost_calculation', 10, 3);
function custom_floor_cost_calculation($cost, $floor_data, $floor_index) {
    // Sleva 10% pro druhé a další podlaží
    if ($floor_index > 0) {
        $cost = $cost * 0.9;
    }
    
    // Příplatek za velkou plochu
    if ($floor_data['area'] > 100) {
        $cost = $cost * 1.05; // +5% za plochu nad 100 m²
    }
    
    return $cost;
}

// Přidání vlastního typu potrubí
add_filter('pv_pipe_types', 'add_custom_pipe_type');
function add_custom_pipe_type($pipe_types) {
    $pipe_types['custom_pipe'] = array(
        'name' => 'Speciální trubka XY',
        'increase' => 20, // 20% příplatek
        'description' => 'Prémiová trubka s vlastnostmi XY'
    );
    
    return $pipe_types;
}

// Úprava emailu pro zákazníka
add_filter('pv_customer_email_content', 'customize_customer_email', 10, 2);
function customize_customer_email($content, $calculation_data) {
    // Přidání vlastní hlavičky
    $custom_header = "🏠 " . get_option('blogname') . " - Kalkulace podlahového vytápění\n\n";
    
    // Přidání vlastní poznámky na konec
    $custom_footer = "\n\n📞 Pro více informací nás kontaktujte na tel.: +420 123 456 789";
    $custom_footer .= "\n🌐 Web: " . home_url();
    $custom_footer .= "\n📧 Email: " . get_option('admin_email');
    
    return $custom_header . $content . $custom_footer;
}

// Přidání vlastních polí do administrace
add_action('pv_admin_settings_after', 'add_custom_admin_fields');
function add_custom_admin_fields($settings) {
    ?>
    <div class="pv-settings-section">
        <h2>Vlastní nastavení</h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="custom_discount">Sleva pro stálé zákazníky (%)</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="custom_discount" 
                               name="custom_discount" 
                               value="<?php echo esc_attr($settings['custom_discount'] ?? '0'); ?>" 
                               step="0.1" min="0" max="50" class="regular-text" />
                        <p class="description">Sleva v % pro označené stálé zákazníky</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

// ============================================================================
// 4. INTEGRACE S EXTERNÍMI SYSTÉMY
// ============================================================================

// Odeslání dat do CRM systému po výpočtu
add_action('pv_calculation_sent', 'send_to_crm_system', 10, 2);
function send_to_crm_system($email_data, $calculation_data) {
    // Příprava dat pro CRM
    $crm_data = array(
        'email' => $email_data['email'],
        'phone' => $email_data['phone'],
        'total_cost' => $calculation_data['total_cost'],
        'floors_count' => count($calculation_data['details']),
        'contact_requested' => $email_data['contact_support'],
        'source' => 'heating_calculator',
        'date' => date('Y-m-d H:i:s')
    );
    
    // Odeslání do CRM (příklad s HTTP API)
    $response = wp_remote_post('https://your-crm-system.com/api/leads', array(
        'body' => json_encode($crm_data),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer YOUR_API_KEY'
        )
    ));
    
    if (is_wp_error($response)) {
        error_log('CRM integration failed: ' . $response->get_error_message());
    }
}

// Integrace s Google Analytics
add_action('pv_calculation_completed', 'track_calculation_in_ga');
function track_calculation_in_ga($calculation_data) {
    // Google Analytics tracking event
    ?>
    <script>
    if (typeof gtag !== 'undefined') {
        gtag('event', 'calculation_completed', {
            'event_category': 'Heating Calculator',
            'event_label': 'Total Cost',
            'value': <?php echo intval($calculation_data['total_cost']); ?>,
            'custom_map': {
                'custom_parameter_1': 'floors_count'
            },
            'floors_count': <?php echo count($calculation_data['details']); ?>
        });
    }
    </script>
    <?php
}

// ============================================================================
// 5. VLASTNÍ VALIDACE A BEZPEČNOST
// ============================================================================

// Přidání vlastní validace před výpočtem
add_filter('pv_validate_calculation_data', 'custom_validation', 10, 2);
function custom_validation($is_valid, $floors_data) {
    foreach ($floors_data as $floor) {
        // Maximální plocha 500 m²
        if ($floor['area'] > 500) {
            wp_send_json_error('Maximální plocha jednoho podlaží je 500 m²');
            return false;
        }
        
        // Minimální plocha 10 m²
        if ($floor['area'] < 10) {
            wp_send_json_error('Minimální plocha jednoho podlaží je 10 m²');
            return false;
        }
    }
    
    return $is_valid;
}

// Rate limiting pro AJAX požadavky
add_action('wp_ajax_calculate_heating', 'rate_limit_calculation', 1);
add_action('wp_ajax_nopriv_calculate_heating', 'rate_limit_calculation', 1);
function rate_limit_calculation() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'pv_calc_limit_' . md5($ip);
    
    $attempts = get_transient($transient_key) ?: 0;
    
    if ($attempts >= 10) { // Max 10 výpočtů za hodinu
        wp_send_json_error('Příliš mnoho požadavků. Zkuste to později.');
    }
    
    set_transient($transient_key, $attempts + 1, HOUR_IN_SECONDS);
}

// ============================================================================
// 6. ROZŠÍŘENÍ O VÍCE TYPŮ VYTÁPĚNÍ
// ============================================================================

// Přidání dalších typů vytápění
add_filter('pv_heating_types', 'add_more_heating_types');
function add_more_heating_types($types) {
    $types['electric_cables'] = array(
        'name' => 'Elektrické topné kabely',
        'price_per_sqm' => 180,
        'description' => 'Tenké topné kabely do anhydritu'
    );
    
    $types['carbon_film'] = array(
        'name' => 'Uhlíkové topné fólie',
        'price_per_sqm' => 220,
        'description' => 'Infrapanely pod plovoucí podlahu'
    );
    
    return $types;
}

// ============================================================================
// 7. REPORTING A STATISTIKY
// ============================================================================

// Uložení statistik kalkulací
add_action('pv_calculation_completed', 'save_calculation_stats');
function save_calculation_stats($calculation_data) {
    $stats = get_option('pv_calculation_stats', array());
    
    $today = date('Y-m-d');
    if (!isset($stats[$today])) {
        $stats[$today] = array(
            'count' => 0,
            'total_value' => 0,
            'avg_floors' => 0
        );
    }
    
    $stats[$today]['count']++;
    $stats[$today]['total_value'] += $calculation_data['total_cost'];
    $stats[$today]['avg_floors'] = ($stats[$today]['avg_floors'] + count($calculation_data['details'])) / $stats[$today]['count'];
    
    // Zachovat pouze posledních 90 dní
    $stats = array_slice($stats, -90, null, true);
    
    update_option('pv_calculation_stats', $stats);
}

// Zobrazení statistik v adminu
add_action('pv_admin_dashboard_widgets', 'add_stats_widget');
function add_stats_widget() {
    $stats = get_option('pv_calculation_stats', array());
    $today_stats = $stats[date('Y-m-d')] ?? array('count' => 0, 'total_value' => 0);
    
    ?>
    <div class="pv-stats-widget">
        <h3>📊 Statistiky za dnes</h3>
        <p><strong>Počet kalkulací:</strong> <?php echo $today_stats['count']; ?></p>
        <p><strong>Celková hodnota:</strong> <?php echo number_format($today_stats['total_value'], 0, ',', ' '); ?> Kč</p>
        <p><strong>Průměrná zakázka:</strong> <?php echo $today_stats['count'] ? number_format($today_stats['total_value'] / $today_stats['count'], 0, ',', ' ') : '0'; ?> Kč</p>
    </div>
    <?php
}

// ============================================================================
// 8. MOBILNÍ OPTIMALIZACE
// ============================================================================

// Detekce mobilního zařízení a úprava rozhraní
add_filter('pv_calculator_config', 'mobile_optimization');
function mobile_optimization($config) {
    if (wp_is_mobile()) {
        // Na mobilu menší počáteční plocha
        $config['default_area'] = 30;
        
        // Jednodušší rozhraní
        $config['simple_mode'] = true;
        
        // Menší maximální počet podlaží
        $config['max_floors'] = min($config['max_floors'], 3);
    }
    
    return $config;
}

// ============================================================================
// 9. A/B TESTOVÁNÍ
// ============================================================================

// Rozdělení uživatelů pro A/B test různých cen
add_filter('pv_price_multiplier', 'ab_test_pricing');
function ab_test_pricing($multiplier) {
    // Rozdělení podle IP nebo session
    $user_hash = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    $test_group = hexdec(substr($user_hash, 0, 1)) % 2; // 0 nebo 1
    
    if ($test_group === 1) {
        // Skupina B: o 5% vyšší ceny
        return $multiplier * 1.05;
    }
    
    return $multiplier; // Skupina A: standardní ceny
}

// ============================================================================
// 10. ZÁLOHA A OBNOVENÍ NASTAVENÍ
// ============================================================================

// Export nastavení do JSON
function export_pv_settings() {
    $settings = get_option('pv_settings');
    $export_data = array(
        'settings' => $settings,
        'export_date' => date('Y-m-d H:i:s'),
        'site_url' => home_url(),
        'plugin_version' => PV_VERSION
    );
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="pv-settings-' . date('Y-m-d') . '.json"');
    echo json_encode($export_data, JSON_PRETTY_PRINT);
    exit;
}

// Import nastavení z JSON
function import_pv_settings($json_data) {
    $import_data = json_decode($json_data, true);
    
    if (!$import_data || !isset($import_data['settings'])) {
        return false;
    }
    
    // Validace před importem
    $settings = $import_data['settings'];
    
    // Aktualizace nastavení
    update_option('pv_settings', $settings);
    
    return true;
}

/**
 * DALŠÍ MOŽNOSTI ROZŠÍŘENÍ:
 * 
 * - Integrace s WooCommerce (vytvoření produktu z kalkulace)
 * - PDF export kalkulace
 * - Kalendářní rezervace konzultací
 * - Víceúrovňové ceny podle regionů
 * - Integration s mapami pro výpočet dopravy
 * - Pokročilé reporty a dashboardy
 * - Multi-site podpora
 * - REST API pro externí aplikace
 * - Webhook notifikace
 * - GDPR compliance nástroje
 */