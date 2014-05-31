$(function() {
    var acs_action = 'myprefix_autocompletesearch';
    $(".js-search").autocomplete({
        minLength: 2,
        delay: 50,
        cache: true,
        source: function(req, response) {
            $.getJSON(cb_search.url + '?callback=?&action=' + acs_action, req, response);
            console.log('source');
        },
        select: function(event, ui) {
            console.log(ui.item.label);
            // window.location.href = '?page_id=' + ui.item.searchpageid + '&id=' + ui.item.imdbid;
            return false;
        }
    }).focus(function(event, ui) {
        $(this).data("ui-autocomplete").search($(this).val());
        return false;
    })
    /*.data('ui-autocomplete')._renderItem = function(ul, item) {
        console.log('data');
        if (item === null || item.imdbid == -1) {
            return $("<li></li>")
                .data("item.autocomplete", item)
                .append(item.title)
                .appendTo(ul);
        }
        var inner_html =
            '<a class="movieSearch">' +
            '<div class="list_item_container">' +
            '<div class="poster pull-left">' +
            '<img class="img-rounded" src="' + item.image + '">' +
            '</div>' +
            '<div class="badge badge-info pull-right">' +
            item.type +
            '</div>' +
            '<div class="pull-left">' +
            '<div class="yearlabel label label-inverse">' + item.year + '</div>' +
            '</div><br />' +
            '<div class="title">' +
            '<small>' + item.title + '</small>' +
            '</div>' +
            '</div>' +
            '</a>';
        return $("<li></li>")
            .data( /*"item.autocomplete"*/
    /*"ui-autocomplete-item", item)
            .append(inner_html)
            .appendTo(ul);
    }
    $(".js-search").autocomplete({
        position: {
            my: "right top",
            at: "right bottom"
        }
    });*/
});