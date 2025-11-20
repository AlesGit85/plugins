// Administrace kalkulátoru podlahového vytápění s rozšířenou podporou fontů

(function($) {
    'use strict';
    
    let currentFont = 'default';
    
    $(document).ready(function() {
        initAdmin();
        initFontHandling();
        initLiveFontPreview();
    });
    
    function initAdmin() {
        // Inicializace color pickeru
        if ($.fn.wpColorPicker) {
            $('.pv-color-picker').wpColorPicker({
                defaultColor: false,
                change: function(event, ui) {
                    updatePreview();
                },
                clear: function() {
                    updatePreview();
                }
            });
        }
        
        // Validace formuláře před odesláním
        $('form').on('submit', validateAdminForm);
        
        // Auto-save draft pro některá pole
        setupAutoSave();
        
        // Tooltips pro pole s popiskem
        setupTooltips();
        
        // Preview změn
        setupPreview();
    }
    
    function initFontHandling() {
        // Font preview při změně selectu
        $('#selected_font').on('change', function() {
            const selectedFont = $(this).val();
            currentFont = selectedFont;
            updateFontPreview(selectedFont);
        });
        
        // Mazání fontů
        $(document).on('click', '.pv-delete-font', function() {
            const button = $(this);
            const fontKey = button.data('font-key');
            const fontName = button.siblings('.pv-font-name').text();
            
            if (confirm('Opravdu chcete smazat font "' + fontName + '"?')) {
                deleteFontFile(fontKey, button);
            }
        });
        
        // File upload validation
        $('#custom_font_upload').on('change', function() {
            validateFontFile(this);
        });
        
        // Počáteční font preview
        currentFont = $('#selected_font').val();
        updateFontPreview(currentFont);
    }
    
    function initLiveFontPreview() {
        // Live preview pro velikosti a váhy fontů
        const fontControls = [
            '#heading_font_size', '#heading_font_weight',
            '#label_font_size', '#label_font_weight', 
            '#button_font_size', '#button_font_weight'
        ];
        
        $(fontControls.join(', ')).on('input change', function() {
            updateLiveFontPreview();
        });
        
        // Počáteční aplikace
        updateLiveFontPreview();
    }
    
    function updateLiveFontPreview() {
        const settings = {
            heading: {
                size: $('#heading_font_size').val() || '20',
                weight: $('#heading_font_weight').val() || '600'
            },
            label: {
                size: $('#label_font_size').val() || '14',
                weight: $('#label_font_weight').val() || '600'  
            },
            button: {
                size: $('#button_font_size').val() || '16',
                weight: $('#button_font_weight').val() || '600'
            }
        };
        
        // Aplikovat styly na preview elementy
        $('.pv-preview-heading').css({
            'font-size': settings.heading.size + 'px',
            'font-weight': settings.heading.weight
        });
        
        $('.pv-preview-label').css({
            'font-size': settings.label.size + 'px',
            'font-weight': settings.label.weight
        });
        
        $('.pv-preview-button').css({
            'font-size': settings.button.size + 'px',
            'font-weight': settings.button.weight
        });
        
        // Animace změny
        $('.pv-preview-section').addClass('font-changing');
        setTimeout(function() {
            $('.pv-preview-section').removeClass('font-changing');
        }, 400);
    }
    
    function validateFontFile(input) {
        const file = input.files[0];
        if (!file) return;
        
        // Kontrola typu souboru
        const allowedTypes = ['woff', 'woff2', 'ttf', 'otf'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(fileExtension)) {
            alert('Nepodporovaný formát fontu. Použijte WOFF, WOFF2, TTF nebo OTF.');
            input.value = '';
            return false;
        }
        
        // Kontrola velikosti (2MB limit)
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (file.size > maxSize) {
            alert('Font je příliš velký. Maximální velikost je 2MB.');
            input.value = '';
            return false;
        }
        
        // Zobrazit náhled
        showFontUploadPreview(file);
        return true;
    }
    
    function showFontUploadPreview(file) {
        const preview = $('.pv-preview-section');
        const fontName = file.name.replace(/\.[^/.]+$/, ""); // Odstranit příponu
        
        // Vytvoř notification
        const notification = $('<div class="font-upload-notification">📁 Font "' + fontName + '" připraven k nahrání</div>');
        notification.css({
            'background': '#e8f5e8',
            'color': '#2e7d32',
            'padding': '8px 12px',
            'border-radius': '4px',
            'margin': '10px 0',
            'font-size': '13px',
            'border': '1px solid #81c784'
        });
        
        $('.pv-font-preview').prepend(notification);
        
        // Pokus o live preview fontu
        const reader = new FileReader();
        reader.onload = function(e) {
            const fontData = e.target.result;
            const fontFace = new FontFace('temp-preview-font', `url(${fontData})`);
            
            fontFace.load().then(function(loadedFont) {
                document.fonts.add(loadedFont);
                applyTempFont('temp-preview-font');
                
                notification.html('✓ Font "' + fontName + '" načten pro náhled').css({
                    'background': '#e3f2fd',
                    'color': '#1565c0',
                    'border-color': '#64b5f6'
                });
                
                setTimeout(() => notification.fadeOut(function() { $(this).remove(); }), 3000);
            }).catch(function() {
                notification.html('⚠ Náhled fontu se nepodařilo načíst').css({
                    'background': '#ffebee',
                    'color': '#c62828',
                    'border-color': '#ef5350'
                });
                
                setTimeout(() => notification.fadeOut(function() { $(this).remove(); }), 3000);
            });
        };
        reader.readAsDataURL(file);
    }
    
    function applyTempFont(fontFamily) {
        $('.pv-preview-section *').css('font-family', fontFamily + ', sans-serif');
    }
    
    function updateFontPreview(selectedFont) {
        let fontFamily;
        
        if (selectedFont === 'default' || !selectedFont) {
            fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        } else {
            fontFamily = 'pv-custom-' + selectedFont + ', sans-serif';
        }
        
        // Aplikovat font na všechny preview elementy
        $('.pv-preview-section *').css('font-family', fontFamily);
        
        // Update info o aktivním fontu
        const fontOption = $('#selected_font option[value="' + selectedFont + '"]');
        const fontName = fontOption.text();
        
        // Vytvořit/aktualizovat font info
        let fontInfo = $('.pv-font-info');
        if (fontInfo.length === 0) {
            fontInfo = $('<div class="pv-font-info"></div>');
            $('.pv-preview-note').before(fontInfo);
        }
        
        fontInfo.html('<strong>Aktivní font:</strong> ' + fontName).css({
            'padding': '10px',
            'background': '#f0f8ff',
            'border': '1px solid #b3d9ff',
            'border-radius': '4px',
            'margin-bottom': '15px',
            'font-size': '13px',
            'color': '#333'
        });
    }
    
    function deleteFontFile(fontKey, button) {
        // Zobrazit loading
        button.prop('disabled', true).text('Mažu...');
        
        $.ajax({
            url: pv_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pv_delete_font',
                nonce: pv_admin_ajax.nonce,
                font_key: fontKey
            },
            success: function(response) {
                if (response.success) {
                    // Odebrat řádek z DOM
                    button.closest('.pv-font-item').fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Odebrat z select boxu
                    $('#selected_font option[value="' + fontKey + '"]').remove();
                    
                    // Pokud byl smazaný font vybraný, přepnout na default
                    if ($('#selected_font').val() === fontKey) {
                        $('#selected_font').val('default').trigger('change');
                    }
                    
                    // Zobrazit success zprávu
                    showAdminNotice('Font byl úspěšně odstraněn.', 'success');
                } else {
                    showAdminNotice('Chyba při mazání fontu: ' + response.data, 'error');
                    button.prop('disabled', false).text('Smazat');
                }
            },
            error: function() {
                showAdminNotice('Chyba při komunikaci se serverem.', 'error');
                button.prop('disabled', false).text('Smazat');
            }
        });
    }
    
    function showAdminNotice(message, type = 'info') {
        const noticeClass = 'notice notice-' + type + ' is-dismissible';
        const notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');
        
        $('.pv-admin-container').prepend(notice);
        
        // Auto dismiss
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 5000);
        
        // Manual dismiss
        notice.on('click', '.notice-dismiss', function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        });
    }
    
    function validateAdminForm(e) {
        let hasErrors = false;
        const errors = [];
        
        // Validace číselných polí
        $('input[type="number"]').each(function() {
            const input = $(this);
            const value = parseFloat(input.val());
            const min = parseFloat(input.attr('min')) || 0;
            const max = parseFloat(input.attr('max')) || Infinity;
            
            if (isNaN(value) || value < min || value > max) {
                hasErrors = true;
                input.addClass('error');
                
                const label = input.closest('tr').find('label').first().text() ||
                             input.closest('.pv-font-control').find('label').text();
                errors.push(label + ': Neplatná hodnota');
            } else {
                input.removeClass('error');
            }
        });
        
        // Validace emailu
        const emailInput = $('input[type="email"]');
        if (emailInput.length && emailInput.val()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.val())) {
                hasErrors = true;
                emailInput.addClass('error');
                errors.push('Email: Neplatný formát emailové adresy');
            } else {
                emailInput.removeClass('error');
            }
        }
        
        // Validace barev
        $('.pv-color-picker').each(function() {
            const input = $(this);
            const value = input.val();
            
            if (value && !isValidHexColor(value)) {
                hasErrors = true;
                input.addClass('error');
                errors.push(input.closest('tr').find('label').text() + ': Neplatná hex barva');
            } else {
                input.removeClass('error');
            }
        });
        
        // Validace font uploadu
        const fontUpload = $('#custom_font_upload')[0];
        if (fontUpload.files.length > 0) {
            if (!validateFontFile(fontUpload)) {
                hasErrors = true;
                errors.push('Font: Neplatný soubor');
            }
        }
        
        // Validace font velikostí
        const fontSizeInputs = $('input[name$="_font_size"]');
        fontSizeInputs.each(function() {
            const input = $(this);
            const value = parseInt(input.val());
            
            if (value && (value < 8 || value > 72)) {
                hasErrors = true;
                input.addClass('error');
                errors.push('Velikost fontu: Musí být mezi 8-72px');
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            alert('Opravte prosím následující chyby:\n\n' + errors.join('\n'));
            
            // Scroll k první chybě
            const firstError = $('.error').first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 300);
                firstError.focus();
            }
        }
    }
    
    function setupAutoSave() {
        // Auto-save pro některá důležitá pole při změně
        const autoSaveFields = [
            'input[name="admin_email"]',
            'input[name="company_name"]',
            'input[name="max_floors"]',
            'select[name="selected_font"]',
            'input[name$="_font_size"]',
            'select[name$="_font_weight"]'
        ];
        
        $(autoSaveFields.join(', ')).on('blur change', function() {
            const field = $(this);
            field.addClass('auto-saved');
            setTimeout(() => field.removeClass('auto-saved'), 2000);
        });
    }
    
    function setupTooltips() {
        // Přidání tooltipů k popisným textům
        $('.description').each(function() {
            const desc = $(this);
            const input = desc.closest('td').find('input, select').first();
            
            if (input.length) {
                input.attr('title', desc.text());
            }
        });
        
        // Tooltips pro font controls
        $('.pv-font-control label').each(function() {
            const label = $(this);
            const input = label.siblings('input, select');
            
            if (input.length) {
                input.attr('title', label.text() + ' - aktuální hodnota: ' + input.val());
            }
        });
    }
    
    function setupPreview() {
        // Live preview změn barev
        const styleElement = $('<style id="pv-admin-preview"></style>');
        $('head').append(styleElement);
        
        $('.pv-color-picker').on('change', updatePreview);
        
        // Počáteční preview
        updatePreview();
    }
    
    function updatePreview() {
        const primaryColor = $('input[name="primary_color"]').val() || '#0073aa';
        const buttonColor = $('input[name="button_color"]').val() || '#00a32a';
        
        const previewCSS = `
            .pv-admin-preview {
                border-left: 4px solid ${primaryColor};
                padding-left: 1rem;
                margin: 1rem 0;
            }
            .pv-admin-preview h4 {
                color: ${primaryColor};
            }
            .pv-admin-preview .button {
                background: ${buttonColor} !important;
                border-color: ${buttonColor} !important;
            }
            .pv-preview-button.button-primary {
                background: ${buttonColor} !important;
                border-color: ${buttonColor} !important;
            }
        `;
        
        $('#pv-admin-preview').text(previewCSS);
        
        // Přidání preview boxu pokud neexistuje
        if (!$('.pv-admin-preview').length) {
            const previewBox = $(`
                <div class="pv-admin-preview">
                    <h4>Náhled barev</h4>
                    <p>Toto je ukázka, jak budou vypadat barvy v kalkulačce.</p>
                    <button type="button" class="button">Ukázkové tlačítko</button>
                </div>
            `);
            
            $('.pv-settings-section').last().after(previewBox);
        }
    }
    
    function isValidHexColor(color) {
        const hexRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
        return hexRegex.test(color);
    }
    
    // Utility funkce pro font weights
    function getFontWeightName(weight) {
        const weights = {
            '300': 'Tenký',
            '400': 'Normální', 
            '500': 'Středně silný',
            '600': 'Polosilný',
            '700': 'Silný',
            '800': 'Extra silný'
        };
        return weights[weight] || 'Neznámý';
    }
    
    // Debug function
    function debugFontSettings() {
        const settings = {
            selectedFont: $('#selected_font').val(),
            heading: {
                size: $('#heading_font_size').val(),
                weight: $('#heading_font_weight').val()
            },
            label: {
                size: $('#label_font_size').val(), 
                weight: $('#label_font_weight').val()
            },
            button: {
                size: $('#button_font_size').val(),
                weight: $('#button_font_weight').val()
            }
        };
        
        console.log('Font Settings:', settings);
        return settings;
    }
    
    // Export hodnot pro testování
    window.PVAdmin = {
        validateForm: validateAdminForm,
        updatePreview: updatePreview,
        updateFontPreview: updateFontPreview,
        updateLiveFontPreview: updateLiveFontPreview,
        deleteFontFile: deleteFontFile,
        isValidHexColor: isValidHexColor,
        debugFontSettings: debugFontSettings,
        showAdminNotice: showAdminNotice
    };
    
})(jQuery);

// CSS pro rozšířené font handling
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .pv-admin-container input.error {
            border-color: #dc3232 !important;
            box-shadow: 0 0 0 1px #dc3232;
        }
        
        .pv-admin-container input.auto-saved {
            border-color: #46b450 !important;
            box-shadow: 0 0 0 1px #46b450;
        }
        
        .font-upload-notification {
            animation: slideInNotification 0.3s ease-out;
        }
        
        @keyframes slideInNotification {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .pv-font-info {
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Live preview hover effects */
        .pv-preview-section [data-element]:hover {
            background: rgba(0, 115, 170, 0.1);
            border-radius: 4px;
            padding: 4px;
            margin: -4px;
            transition: all 0.2s ease;
        }
    `;
    document.head.appendChild(style);
});