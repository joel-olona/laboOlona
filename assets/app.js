/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';
import 'bootstrap';
import './bootstrap.js';
import 'bootstrap-icons/font/bootstrap-icons.css';
import $ from 'jquery';


$(function() {
    $('#experience').on('shown.bs.modal', function () {
        for (let i = 0; i < 10; i++) {  // Ici, j'utilise 10 comme exemple, ajustez selon le nombre d'éléments que vous avez
            const $currentlyCheckbox = $(`#step_two_experiences_${i}_enPoste`);
            const $endDateFieldContainer = $(`#step_two_experiences_${i}_dateFin`).closest('div');
    
            if (!$currentlyCheckbox.length) {
                // Si le checkbox n'existe pas, on sort de la boucle
                break;
            }
    
            if ($currentlyCheckbox.is(':checked')) {
                $endDateFieldContainer.parent().hide();
            }
    
            $currentlyCheckbox.off('change').change(function() {
                if ($(this).is(':checked')) {
                    $endDateFieldContainer.parent().hide();
                } else {
                    $endDateFieldContainer.parent().show();
                }
            });
        }
    });    

    var modalIds = ['experience', 'technicalSkill'];

    modalIds.forEach(function(modalId) {
        $('#' + modalId).on('hidden.bs.modal', function () {
            $(this).find('ul[data-form-collection-target="collectionContainer"]').empty();
        });
    });

    $('#account_identity .custom-control-input').on('change', function() {
        $(this).closest('form').submit();
    });

    $('.image-checkbox img').on('click', function() {
        // Ajouter l'effet de clignotement
        $(this).addClass('blinking');
        
        // Retirer l'effet de clignotement après 1.2 secondes (2 cycles d'animation)
        setTimeout(() => {
            $(this).removeClass('blinking');
        }, 900);
    });
});