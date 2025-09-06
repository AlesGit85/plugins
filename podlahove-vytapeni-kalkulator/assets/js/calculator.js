// Kalkulátor podlahového vytápění - JavaScript

(function($) {
    'use strict';
    
    let floorCount = 1;
    let maxFloors = 5; // Bude nastaveno z PHP
    let calculationResult = null;
    
    $(document).ready(function() {
        initCalculator();
    });
    
    function initCalculator() {
        // Event listeners
        $(document).on('click', '.pv-area-plus', handleAreaChange);
        $(document).on('click', '.pv-area-minus', handleAreaChange);
        $(document).on('input', '.pv-area-input', validateAreaInput);
        $(document).on('click', '#pv-add-floor', addFloor);
        $(document).on('click', '.pv-remove-floor', removeFloor);
        $(document).on('click', '#pv-calculate', calculatePrice);
        $(document).on('click', '#pv-send-calculation', sendCalculation);
        
        // Inicializace max podlaží z data atributu
        const container = $('.pv-calculator-container');
        if (container.data('max-floors')) {
            maxFloors = parseInt(container.data('max-floors')) || 0;
        }
        
        updateRemoveButtons();
    }
    
    function handleAreaChange(e) {
        e.preventDefault();
        const button = $(this);
        const targetId = button.data('target');
        const input = $('#' + targetId);
        const currentValue = parseFloat(input.val()) || 0;
        const step = parseFloat(input.attr('step')) || 1;
        
        if (button.hasClass('pv-area-plus')) {
            input.val(Math.max(0, currentValue + step));
        } else {
            input.val(Math.max(step, currentValue - step));
        }
        
        input.trigger('input');
    }
    
    function validateAreaInput(e) {
        const input = $(this);
        const value = parseFloat(input.val());
        const min = parseFloat(input.attr('min')) || 0;
        const step = parseFloat(input.attr('step')) || 1;
        
        if (isNaN(value) || value < min) {
            input.val(step);
        }
    }
    
    function addFloor() {
        if (maxFloors > 0 && floorCount >= maxFloors) {
            alert('Maximální počet podlaží je ' + maxFloors);
            return;
        }
        
        floorCount++;
        const template = $('#pv-floor-template').html();
        const floorHtml = template.replace(/\{\{floor_number\}\}/g, floorCount);
        
        $('#pv-floors-container').append(floorHtml);
        updateRemoveButtons();
        
        // Animace nového podlaží
        const newFloor = $('.pv-floor').last();
        newFloor.hide().slideDown(300);
        
        // Scroll k novému podlaží
        $('html, body').animate({
            scrollTop: newFloor.offset().top - 100
        }, 300);
    }
    
    function removeFloor() {
        const floor = $(this).closest('.pv-floor');
        const floorNumber = parseInt(floor.data('floor'));
        
        if (floorCount <= 1) {
            return;
        }
        
        floor.slideUp(300, function() {
            $(this).remove();
            floorCount--;
            updateFloorNumbers();
            updateRemoveButtons();
        });
    }
    
    function updateFloorNumbers() {
        $('.pv-floor').each(function(index) {
            const newNumber = index + 1;
            const floor = $(this);
            
            floor.attr('data-floor', newNumber);
            floor.find('h3').text('Podlaží ' + newNumber);
            
            // Update input names and IDs
            floor.find('input, select').each(function() {
                const element = $(this);
                const name = element.attr('name');
                const id = element.attr('id');
                
                if (name) {
                    element.attr('name', name.replace(/_\d+/, '_' + newNumber));
                }
                if (id) {
                    element.attr('id', id.replace(/_\d+/, '_' + newNumber));
                    // Update related labels
                    floor.find('label[for="' + id + '"]').attr('for', element.attr('id'));
                }
                
                // Update data-target for area buttons
                if (element.hasClass('pv-area-btn') && element.data('target')) {
                    element.attr('data-target', element.data('target').replace(/_\d+/, '_' + newNumber));
                }
            });
        });
    }
    
    function updateRemoveButtons() {
        $('.pv-remove-floor').toggle(floorCount > 1);
        
        // Update add button visibility
        if (maxFloors > 0) {
            $('#pv-add-floor').toggle(floorCount < maxFloors);
        }
    }
    
    function calculatePrice() {
        const floors = collectFloorData();
        
        if (!validateFloorData(floors)) {
            return;
        }
        
        showLoading(true);
        
        $.ajax({
            url: pv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'calculate_heating',
                nonce: pv_ajax.nonce,
                floors: JSON.stringify(floors)
            },
            success: function(response) {
                showLoading(false);
                
                if (response.success) {
                    calculationResult = response.data;
                    displayResults(response.data);
                    showContactSection();
                } else {
                    alert('Chyba při výpočtu: ' + (response.data || 'Neznámá chyba'));
                }
            },
            error: function() {
                showLoading(false);
                alert('Chyba při komunikaci se serverem. Zkuste to prosím znovu.');
            }
        });
    }
    
    function collectFloorData() {
        const floors = [];
        
        $('.pv-floor').each(function(index) {
            const floor = $(this);
            const floorNumber = index + 1;
            
            floors.push({
                area: parseFloat(floor.find('input[name="area[]"]').val()) || 0,
                installation_type: floor.find('input[name="installation_' + floorNumber + '"]:checked').val(),
                pipe_type: floor.find('select[name="pipe_type_' + floorNumber + '"]').val(),
                heat_source: floor.find('select[name="heat_source_' + floorNumber + '"]').val()
            });
        });
        
        return floors;
    }
    
    function validateFloorData(floors) {
        for (let i = 0; i < floors.length; i++) {
            const floor = floors[i];
            
            if (!floor.area || floor.area <= 0) {
                alert('Prosím vyplňte platnou plochu pro podlaží ' + (i + 1));
                return false;
            }
            
            if (!floor.installation_type) {
                alert('Prosím vyberte variantu instalace pro podlaží ' + (i + 1));
                return false;
            }
            
            if (!floor.pipe_type) {
                alert('Prosím vyberte typ potrubí pro podlaží ' + (i + 1));
                return false;
            }
            
            if (i === 0 && !floor.heat_source) {
                alert('Prosím vyberte zdroj tepla pro první podlaží');
                return false;
            }
        }
        
        return true;
    }
    
    function displayResults(data) {
        let detailsHtml = '';
        
        data.details.forEach(function(detail) {
            detailsHtml += '<div class="pv-result-item">';
            detailsHtml += '<span>Podlaží ' + detail.floor + ' (' + detail.area + ' m²)</span>';
            detailsHtml += '<span>' + formatPrice(detail.cost) + ' Kč</span>';
            detailsHtml += '</div>';
        });
        
        $('#pv-result-details').html(detailsHtml);
        $('#pv-total-price').text(formatPrice(data.total_cost) + ' Kč');
        $('#pv-result').slideDown(300);
        
        // Scroll k výsledkům
        $('html, body').animate({
            scrollTop: $('#pv-result').offset().top - 100
        }, 300);
    }
    
    function showContactSection() {
        $('#pv-contact-section').slideDown(300);
    }
    
    function sendCalculation() {
        const email = $('#pv-email').val().trim();
        const phone = $('#pv-phone').val().trim();
        const contactSupport = $('#pv-contact-support').is(':checked');
        
        if (!email) {
            alert('Prosím vyplňte email');
            $('#pv-email').focus();
            return;
        }
        
        if (!isValidEmail(email)) {
            alert('Prosím vyplňte platný email');
            $('#pv-email').focus();
            return;
        }
        
        if (!calculationResult) {
            alert('Nejdříve prosím proveďte výpočet');
            return;
        }
        
        showLoading(true);
        
        $.ajax({
            url: pv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'send_calculation_email',
                nonce: pv_ajax.nonce,
                email: email,
                phone: phone,
                contact_support: contactSupport ? '1' : '0',
                total_cost: calculationResult.total_cost,
                calculation_details: JSON.stringify(calculationResult.details)
            },
            success: function(response) {
                showLoading(false);
                
                if (response.success) {
                    showSuccessMessage();
                    resetForm();
                } else {
                    alert('Chyba při odesílání: ' + (response.data || 'Neznámá chyba'));
                }
            },
            error: function() {
                showLoading(false);
                alert('Chyba při odesílání emailu. Zkuste to prosím znovu.');
            }
        });
    }
    
    function showLoading(show) {
        if (show) {
            $('#pv-loading').fadeIn(200);
            $('.pv-btn').prop('disabled', true);
        } else {
            $('#pv-loading').fadeOut(200);
            $('.pv-btn').prop('disabled', false);
        }
    }
    
    function showSuccessMessage() {
        $('#pv-calculator-form').slideUp(300);
        $('#pv-success-message').slideDown(300);
        
        // Scroll k zpráv o úspěchu
        $('html, body').animate({
            scrollTop: $('#pv-success-message').offset().top - 100
        }, 300);
    }
    
    function resetForm() {
        $('#pv-calculator-form')[0].reset();
        $('#pv-result').hide();
        $('#pv-contact-section').hide();
        calculationResult = null;
        
        // Reset floors to just one
        $('.pv-floor').not(':first').remove();
        floorCount = 1;
        updateRemoveButtons();
    }
    
    function formatPrice(price) {
        return new Intl.NumberFormat('cs-CZ', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(Math.round(price));
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Přidání smooth scrolling pro lepší UX
    function smoothScrollTo(element, offset = 100) {
        if (element.length) {
            $('html, body').animate({
                scrollTop: element.offset().top - offset
            }, 300);
        }
    }
    
    // Export pro další použití
    window.PVCalculator = {
        formatPrice: formatPrice,
        smoothScrollTo: smoothScrollTo
    };
    
})(jQuery);