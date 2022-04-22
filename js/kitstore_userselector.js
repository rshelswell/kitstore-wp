(function ($) {
    $(document).ready(function () {
        $('#group-selector').change(function () {
            $.post(
                KWP_Ajax.ajaxurl,
                {
                    // wp ajax action
                    action: 'ajax-kitstoreUserSelector',
                    q: $('group-selector').children("option:selected").val();
                    nextNonce: KWP_Ajax.nextNonce
                },
                function (response) {
                    console.log(response)
                }
            );
            return false;
        });
    });
})(jQuery);
