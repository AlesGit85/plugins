// Administrace kalkulátoru podlahového vytápění

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initAdmin();
    });
    
    function initAdmin() {
        // Inicializace color pickeru
        if ($.fn.wpColorPicker) {
            $('.pv-color-picker').wpColorPicker({
                defaultColor: false,
                change: function(event, ui) {
                    // Můžeme přidat real-time preview zde
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
            'input[name="max_floors"]'
        ];
        
        $(autoSaveFields.join(', ')).on('blur', function() {
            const field = $(this);
            // Zde by šlo implementovat AJAX auto-save
            // Pro teď jen vizuální indikace
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
    
    // Přidání helper funkcí pro lepší UX
    function showSaveConfirmation() {
        const confirmation = $('<div class="notice notice-success is-dismissible"><p>Nastavení bylo uloženo!</p></div>');
        $('.pv-admin-container').prepend(confirmation);
        
        setTimeout(() => {
            confirmation.fadeOut(() => confirmation.remove());
        }, 3000);
    }
    
    // Export hodnot pro testování
    window.PVAdmin = {
        validateForm: validateAdminForm,
        updatePreview: updatePreview,
        isValidHexColor: isValidHexColor
    };
    
})(jQuery);

// CSS pro error stavy
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
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .pv-admin-container .auto-saved {
            animation: pulse 0.5s ease-in-out;
        }
    `;
    document.head.appendChild(style);
});