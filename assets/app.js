/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import $ from 'jquery';
import 'bootstrap';
import './styles/app.scss';
import 'bootstrap-icons/font/bootstrap-icons.css';


$(function() {

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