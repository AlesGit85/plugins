<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="pv-admin-container">
        <div class="pv-admin-main">
            <form method="post" action="" enctype="multipart/form-data">
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
                            <tr>
                                <th scope="row">
                                    <label for="decimal_places">Počet desetinných míst</label>
                                </th>
                                <td>
                                    <select id="decimal_places" name="decimal_places" class="regular-text">
                                        <option value="0" <?php selected($settings['decimal_places'] ?? '0', '0'); ?>>Celá čísla (123 456 Kč)</option>
                                        <option value="1" <?php selected($settings['decimal_places'] ?? '0', '1'); ?>>1 desetinné místo (123 456,5 Kč)</option>
                                        <option value="2" <?php selected($settings['decimal_places'] ?? '0', '2'); ?>>2 desetinná místa (123 456,50 Kč)</option>
                                    </select>
                                    <p class="description">Jak zobrazovat ceny v kalkulačce</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pv-settings-section">
                    <h2>Vlastní fonty</h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="custom_font_upload">Nahrát vlastní font</label>
                                </th>
                                <td>
                                    <input type="file" 
                                           id="custom_font_upload" 
                                           name="custom_font_upload" 
                                           accept=".woff,.woff2,.ttf,.otf"
                                           class="regular-text" />
                                    <p class="description">Podporované formáty: WOFF, WOFF2, TTF, OTF (doporučeno WOFF2)</p>
                                    
                                    <?php if (!empty($settings['uploaded_fonts'])): ?>
                                        <h4>Nahrané fonty:</h4>
                                        <div class="pv-uploaded-fonts">
                                            <?php foreach ($settings['uploaded_fonts'] as $font_key => $font_info): ?>
                                                <div class="pv-font-item">
                                                    <span class="pv-font-name"><?php echo esc_html($font_info['name']); ?></span>
                                                    <button type="button" 
                                                            class="button button-small pv-delete-font" 
                                                            data-font-key="<?php echo esc_attr($font_key); ?>">
                                                        Smazat
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="selected_font">Aktivní font</label>
                                </th>
                                <td>
                                    <select id="selected_font" name="selected_font" class="regular-text">
                                        <option value="default" <?php selected($settings['selected_font'] ?? 'default', 'default'); ?>>
                                            Výchozí (systémový)
                                        </option>
                                        <?php if (!empty($settings['uploaded_fonts'])): ?>
                                            <?php foreach ($settings['uploaded_fonts'] as $font_key => $font_info): ?>
                                                <option value="<?php echo esc_attr($font_key); ?>" 
                                                        <?php selected($settings['selected_font'] ?? 'default', $font_key); ?>>
                                                    <?php echo esc_html($font_info['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <p class="description">Vyberte font, který se použije v kalkulačce</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pv-settings-section">
                    <h2>Velikosti a váhy fontů</h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label>Nadpisy podlaží</label>
                                </th>
                                <td>
                                    <div class="pv-font-controls">
                                        <div class="pv-font-control">
                                            <label for="heading_font_size">Velikost (px)</label>
                                            <input type="number"
                                                   id="heading_font_size"
                                                   name="heading_font_size"
                                                   value="<?php echo esc_attr($settings['heading_font_size'] ?? '20'); ?>"
                                                   step="1" min="10" max="48" class="small-text" />
                                        </div>
                                        <div class="pv-font-control">
                                            <label for="heading_font_weight">Váha</label>
                                            <select id="heading_font_weight" name="heading_font_weight" class="regular-text">
                                                <option value="300" <?php selected($settings['heading_font_weight'] ?? '600', '300'); ?>>Tenký (300)</option>
                                                <option value="400" <?php selected($settings['heading_font_weight'] ?? '600', '400'); ?>>Normální (400)</option>
                                                <option value="500" <?php selected($settings['heading_font_weight'] ?? '600', '500'); ?>>Středně silný (500)</option>
                                                <option value="600" <?php selected($settings['heading_font_weight'] ?? '600', '600'); ?>>Polosilný (600)</option>
                                                <option value="700" <?php selected($settings['heading_font_weight'] ?? '600', '700'); ?>>Silný (700)</option>
                                                <option value="800" <?php selected($settings['heading_font_weight'] ?? '600', '800'); ?>>Extra silný (800)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <p class="description">"Podlaží 1", "Podlaží 2", atd.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label>Popisky polí</label>
                                </th>
                                <td>
                                    <div class="pv-font-controls">
                                        <div class="pv-font-control">
                                            <label for="label_font_size">Velikost (px)</label>
                                            <input type="number"
                                                   id="label_font_size"
                                                   name="label_font_size"
                                                   value="<?php echo esc_attr($settings['label_font_size'] ?? '14'); ?>"
                                                   step="1" min="10" max="24" class="small-text" />
                                        </div>
                                        <div class="pv-font-control">
                                            <label for="label_font_weight">Váha</label>
                                            <select id="label_font_weight" name="label_font_weight" class="regular-text">
                                                <option value="300" <?php selected($settings['label_font_weight'] ?? '600', '300'); ?>>Tenký (300)</option>
                                                <option value="400" <?php selected($settings['label_font_weight'] ?? '600', '400'); ?>>Normální (400)</option>
                                                <option value="500" <?php selected($settings['label_font_weight'] ?? '600', '500'); ?>>Středně silný (500)</option>
                                                <option value="600" <?php selected($settings['label_font_weight'] ?? '600', '600'); ?>>Polosilný (600)</option>
                                                <option value="700" <?php selected($settings['label_font_weight'] ?? '600', '700'); ?>>Silný (700)</option>
                                                <option value="800" <?php selected($settings['label_font_weight'] ?? '600', '800'); ?>>Extra silný (800)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <p class="description">"Plocha (m²)", "Varianta instalace", "Typ potrubí", "Zdroj tepla"</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label>Tlačítka</label>
                                </th>
                                <td>
                                    <div class="pv-font-controls">
                                        <div class="pv-font-control">
                                            <label for="button_font_size">Velikost (px)</label>
                                            <input type="number"
                                                   id="button_font_size"
                                                   name="button_font_size"
                                                   value="<?php echo esc_attr($settings['button_font_size'] ?? '16'); ?>"
                                                   step="1" min="10" max="24" class="small-text" />
                                        </div>
                                        <div class="pv-font-control">
                                            <label for="button_font_weight">Váha</label>
                                            <select id="button_font_weight" name="button_font_weight" class="regular-text">
                                                <option value="300" <?php selected($settings['button_font_weight'] ?? '600', '300'); ?>>Tenký (300)</option>
                                                <option value="400" <?php selected($settings['button_font_weight'] ?? '600', '400'); ?>>Normální (400)</option>
                                                <option value="500" <?php selected($settings['button_font_weight'] ?? '600', '500'); ?>>Středně silný (500)</option>
                                                <option value="600" <?php selected($settings['button_font_weight'] ?? '600', '600'); ?>>Polosilný (600)</option>
                                                <option value="700" <?php selected($settings['button_font_weight'] ?? '600', '700'); ?>>Silný (700)</option>
                                                <option value="800" <?php selected($settings['button_font_weight'] ?? '600', '800'); ?>>Extra silný (800)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <p class="description">"+ Přidat podlaží", "Vypočítat cenu", "Odeslat výpočet"</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pv-settings-section">
                    <h2>Náhled fontů</h2>
                    <div id="font-preview" class="pv-font-preview">
                        <div class="pv-preview-section">
                            <h3 class="pv-preview-heading" data-element="heading">Podlaží 1</h3>
                            <div class="pv-preview-labels">
                                <label class="pv-preview-label" data-element="label">Plocha (m²)</label>
                                <label class="pv-preview-label" data-element="label">Varianta instalace</label>
                                <label class="pv-preview-label" data-element="label">Typ potrubí</label>
                                <label class="pv-preview-label" data-element="label">Zdroj tepla</label>
                            </div>
                            <div class="pv-preview-buttons">
                                <button type="button" class="pv-preview-button button" data-element="button">+ Přidat podlaží</button>
                                <button type="button" class="pv-preview-button button button-primary" data-element="button">Vypočítat cenu</button>
                            </div>
                        </div>
                        <p class="pv-preview-note">
                            <strong>Live náhled:</strong> Změny se projeví okamžitě při úpravě hodnot výše.
                        </p>
                    </div>
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
                    <li>✓ Vlastní fonty s detailním nastavením</li>
                </ul>
            </div>

            <div class="pv-info-box">
                <h3>📝 Tipy pro fonty</h3>
                <ul>
                    <li><strong>WOFF2</strong> - nejlepší komprese a podpora</li>
                    <li><strong>WOFF</strong> - starší podpora prohlížečů</li>
                    <li><strong>TTF/OTF</strong> - původní formáty</li>
                </ul>
                <p>Doporučená velikost fontů: maximálně 200KB pro rychlé načítání.</p>
                
                <h4>📐 Velikosti fontů</h4>
                <ul>
                    <li><strong>Nadpisy:</strong> 16-24px pro čitelnost</li>
                    <li><strong>Popisky:</strong> 12-16px pro jasnost</li>
                    <li><strong>Tlačítka:</strong> 14-18px pro akčnost</li>
                </ul>
            </div>
        </div>
    </div>
</div>