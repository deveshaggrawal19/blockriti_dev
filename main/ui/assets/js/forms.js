$(document).ready(function(){
    $('.control-error').on('keyup, change', '.form-control', function(){
        $(this).closest('div.control-error').removeClass('control-error');
        $(this).next('p.form-control-static.inline-error').fadeOut('fast');
    });
});