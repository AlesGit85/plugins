// Administrace kalkulátoru podlahového vytápění s podporou fontů

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initAdmin();
        initFontHandling();
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
            updateFontPreview($(this).val());
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
        updateFontPreview($('#selected_font').val());
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
        const preview = $('#font-preview p');
        const fontName = file.name.replace(/\.[^/.]+$/, ""); // Odstranit příponu
        
        // Vytvořit dočasný font face pro preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const fontData = e.target.result;
            const fontFace = new FontFace('temp-preview-font', `url(${fontData})`);
            
            fontFace.load().then(function(loadedFont) {
                document.fonts.add(loadedFont);
                preview.css('font-family', 'temp-preview-font, sans-serif');
                preview.text('Náhled fontu ' + fontName + ': Kalkulátor podlahového vytápění 1234567890');
                
                // Přidat informaci o uploadu
                preview.append('<br><small style="color: #46b450;">✓ Font připraven k nahrání</small>');
            }).catch(function() {
                preview.append('<br><small style="color: #dc3232;">⚠ Náhled fontu se nepodařilo načíst</small>');
            });
        };
        reader.readAsDataURL(file);
    }
    
    function updateFontPreview(selectedFont) {
        const preview = $('#font-preview p');
        
        if (selectedFont === 'default' || !selectedFont) {
            preview.css('font-family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif');
            preview.text('Náhled fontu: Kalkulátor podlahového vytápění 1234567890');
        } else {
            // Najít font v nahraných fontech
            const fontOption = $('#selected_font option[value="' + selectedFont + '"]');
            const fontName = fontOption.text();
            
            preview.css('font-family', 'pv-custom-' + selectedFont + ', sans-serif');
            preview.text('Náhled fontu ' + fontName + ': Kalkulátor podlahového vytápění 1234567890');
        }
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
        
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 5000);
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
                errors.push(input.closest('tr').find('label').text() + ': Neplatná hodnota');
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
            'select[name="selected_font"]'
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
    
    // Export hodnot pro testování
    window.PVAdmin = {
        validateForm: validateAdminForm,
        updatePreview: updatePreview,
        updateFontPreview: updateFontPreview,
        deleteFontFile: deleteFontFile,
        isValidHexColor: isValidHexColor
    };
    
})(jQuery);

// CSS pro font handling
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
        
        .pv-admin-container .form-table tr:hover {
            background: #fafafa;
        }
        
        .pv-uploaded-fonts {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .pv-font-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .pv-font-item:last-child {
            margin-bottom: 0;
        }
        
        .pv-font-name {
            font-weight: 600;
            color: #333;
        }
        
        .pv-delete-font {
            background: #dc3232;
            border-color: #dc3232;
            color: white;
        }
        
        .pv-delete-font:hover {
            background: #c32626;
            border-color: #c32626;
        }
        
        .pv-font-preview {
            margin-top: 15px;
            padding: 15px;
            background: #f0f8ff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .pv-font-preview p {
            margin: 0;
            font-size: 16px;
            line-height: 1.5;
            word-break: break-word;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .pv-admin-container .auto-saved {
            animation: pulse 0.5s ease-in-out;
        }
        
        /* Font upload styling */
        #custom_font_upload {
            padding: 8px;
            border: 2px dashed #ddd;
            background: #fafafa;
            border-radius: 4px;
            width: 100%;
            max-width: 400px;
        }
        
        #custom_font_upload:hover {
            border-color: #0073aa;
            background: #f0f8ff;
        }
        
        /* Responsive font preview */
        @media (max-width: 768px) {
            .pv-font-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .pv-delete-font {
                align-self: flex-end;
            }
        }
    `;
    document.head.appendChild(style);
});