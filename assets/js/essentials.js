/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */
; (function ($) {


    window.ci = {};

    window.ci.ctc = function (element , val = false) {

        var text = val ? $(element).val() : $(element).text();
        var $temp = $(`<input value="${text}" />`).css({
            'position': 'absolute',
            'top': '-1000px'
        });

        $("body").append($temp);

        $temp.select();
        document.execCommand("copy");
        $temp.remove();

    }

    window.ci.gcs = function (id, target) {
        $.get(_ci.ajax_url, {
            action: "code_stats",
            _wpnonce : _ci.ajax_nonce,
            id: id
        }, function (result) {
            target.parent().html(result);
        }).fail(function () {
        });
    }


    $(document).ready(function () {

        $(".ci-codes__chart-placeholder").each(function (i, e) {
            var target = $(e);
            var id = $(e).attr("data-post");
            window.ci.gcs(id, target);
        });

    });



})(jQuery);
