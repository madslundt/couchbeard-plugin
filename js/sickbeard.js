var sickbeard_request = $.ajax({
    url: wp_sickbeard.ajaxurl,
    type: "POST",
    data: {
        action: 'sb_getTV_template'
    }
});

$(function() {
    sickbeard_request.done(function(msg) {
        $('#sickbeard .data').html(msg);
    });
});