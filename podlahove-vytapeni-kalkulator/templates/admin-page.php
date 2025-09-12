<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="pv-admin-container">
        <div class="pv-admin-main">
            <form method="post" action="">
                <?php wp_nonce_field('pv_settings_nonce'); ?>

                <div class="pv-settings-section">
                    <h2>Cenové nastavení</h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="tacker_system_price">Tacker systém (Kč/m²)</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="tacker_system_price"
                                        name="tacker_system_price"
                                        value="<?php echo esc_attr($settings['tacker_system_price']); ?>"
                                        step="0.01" min="0" class="regular-text" />
                                    <p class="description">Cena za m² pro tacker systém (instalace na folii)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="system_board_price">Systémová deska (Kč/m²)</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="system_board_price"
                                        name="system_board_price"
                                        value="<?php echo esc_attr($settings['system_board_price']); ?>"
                                        step="0.01" min="0" class="regular-text" />
                                    <p class="description">Cena za m² pro systémovou desku s výstupky</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pv-settings-section">
                    <h2>Příplatky za typ potrubí (%)</h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="pipe_17x2_increase">PE trubka 17x2 (%)</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="pipe_17x2_increase"
                                        name="pipe_17x2_increase"
                                        value="<?php echo esc_attr($settings['pipe_17x2_increase']); ?>"
                                        step="0.1" min="0" max="100" class="regular-text" />
                                    <p class="description">Procentuální navýšení ceny</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pipe_18x2_increase">PE trubka 18x2 (%)</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="pipe_18x2_increase"
                                        name="pipe_18x2_increase"
                                        value="<?php echo esc_attr($settings['pipe_18x2_increase']); ?>"
                                        step="0.1" min="0" max="100" class="regular-text" />
                                    <p class="description">Procentuální navýšení ceny</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pipe_alu_16x2_increase">Plastohliníková trubka 16x2 (%)</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="pipe_alu_16x2_increase"
                                        name="pipe_alu_16x2_increase"
                                        value="<?php echo esc_attr($settings['pipe_alu_16x2_increase']); ?>"
                                        step="0.1" min="0" max="100" class="regular-text" />
                                    <p class="description">Procentuální navýšení ceny</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pipe_alu_18x2_increase">Plastohliníková trubka 18x2 (%)</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="pipe_alu_18x2_increase"
                                        name="pipe_alu_18x2_increase"
                                        value="<?php echo esc_attr($settings['pipe_alu_18x2_increase']); ?>"
                                        step="0.1" min="0" max="100" class="regular-text" />
                                    <p class="description">Procentuální navýšení ceny</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pv-settings-section">
                    <h2>Příplatky za zdroj tepla (Kč)</h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="low_temp_source_price">Nízkoteplotní zdroj</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="low_temp_source_price"
                                        name="low_temp_source_price"
                                        value="<?php echo esc_attr($settings['low_temp_source_price']); ?>"
                                        step="1" min="0" class="regular-text" />
                                    <p class="description">TČ, kondenzační kotel, elektrokotel apod.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="high_temp_source_price">Vysokoteplotní zdroj</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="high_temp_source_price"
                                        name="high_temp_source_price"
                                        value="<?php echo esc_attr($settings['high_temp_source_price']); ?>"
                                        step="1" min="0" class="regular-text" />
                                    <p class="description">Tuhá paliva, akumulační zásobníky apod.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="radiator_combo_price">Kombinace s radiátory</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="radiator_combo_price"
                                        name="radiator_combo_price"
                                        value="<?php echo esc_attr($settings['radiator_combo_price']); ?>"
                                        step="1" min="0" class="regular-text" />
                                    <p class="description">Kombinované řešení s radiátory</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pv-settings-section">
                    <h2>Obecné nastavení</h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="max_floors">Maximální počet podlaží</label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="max_floors"
                                        name="max_floors"
                                        value="<?php echo esc_attr($settings['max_floors']); ?>"
                                        step="1" min="1" max="20" class="regular-text" />
                                    <p class="description">Kolik podlaží může zákazník maximálně přidat (0 = neomezeno)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="admin_email">Email pro notifikace</label>
                                </th>
                                <td>
                                    <input type="email"
                                        id="admin_email"
                                        name="admin_email"
                                        value="<?php echo esc_attr($settings['admin_email']); ?>"
                                        class="regular-text" />
                                    <p class="description">Email, na který budou zasílány žádosti o kontakt</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="company_name">Název společnosti</label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="company_name"
                                        name="company_name"
                                        value="<?php echo esc_attr($settings['company_name']); ?>"
                                        class="regular-text" />
                                    <p class="description">Název společnosti v emailech</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pv-settings-section">
                    <h2>Vzhled kalkulačky</h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="primary_color">Hlavní barva</label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="primary_color"
                                        name="primary_color"
                                        value="<?php echo esc_attr($settings['primary_color']); ?>"
                                        class="pv-color-picker" />
                                    <p class="description">Hlavní barva kalkulačky (nadpisy, rámečky)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="button_color">Barva tlačítek</label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="button_color"
                                        name="button_color"
                                        value="<?php echo esc_attr($settings['button_color']); ?>"
                                        class="pv-color-picker" />
                                    <p class="description">Barva pozadí tlačítek</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="button_text_color">Barva textu tlačítek</label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="button_text_color"
                                        name="button_text_color"
                                        value="<?php echo esc_attr($settings['button_text_color'] ?? '#ffffff'); ?>"
                                        class="pv-color-picker" />
                                    <p class="description">Barva textu na tlačítkách</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="button_hover_color">Barva tlačítek při hover</label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="button_hover_color"
                                        name="button_hover_color"
                                        value="<?php echo esc_attr($settings['button_hover_color'] ?? '#008a2e'); ?>"
                                        class="pv-color-picker" />
                                    <p class="description">Barva pozadí tlačítek při najetí myší</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="button_hover_text_color">Barva textu tlačítek při hover</label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="button_hover_text_color"
                                        name="button_hover_text_color"
                                        value="<?php echo esc_attr($settings['button_hover_text_color'] ?? '#ffffff'); ?>"
                                        class="pv-color-picker" />
                                    <p class="description">Barva textu tlačítek při najetí myší</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="hover_background">Barva pozadí při hover</label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="hover_background"
                                        name="hover_background"
                                        value="<?php echo esc_attr($settings['hover_background'] ?? '#f0f8ff'); ?>"
                                        class="pv-color-picker" />
                                    <p class="description">Barva pozadí prvků při najetí myší</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php submit_button('Uložit nastavení', 'primary', 'submit'); ?>
            </form>
        </div>

        <div class="pv-admin-sidebar">
            <div class="pv-info-box">
                <h3>Jak používat kalkulačku?</h3>
                <p>Pro zobrazení kalkulačky na vašich stránkách použijte shortcode:</p>
                <code>[podlahove_vytapeni_kalkulator]</code>

                <h4>Volitelné parametry:</h4>
                <code>[podlahove_vytapeni_kalkulator title="Vlastní nadpis"]</code>
            </div>

            <div class="pv-info-box">
                <h3>Přehled funkcí</h3>
                <ul>
                    <li>✓ Výpočet nákladů podle typu instalace</li>
                    <li>✓ Různé typy potrubí s příplatky</li>
                    <li>✓ Zohlednění zdroje tepla</li>
                    <li>✓ Možnost více podlaží</li>
                    <li>✓ Sběr kontaktních údajů</li>
                    <li>✓ Automatické odesílání emailů</li>
                    <li>✓ Plně přizpůsobitelný design</li>
                </ul>
            </div>
        </div>
    </div>
</div>