/* common.js - shared page scripts (needs jQuery 1.x) */

$(document).ready(function () {
    // confirmation for dangerous buttons
    $('input[data-confirm], a[data-confirm]').click(function () {
        return confirm($(this).attr('data-confirm'));
    });

    // fade out success messages
    setTimeout(function () {
        $('.msg-success').fadeOut('slow');
    }, 5000);

    // highlight table rows on hover (old IE does not support tr:hover)
    $('table.grid tr').hover(
        function () { $(this).addClass('hover'); },
        function () { $(this).removeClass('hover'); }
    );
});
