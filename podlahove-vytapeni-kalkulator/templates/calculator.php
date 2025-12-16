<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="pv-calculator-container" style="--primary-color: <?php echo esc_attr($settings['primary_color']); ?>; --button-color: <?php echo esc_attr($settings['button_color']); ?>;">
    <div class="pv-calculator">
        <h2 class="pv-calculator-title"><?php echo esc_html($atts['title']); ?></h2>
        
        <form id="pv-calculator-form">
            <div id="pv-floors-container">
                <div class="pv-floor" data-floor="1">
                    <div class="pv-floor-header">
                        <h3>Podlaží 1</h3>
                        <button type="button" class="pv-remove-floor" style="display: none;">×</button>
                    </div>
                    
                    <div class="pv-form-row">
                        <div class="pv-form-group">
                            <label for="area_1">Plocha (m²)</label>
                            <div class="pv-area-control">
                                <button type="button" class="pv-area-btn pv-area-minus" data-target="area_1">-</button>
                                <input type="number" id="area_1" name="area[]" min="1" step="1" value="50" class="pv-area-input">
                                <button type="button" class="pv-area-btn pv-area-plus" data-target="area_1">+</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pv-form-row">
                        <div class="pv-form-group pv-installation-group">
                            <label>Varianta instalace</label>
                            <div class="pv-installation-options">
                                <label class="pv-radio-option">
                                    <input type="radio" name="installation_1" value="tacker" checked>
                                    <span class="pv-radio-custom"></span>
                                    <div class="pv-radio-content">
                                        <strong>Tacker systém</strong>
                                        <small>Instalace na folii</small>
                                    </div>
                                </label>
                                <label class="pv-radio-option">
                                    <input type="radio" name="installation_1" value="system_board">
                                    <span class="pv-radio-custom"></span>
                                    <div class="pv-radio-content">
                                        <strong>Systémová deska</strong>
                                        <small>S výstupky</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pv-form-row pv-dropdowns-row">
                        <div class="pv-form-group">
                            <label>Typ potrubí</label>
                            <div class="pv-select-wrapper">
                                <select name="pipe_type_1" class="pv-select">
                                    <option value="pe_16x2">16x2 polyethylenová trubka</option>
                                    <option value="pe_17x2">17x2 polyethylenová trubka</option>
                                    <option value="pe_18x2">18x2 polyethylenová trubka</option>
                                    <option value="alu_16x2">16x2 plastohliníková trubka</option>
                                    <option value="alu_18x2">18x2 plastohliníková trubka</option>
                                    <option value="advice">Nevím - nechám si poradit</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="pv-form-group">
                            <label>Zdroj tepla</label>
                            <div class="pv-select-wrapper">
                                <select name="heat_source_1" class="pv-select">
                                    <option value="low_temp">Nízkoteplotní</option>
                                    <option value="high_temp">Vysokoteplotní</option>
                                    <option value="radiator_combo">Kombinace s radiátory</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="pv-add-floor-section">
                <button type="button" id="pv-add-floor" class="pv-btn pv-btn-secondary">
                    + Přidat podlaží
                </button>
            </div>
            
            <div class="pv-calculation-section">
                <button type="button" id="pv-calculate" class="pv-btn pv-btn-primary">
                    Vypočítat cenu
                </button>
                
                <div id="pv-result" class="pv-result" style="display: none;">
                    <div class="pv-result-header">
                        <h3>Orientační cena systému</h3>
                    </div>
                    
                    <div id="pv-result-details" class="pv-result-details"></div>
                    
                    <div class="pv-result-total">
                        <span class="pv-total-label">Celková cena:</span>
                        <span id="pv-total-price" class="pv-total-price">0 Kč</span>
                    </div>
                </div>
            </div>
            
            <div id="pv-contact-section" class="pv-contact-section" style="display: none;">
                <h3>Kontaktní údaje</h3>
                <p>Pro zaslání detailního výpisu a konzultace s technickou podporou vyplňte prosím kontaktní údaje:</p>
                
                <div class="pv-form-row">
                    <div class="pv-form-group">
                        <label for="pv-email">Email *</label>
                        <input type="email" id="pv-email" name="email" required class="pv-input">
                    </div>
                    <div class="pv-form-group">
                        <label for="pv-phone">Telefon</label>
                        <input type="tel" id="pv-phone" name="phone" class="pv-input">
                    </div>
                </div>
                
                <div class="pv-form-row">
                    <div class="pv-form-group pv-checkbox-group">
                        <label class="pv-checkbox-option">
                            <input type="checkbox" id="pv-contact-support" name="contact_support">
                            <span class="pv-checkbox-custom"></span>
                            <span>Mám zájem o kontaktování technické podpory pro upřesnění a zaslání detailního výpisu prvků pro můj projekt</span>
                        </label>
                    </div>
                </div>
                
                <div class="pv-form-row">
                    <small class="pv-disclaimer">
                        Veškeré uvedené údaje slouží pouze pro interní účely „kalkulačky"
                    </small>
                </div>
                
                <button type="button" id="pv-send-calculation" class="pv-btn pv-btn-primary">
                    Odeslat výpočet
                </button>
            </div>
        </form>
        
        <div id="pv-loading" class="pv-loading" style="display: none;">
            <div class="pv-loader"></div>
            <p>Počítám...</p>
        </div>
        
        <div id="pv-success-message" class="pv-success-message" style="display: none;">
            <div class="pv-success-icon">✓</div>
            <h3>Email byl úspěšně odeslán!</h3>
            <p>Na váš email jsme zaslali detailní výpočet nákladů. V případě zájmu o konzultaci vás budeme kontaktovat.</p>
        </div>
    </div>
