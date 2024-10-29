jQuery(document).ready(function() {
    var previewButton = jQuery('.previewbutton');

    const wrapper = document.createElement('div');
    wrapper.innerHTML = asp_nl2br( jQuery('textarea[name="asp_content"]').val() );

    previewButton.on('click', function(event) {
        event.preventDefault();

        var theme = jQuery('select[name="asp_theme"]').val();

        var link = jQuery('link#sweet-alert-theme');
        if (jQuery.inArray(theme, ['facebook', 'google', 'twitter']) != -1) {
            if (link.size() == 1) {
                link.attr('href', asp.aspexisweetpopups_url + 'css/sweetalert-' + theme + '.css');
            } else {
                jQuery('<link />', {
                    id: 'sweet-alert-theme',
                    rel: 'stylesheet',
                    type: 'text/css',
                    href: asp.aspexisweetpopups_url + 'css/sweetalert-' + theme + '.css'
                }).appendTo('head');
            }
        } else {
            link.remove();
        }

        var type = jQuery('input[name="asp_icon_type"]:checked').val();
        if (type == 'empty')
            type = '';

        swal({
            title: jQuery('input[name="asp_title"]').val(),
            content: wrapper,
            icon: type
        });
    });

    jQuery('input[name="asp_only_once"]').on('click', function() {
        if (!jQuery(this).is(':checked')) {
            jQuery('input[name="asp_only_once_days"]').prop('disabled', true);
        } else {
            jQuery('input[name="asp_only_once_days"]').prop('disabled', false);
        }
    });

    var fields = jQuery(':input').serializeArray();

    // jQuery(window).on('beforeunload', function() {
    //     var newFields = jQuery(':input').serializeArray();
    //
    //     var inputChanged = false;
    //
    //     jQuery.each(newFields, function() {
    //         var newFieldName = this.name;
    //         var newFieldValue = this.value;
    //         jQuery.each(fields, function() {
    //             if (this.name == newFieldName && this.value != newFieldValue)
    //                 inputChanged = true;
    //         });
    //     });
    //
    //     if (inputChanged)
    //         return false;
    // });

    jQuery('a').on('click', function(event) {
        var newFields = jQuery(':input').serializeArray();

        var inputChanged = false;

        jQuery.each(newFields, function() {
            var newFieldName = this.name;
            var newFieldValue = this.value;
            jQuery.each(fields, function() {
                if (this.name == newFieldName && this.value != newFieldValue)
                    inputChanged = true;
            });
        });

        var that = this;
        if (inputChanged) {
            event.preventDefault();

            swal({
                title: asp.nav_tab_changed_title,
                text: asp.nav_tab_changed_text,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: asp.nav_tab_changed_yes,
                cancelButtonText: asp.nav_tab_changed_no
            }, function(isConfirm) {
                if (isConfirm == true)
                    sweetAlert.close();
                else {
                    window.location = jQuery(that).attr('href');
                }
            });
        }
    });
});

function asp_nl2br (str, is_xhtml) {   
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';    
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
}
