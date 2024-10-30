var mfs_getUrlParameter = function mfs_getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

jQuery(document).ready(function() {
    jQuery('table.wp-list-table #the-list').sortable({
        'items': 'tr',
        'axis': 'y',
        'update' : function(e, ui) {
            var post_type = 'mfs_slider';
            var order     = jQuery('#the-list').sortable('serialize');
            var paged     = mfs_getUrlParameter('paged');
            if (typeof paged === 'undefined')
                paged = 1;

            var queryString = {
                'action': 'mfs_save_order',
                'post_type': post_type,
                'order': order,
                'paged': paged,
                'mfs_order_sort_nonce': mfs_order.mfs_order_sort_nonce
            };
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: queryString,
                cache: false,
                dataType: "html",
                success: function(data) {},
                error: function(html) {}
            });
        }
    });
});