</div>

<script type="text/template" id="pv-floor-template">
    <div class="pv-floor" data-floor="{{floor_number}}">
        <div class="pv-floor-header">
            <h3>Podlaží {{floor_number}}</h3>
            <button type="button" class="pv-remove-floor">×</button>
        </div>
        
        <div class="pv-form-row">
            <div class="pv-form-group">
                <label for="area_{{floor_number}}">Plocha (m²)</label>
                <div class="pv-area-control">
                    <button type="button" class="pv-area-btn pv-area-minus" data-target="area_{{floor_number}}">-</button>
                    <input type="number" id="area_{{floor_number}}" name="area[]" min="1" step="1" value="50" class="pv-area-input">
                    <button type="button" class="pv-area-btn pv-area-plus" data-target="area_{{floor_number}}">+</button>
                </div>
            </div>
        </div>
        
        <div class="pv-form-row">
            <div class="pv-form-group pv-installation-group">
                <label>Varianta instalace</label>
                <div class="pv-installation-options">
                    <label class="pv-radio-option">
                        <input type="radio" name="installation_{{floor_number}}" value="tacker" checked>
                        <span class="pv-radio-custom"></span>
                        <div class="pv-radio-content">
                            <strong>Tacker systém</strong>
                            <small>Instalace na folii</small>
                        </div>
                    </label>
                    <label class="pv-radio-option">
                        <input type="radio" name="installation_{{floor_number}}" value="system_board">
                        <span class="pv-radio-custom"></span>
                        <div class="pv-radio-content">
                            <strong>Systémová deska</strong>
                            <small>S výstupky</small>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="pv-form-row pv-dropdowns-row">
            <div class="pv-form-group">
                <label for="pipe_type_{{floor_number}}">Typ potrubí</label>
                <div class="pv-select-wrapper">
                    <select name="pipe_type_{{floor_number}}" id="pipe_type_{{floor_number}}" class="pv-select">
                        <option value="pe_16x2">16x2 polyethylenová trubka</option>
                        <option value="pe_17x2">17x2 polyethylenová trubka</option>
                        <option value="pe_18x2">18x2 polyethylenová trubka</option>
                        <option value="alu_16x2">16x2 plastohliníková trubka</option>
                        <option value="alu_18x2">18x2 plastohliníková trubka</option>
                        <option value="advice">Nevím - nechám si poradit</option>
                    </select>
                </div>
            </div>
            
            <div class="pv-form-group">
                <label for="heat_source_{{floor_number}}">Zdroj tepla</label>
                <div class="pv-select-wrapper">
                    <select name="heat_source_{{floor_number}}" id="heat_source_{{floor_number}}" class="pv-select">
                        <option value="">Použije se dle 1. podlaží</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</script>