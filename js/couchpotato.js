var couchpotato_request = $.ajax({
    url: wp_couchpotato.ajaxurl,
    type: "POST",
    dataType: 'HTML',
    data: {
        action: "cp_getMovies_template"
    }
});

$(function() {
    couchpotato_request.done(function(movies_html) {
        $('#couchpotato .data').html(movies_html);
    });

    $('.couchpotato').on('click', '.list-group-item', function(e) {
        var id = $(this).data('id');
        var imdb = $(this).data('imdb');
        console.log('ID: ' + id);
        console.log('IMDB: ' + imdb);
        if (!$('#movieModal').data('id') || $('#movieModal').data('id') != id) {
            $('#movieModal .modal-content').html('');
            $('#movieModal').data('id', id);
            $.ajax({
                url: wp_couchpotato.ajaxurl,
                type: "POST",
                dataType: 'HTML',
                data: {
                    action: "cp_getMovie_template",
                    id: id
                }
            }).done(function(movie_html) {
                $('#movieModal .modal-content').html(movie_html);
            });
        }
    });

    $('.couchpotato').on('click', '.js-cp-download', function() {
        console.log('click');
        return false;
    });
    $('.couchpotato').on('click', '.js-cp-refresh', function() {
        $(this).children('i').addClass('loader');
        setTimeout(function() { $('.js-cp-refresh').children('i').removeClass('loader'); }, 3000);
        return false;
    });
});