/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */
; (($) => {
    "user strict"

    // init i18n methods
    if (typeof wp !== "undefined" && typeof wp.i18n !== "undefined") {
        var { __, _x, _n, sprintf } = wp.i18n;
    } else {
        function __(text, ctx) {
            var dic = _ci.i18n[ctx] || {
                "texts": [],
                "translates": []
            };
            var index = dic["texts"].indexOf(text);
            if(index<0){
                return text;
            }
            return dic["translates"][index];
        }
    }

    var parent, textarea, toolbar, fullscreen, code,
        languages = [
            "html",
            "css",
            "javascript",
            "xml",
            "json",
            "php"
        ], langsList;


    function updatePostTitle() {
        var $title = $("#title");
        var $wrap = $("#titlewrap");
        if ($title.val().length == 0) {
            $wrap.addClass('busy');
            $.get(_ci.ajax_url, {
                "action": "code_generate_title",
                "_wpnonce": _ci.ajax_nonce
            }, function (result) {
                $wrap.removeClass('busy');
                if (result.success) {
                    $title.val(result.data);

                }
            }).fail(function () {
                $wrap.removeClass('busy');
            });
        }
    }

    $(document).ready(() => {

        $('.quicktags-toolbar').hide();
        $('.wp-editor-area').hide();
        $('#wp-content-editor-tools').hide();
        $('#wp-content-wrap').hide();


        updatePostTitle();


        $('#post-status-info').remove();

        // create new elements
        parent = $('#postdivrich');

        textarea = $('.wp-editor-area');

        langsList = $("<ul>").addClass("ci-languages");

        languages.forEach(l => {
            var item = $("<li>")
                .addClass("ci-lang-select")
                .attr("data-language", l)
                .text(l)
                .click(function (e) {

                    $(".ci-lang-select.active").removeClass("active");

                    var lang = $(e.target).attr("data-language");

                    var model = window.ci.editor.getModel();

                    monaco.editor.setModelLanguage(model, lang);

                    $(e.target).addClass("active");

                });

            if (l == "html") {
                item.addClass("active");
            }

            langsList.append(item);
        });

        toolbar = $('<div>')
            .addClass('quicktags-toolbar dcp-ci-toolbar')
            .appendTo(parent);


        toolbar.append(langsList);


        container = $('<div>')
            .addClass('dcp-ci-editor')
            .appendTo(parent);

        fullscreen = $('<div>')
            .addClass('full-screen ed_button qt-dfw')
            .appendTo(toolbar)
            .click((e) => {
                e.preventDefault();
                parent.toggleClass('fullscreen');
                window.ci.editor.layout();
            });

        // store initial value
        code = textarea.text();

        require(['vs/editor/editor.main'], () => {

            // create editor
            window.ci.editor = monaco.editor.create(container[0], {
                value: textarea.text(),
                theme: 'vs-dark',
                language: 'html'
            });

            // update code
            window.ci.editor.getModel().onDidChangeContent((event) => {
                textarea.text(window.ci.editor.getModel().getValue());
            });

            window.ci.editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KEY_S, () => {

                if (textarea.text() === code) {
                    return;
                }

                $('#publish').trigger('click');

            });

            window.ci.editor.addCommand(monaco.KeyMod.Alt | monaco.KeyMod.Shift | monaco.KeyCode.KEY_F, () => {

                window.ci.editor.getAction('editor.action.formatDocument').run();

            });

        });


        $("[data-checkbox-activator]").each(function (index, element) {

            var _this = $(this);

            function toggleTargets(obj, reverse = false) {

                if (reverse) {

                    var hidetargets = obj.attr("data-hide-targets") || "";

                    hidetargets.split(',').forEach(function (e, i) {

                        var elem = $(`#${e}`);

                        if (obj[0].checked) {
                            elem.hide();
                        } else {
                            elem.show();
                        }

                    });

                    return;
                }

                var showtargets = obj.attr("data-show-targets") || "";

                showtargets.split(',').forEach(function (e, i) {

                    var elem = $(`#${e}`);

                    if (obj[0].checked) {
                        elem.show();
                    } else {
                        elem.hide();
                    }

                });

            }

            toggleTargets(_this);
            toggleTargets(_this, true);

            _this.on("click", function (event) {
                var obj = $(event.target);
                toggleTargets(obj);
                toggleTargets(obj, true);
            });

        });



        $("#fileInputDelegate").on("click", function (e) {
            e.preventDefault();
            $('#fileInput').trigger("click");
        });

        $("#title").addClass("disabled-input");

        $copybtn = $('<a href="javascript:void(0)" class="copy-btn">' +
            __("Copy", "code-injection") +
            '<span class="dashicons dashicons-external"></span></a>')
            .on('click', function () {
                window.ci.ctc($("#title")[0] , true);
            });

            if(window._ci.is_rtl == "true"){
                $copybtn.css({
                    left: "0",
                    right: "unset"
                });
            }

        $("#titlewrap").append($copybtn);


        $('#fileInput').on("change", function (e) {

            var input = $(this)[0];

            var fileTypes = ['txt', 'css', 'html', 'htm', 'php', 'temp', 'js', 'svg'];  //acceptable file types

            var file = input.files[0];

            var filesize = file.size;

            var extension = file.name.split('.').pop().toLowerCase();

            var isSuccess = fileTypes.indexOf(extension) > -1;


            if (filesize > 300000) {
                var sizeConfirm = confirm(__("The File is too large. Do you want to proceed?", "code-injection"));
                if (!sizeConfirm) {
                    return;
                }
            }

            if (!isSuccess) {
                alert(__("The selected file type is not supported.", "code-injection") + " [ *." + fileTypes.join(", *.") + " ]");
                return;
            }

            var reader = new FileReader();

            reader.onload = function (e) {
                var textarea = $('.wp-editor-area');

                var value = e.target.result;

                var editorModel = window.ci.editor.getModel();

                if (textarea.text() != "") {
                    var overrideConfirm = confirm(__("Are you sure? You are about to replace the current code with the selected file content.", "code-injection"));
                    if (overrideConfirm) {
                        //textarea.text(e.target.result);
                        editorModel.setValue(value);
                    }
                } else {
                    editorModel.setValue(value);
                }
            };

            reader.readAsText(file);


        });

        $(window).on('resize', function () {
            if (window.ci.editor) {
                window.ci.editor.layout();
            }
        });

    });

})(jQuery);