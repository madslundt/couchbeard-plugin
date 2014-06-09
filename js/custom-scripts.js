/*var sabnzbd_request = $.ajax({
    url: wp.ajaxurl,
    type: "POST",
    data: {
        action: 'connectionStatus'
    }
});*/

$(function() {
    /* ==========================================================================
       SabNZBD
       ========================================================================== */
    /*sabnzbd_request.fail(function(msg) {
        console.log('fail');
        $('#sabnzbd .more').text("Couldn't connect to SABnzbd");
    });*/


    /* ==========================================================================
       General
       ========================================================================== */
    $('.thumbnail').on('click', '.loadmore', function(e) {
        $(this).parent().children('.hidden').removeClass('hidden');
        $(this).hide();
        return false;
    });

    $('.thumbnail').on('click', '.js-update', function(e) {
        $(this).children('i').addClass('loader');
        setTimeout(function() { $('.js-update').children('i').removeClass('loader'); }, 3000);
        return false;
    });

    $(function() {
    $('.couchbeard .search :input').val('').fancyInput()[0].focus();
});
});