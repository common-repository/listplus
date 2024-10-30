"use strict";

jQuery(document).ready(function($) {
    function uniqueId() {
        return "f_" + new Date().getTime() + Math.trunc(365 * Math.random());
    }

    // Tabs form.
    $(".l-tab-wrapper").on("click", "a", function(e) {
        e.preventDefault();
        var $a = $(this);
        var nav = $a.parent();
        var tab = nav.parent();
        var selector = $a.attr("data-selector") || false;
        if (selector) {
            $("a", nav).removeClass("nav-tab-active");
            $a.addClass("nav-tab-active");
            $(".l-tab-content", tab).removeClass("active");
            $(".l-tab-content" + selector, tab).addClass("active");
        }
    });

    if (typeof window.wp !== "undefined") {
        // Media.
        var uploaderCurrentItem = false;
        var uploaderButton = false;
        var uploader = wp.media({
            title: wp.media.view.l10n.addMedia,
            multiple: false
            //library: {type: 'all' },
            //button : { text : 'Insert' }
        });

        uploader.on("close", function() {
            // get selections and save to hidden input plus other AJAX stuff etc.
            var selection = uploader.state().get("selection");
            // console.log(selection);
        });

        uploader.on("select", function() {
            // Grab our attachment selection and construct a JSON representation of the model.
            var mediaAttachment = uploader
                .state()
                .get("selection")
                .first()
                .toJSON();
            $(".image_id", uploaderCurrentItem).val(mediaAttachment.id);
            var preview, img_url;
            img_url = mediaAttachment.url;
            $(".current", uploaderCurrentItem).addClass("has-media");
            $(".image_url", uploaderCurrentItem).val(img_url);
            if (mediaAttachment.type == "image") {
                preview = '<img src="' + img_url + '" alt="">';
                $(".thumbnail-image", uploaderCurrentItem).html(preview);
            }
            $(".image_id", uploaderCurrentItem).trigger("change");
        });

        $(".ff-wpmedia").each(function() {
            var _item = $(this);
            // When remove item.
            $(".remove-button", _item).on("click", function(e) {
                e.preventDefault();
                $(".image_id, .image_url", _item).val("");
                $(".thumbnail-image", _item).html("");
                _item.removeClass("has-media");
                $(this).hide();
                $(".image_id", _item).trigger("change");
            });

            // When upload item.
            $(".upload-button, .thumbnail-image", _item).on("click", function(
                e
            ) {
                e.preventDefault();
                uploaderCurrentItem = _item;
                uploaderButton = $(this);
                uploader.open();
            });
        });
    } // end if wp media.

    var select2Template = function(state) {
        if (!state.id) {
            return state.text;
        }
        var html = "";
        var moreClass = "";
        if (state.image) {
            moreClass = "has-img";
            html += '<img src="' + state.image + '" alt="" class="img-icon" />';
        } else if (state.svg) {
            moreClass = "has-svg";
            html += '<span class="svg-icon" >' + state.svg + "</span>";
        }

        html += '<span class="text"></span>';
        var $state = $(
            '<span class="custom-tpl ' + moreClass + '">' + html + "</span>"
        );
        $state.find(".text").html(state.text);
        return $state;
    };

    // Select 2.
    $(".lp-form .select2, .lp-form .select2-tax, .lp-form .select2-author")
        .not(".no-select2")
        .each(function() {
            var select = $(this);
            var tax = false,
                author = false;
            var config;
            if (select.hasClass("select2-author")) {
                author = true;
            } else {
                tax = select.attr("data-tax") || false;
            }

            var selected = select.val();
            var data = null;
            var is_ajax = false;
            if (tax) {
                try {
                    config = Object.assign({}, ListPlus.taxs[tax]);
                    data = config["items"];
                    is_ajax = config["more"] ? true : false;
                } catch (e) {
                    data = null;
                }
            }

            if (author) {
                try {
                    config = Object.assign({}, ListPlus.authors);
                    data = config["items"];
                    is_ajax = config["more"] ? true : false;
                } catch (e) {
                    data = null;
                }
            }

            var customDataKey = select.attr("data-custom-key") || false;
            if (customDataKey) {
                //console.log( 'customDataKey', customDataKey );
                try {
                    config = Object.assign({}, ListPlus[customDataKey]);
                    data = config["items"];
                    is_ajax = config["more"] ? true : false;
                } catch (e) {
                    data = null;
                }
            }

            var placeholderTxt = select.attr("placeholder") || false;
            if (!placeholderTxt) {
                placeholderTxt =
                    select
                        .find('option[value=""]')
                        .eq(0)
                        .text() || "";
            }

            var options = {
                placeholder: {
                    id: "", // the value of the option
                    text: placeholderTxt
                },
                allowClear: true
            };

            if (data) {
                options.data = data;
                select.html("");
            }

            if (is_ajax) {
                //options.minimumInputLength = 3;
                options.ajax = {
                    transport: function(params, success, failure) {
                        if (!params.data.q || params.data.q.length < 3) {
                            success({
                                results: data
                            });
                            return {
                                abort: function() {
                                    // console.log("ajax call aborted");
                                }
                            };
                        }

                        var ajaxMoredata = {
                            action: "listplus_tax_ajax",
                            taxonomy: tax,
                            _nonce: ListPlus.tax_nonce
                        };

                        if (author) {
                            ajaxMoredata = {
                                action: "listplus_author_ajax",
                                _nonce: ListPlus.author_nonce
                            };
                        }

                        var ajax_options = {
                            url: ListPlus.ajax_url,
                            type: "GET",
                            data: Object.assign(params.data, ajaxMoredata)
                        };

                        // console.log('params', params);
                        // console.log('ajax_options', ajax_options);

                        var $request = $.ajax(ajax_options);

                        $request.then(success);
                        $request.fail(failure);

                        return $request;
                    }
                };
            } // end if is ajax.

            options.templateResult = select2Template;
            options.templateSelection = select2Template;

            select.select2(options);
            select.val(selected).trigger("change");

            // $list.sortable({
            //     placeholder : 'ui-state-highlight select2-selection__choice',
            //     forcePlaceholderSize: true,
            //     items       : 'li:not(.select2-search__field)',
            //     tolerance   : 'pointer',
            //     stop: function() {
            //         $( $list.find( '.select2-selection__choice' ).get().reverse() ).each( function() {
            //             var id     = $( this ).data( 'data' ).id;
            //             var option = $select.find( 'option[value="' + id + '"]' )[0];
            //             $select.prepend( option );
            //         } );
            //     }
            // });

            if (
                select.attr("data-to-list") ||
                select.hasClass("data-to-list")
            ) {
                select.val(null).trigger("change");

                var parent = select.closest(".ff-l-wrapper");
                var $list = parent.find(".ff-s-list");

                select.on("select2:select", function(e) {
                    var data = e.params.data;

                    if (!data.id || data.id === "-1") {
                        return;
                    }
                    var newIdx = "t_" + new Date().getTime();
                    var html_template = parent.find(".list-li-template").html();
                    html_template = html_template.replace(/__IDX__/g, newIdx);
                    var compiled = _.template(html_template);

                    //  console.log( 'data', data );

                    var html = compiled(data);
                    var item = $(html);
                    item.find(".input-val-id").val(data.id);
                    var label = data.text;
                    if (data.image) {
                        label =
                            '<img class="name-img" src="' +
                            data.image +
                            '" alt="/>' +
                            label;
                    } else if (data.svg) {
                        label =
                            '<span class="name-svg">' +
                            data.svg +
                            "</span>" +
                            label;
                    }

                    item.find(".name").html(label);
                    $list.append(item);
                    $list.trigger("new_item", [item, data]);
                    select.val(null).trigger("change");
                });
            }
        });

    // New tax term.
    $(".dt-new-btn").on("click", function(e) {
        e.preventDefault();
        var p = $(this).closest(".ff-d-tax-new");
        p.toggleClass("adding-new");
        p.find(".new-term-name").focus();
    });

    $(".new-term-name").on("keydown", function(e) {
        if (13 === e.which) {
            e.preventDefault();
            var input = $(this);
            var new_t = input.val();
            input.val(""); // Reset input.
            var parent = input.closest(".ff-l-wrapper");
            var $list = parent.find(".ff-s-list");

            var newIdx = "t_" + new Date().getTime();
            var html_template = parent.find(".list-li-template").html();
            html_template = html_template.replace(/__IDX__/g, newIdx);
            var compiled = _.template(html_template);

            var data = {
                id: "_new_item",
                text: new_t
            };
            var html = compiled(data);
            var item = $(html);
            item.find(".input-val-id").val(data.id);
            item.find(".name").html(data.text);
            item.find(".input-term-name").val(data.text);
            $list.append(item);
            $list.trigger("new_item", [item, data]);

            // return false;
        }
    });

    // Icons.
    var initSelectIcons = function($el) {
        if (typeof $el === "undefined") {
            $el = $(document);
        }

        var iconSelect = function() {
            // console.log( 'res', icons );
            $(".icon-select", $el).each(function() {
                var selectEl = $(this);
                var val = selectEl.val();
                // console.log('val', val);
                var iconOptions = {
                    placeholder: {
                        id: "", // the value of the option
                        svg: "", // the value of the option
                        text: selectEl.attr("placeholder") || ""
                    },
                    allowClear: true
                };

                var templateIcon = function(state) {
                    if (!state.id) {
                        return state.text;
                    }

                    var html = "";
                    var moreClass = "";
                    if (state.svg) {
                        moreClass = "has-svg";
                        html +=
                            '<span class="svg-icon" >' + state.svg + "</span>";
                    }
                    html += '<span class="text"></span>';
                    var $state = $(
                        '<span class="custom-tpl ' +
                            moreClass +
                            '">' +
                            html +
                            "</span>"
                    );
                    $state.find(".text").html(state.text);
                    return $state;
                };
                iconOptions.data = _.clone(window.icons);

                console.log("Call icon----", selectEl);
                iconOptions.templateResult = templateIcon;
                iconOptions.templateSelection = templateIcon;
                selectEl.select2(iconOptions);
                selectEl.val(val).trigger("change");
            });
        };

        if ($(".icon-select", $el).length) {
            if (typeof window.icons === "undefined") {
                $.ajax({
                    url: ListPlus.ajax_url,
                    data: {
                        action: "listplus_get_icons"
                    },
                    success: function(icons) {
                        window.icons = icons;
                        iconSelect();
                    }
                });
            } else {
                iconSelect();
            }
        }
    };

    initSelectIcons();

    // Date picker
    $(".lp-form .fd-flatpickr").each(function() {
        $(this).flatpickr({
            enableSeconds: true,
            time_24hr: true,
            enableTime: true,
            enableSeconds: true,
            weekNumbers: true,
            dateFormat: "Y-m-d H:i:S",
            altInput: "Y-m-d H:i:S",
            altFormat: "Y-m-d H:i:S",
            wrap: true
        });
    });

    // Remove list item.
    $(document).on(
        "click",
        ".fls-2 li .li-remove, .fls-1 li .li-remove",
        function(e) {
            e.preventDefault();
            $(this)
                .closest("li")
                .remove();
        }
    );

    $(".open-hours").each(function() {
        var hoursBox = $(this);

        var rename = function($row) {
            $(".hd-hour", $row).each(function(index) {
                var r = $(this);
                var name = r.attr("data-name") || "";
                $("input", r).each(function() {
                    var n = $(this).attr("data-name") || false;
                    if (n) {
                        $(this).attr(
                            "name",
                            name + "[" + index + "][" + n + "]"
                        );
                    }
                });
            });
        };

        // Clear open hours.
        hoursBox.on("click", ".clear", function(e) {
            e.preventDefault();
            var p = $(this).closest(".hd-row");
            p.find(".ih")
                .val("")
                .trigger("change");
            p.attr("data-status", "");
            p.find(".status")
                .find("option")
                .prop("selected", false);
        });

        // Open hours status change.
        hoursBox.on("change", ".status", function(e) {
            var stt = $(this).val();
            console.log("val", stt);
            var p = $(this).closest(".hd-row");
            p.attr("data-status", stt);
        });

        // Add more hour range.
        hoursBox.on("click", ".h-add", function(e) {
            e.preventDefault();
            var p = $(this).closest(".hd-hour");
            var clone = p.clone();
            clone.find("input").val("");
            clone.insertAfter(p);
            rename(p.closest(".hd-hours"));
        });

        // Remove hour range.
        hoursBox.on("click", ".h-remove", function(e) {
            e.preventDefault();
            var p = $(this).closest(".hd-hour");
            var w = p.closest(".hd-hours");
            if (w.find(".hd-hour").length > 1) {
                p.remove();
            } else {
                p.find("input").val("");
            }
            rename(w);
        });
    }); // End open hours.

    // Add Websites.
    $(".ff-webistes").on("click", ".add-new", function(e) {
        e.preventDefault();
        var p = $(this).closest(".ff-webistes");
        var ls = p.find(".ls-websistes");
        var cloneItem = ls
            .find(".wi")
            .first()
            .clone();
        cloneItem.find("input").val("");
        ls.append(cloneItem);
    });
    // Remove webiste.
    $(".ff-webistes").on("click", ".wi .remove", function(e) {
        e.preventDefault();
        var item = $(this).closest(".wi");
        var ls = $(this).closest(".ls-websistes");
        var l = ls.find(".wi").length;
        if (l > 1) {
            item.remove();
        } else {
            item.find("input").val("");
        }
    });

    // When forcus error field.
    $(".lp-main-form").on("click", ".ff", function() {
        var $f = $(this);
        $f.removeClass("error");
    });

    // Loop form.
    $(".lp-main-form").each(function() {
        var form = $(this);
        form.uploadZones = {};

        form.find(".list-sortable").sortable({
            items: "li"
        });

        $(".f-dropzone", form).each(function() {
            var dropzone = $(this);
            var inputName = dropzone.attr("data-name") || "upload_files";
            form.uploadZones[inputName] = new FormData(); // Currently empty
            dropzone.find(".input-file-pickup").removeAttr("name");

            dropzone.find(".sortable").sortable({
                items: ".fm-i:not(.ui-state-disabled)"
            });

            dropzone.on(
                "drag dragstart dragend dragover dragenter dragleave drop",
                function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            );

            dropzone.on("dragover dragenter", function() {
                $(this).addClass("is-dragover");
            });
            dropzone.on("dragleave dragend drop", function() {
                $(this).removeClass("is-dragover");
            });

            function previewUploadFiles(files, dropzone) {
                var inputFile = dropzone.find(".input-file-pickup");
                var beforeEl = inputFile.closest(".fm-i");

                for (var i = 0; i < files.length; i++) {
                    var url = URL.createObjectURL(files[i]);
                    var name = uniqueId();
                    var orderInput = inputName + "_order[" + name + "]";
                    let template =
                        '<div class="js-m-add fm-i">\
                            <div class="fm-ii">\
                                <input type="hidden" class="fi-order" name="' +
                        orderInput +
                        '" value="1">\
                                <a class="fm-ri" href="#">' +
                        ListPlus.close_icon +
                        "</a>\
                            </div>\
                        </div>";

                    var $el = $(template);
                    $el.attr("data-index", name);
                    $el.insertBefore(beforeEl);
                    $el.find(".fm-ii").append(
                        '<img src="' + url + '" alt="" />'
                    );
                    form.uploadZones[inputName].append(
                        inputName + "[" + name + "]",
                        files[i]
                    );
                }

                // Display the key/value pairs
                console.log("New dataa--------");
                for (var pair of form.uploadZones[inputName].entries()) {
                    console.log(pair[0] + ", " + pair[1]);
                }
            }

            dropzone.on("drop", function(e) {
                var files = e.originalEvent.dataTransfer.files;
                // Now select your file upload field
                console.log("files", files);
                previewUploadFiles(files, dropzone);
                // var inputFile = dropzone.find('.input-file-pickup');
                //inputFile.prop('files', pickupFiles );
            });

            dropzone.on("change", ".input-file-pickup", function(e) {
                var files = e.target.files;
                previewUploadFiles(files, dropzone);
            });

            $(document).on("click", ".f-dropzone .fm-i .fm-ri", function(e) {
                e.preventDefault();
                var $el = $(this).closest(".fm-i");
                if ($el.hasClass("js-m-add")) {
                    var k = $el.attr("data-index") || "";
                    if (k) {
                        form.uploadZones[inputName].delete(
                            inputName + "[" + k + "]"
                        );
                    }

                    var url = $el.find("img").attr("src");
                    URL.revokeObjectURL(url);

                    // Display the key/value pairs.
                    console.log("Delete dataa--------");
                    for (var pair of form.uploadZones[inputName].entries()) {
                        console.log(pair[0] + ", " + pair[1]);
                    }
                }
                $el.remove();
            });
        }); // end loop drop zones

        // Submit form

        form.on("submit", async function(e) {
            e.preventDefault();
            var $currentForm = $(this);
            $currentForm.addClass("loading");
            if (typeof grecaptcha !== "undefined") {
                try {
                    await grecaptcha
                        .execute(ListPlus.recaptcha_key, { action: "homepage" })
                        .then(function(token) {
                            if (!$("input.ljs-capt", $currentForm).length) {
                                $currentForm.append(
                                    '<input type="hidden" name="recaptcha_respond" class="ljs-capt">'
                                );
                            }
                            $("input.ljs-capt", $currentForm).val(token);
                        });
                } catch (e) {
                    console.log("recaptcha_key");
                }
            }

            var saveFrom = new FormData($currentForm[0]);

            $.each(form.uploadZones, function(key, formData) {
                try {
                    for (var pair of formData.entries()) {
                        saveFrom.append(pair[0], pair[1]);
                    }
                } catch (e) {
                    console.log("Not found FD");
                }
            });

            if (typeof tinymce !== "undefined") {
                $("textarea.wp-editor-area", form).each(function() {
                    var eid = $(this).attr("id");
                    var ename = $(this).attr("name");
                    var content = tinymce.get(eid).getContent();
                    saveFrom.set(ename, content);
                });
            }

            form.find(".lp-success, .lp-errors").remove();

            // Send to server.
            var req = new XMLHttpRequest();
            req.open("POST", ListPlus.ajax_url, true);
            req.onload = function() {
                if (req.readyState === req.DONE) {
                    if (req.status == 200) {
                        console.log("Saved!");
                        $currentForm.removeClass("loading");
                        var respond = JSON.parse(req.response);
                        console.log(respond);

                        $(".ff", form).removeClass("error");

                        if (respond.error_codes && respond.error_codes.length) {
                            $.each(respond.error_codes, function(idx, code) {
                                $('.ff[data-id="' + code + '"]', form).addClass(
                                    "error"
                                );
                            });
                        }

                        if (!respond.success) {
                            if (form.hasClass("notice-bottom")) {
                                form.append(respond.error_html);
                            } else {
                                form.prepend(respond.error_html);
                            }
                        } else {
                            if (form.hasClass("notice-bottom")) {
                                form.append(respond.success_html);
                            } else {
                                form.prepend(respond.success_html);
                            }

                            // form[0].reset();
                            try {
                                $(".rateit", form).rateit("reset");
                            } catch (e) {}
                            if (respond.success && respond.redirect_url) {
                                window.location = respond.redirect_url;
                            }
                        }

                        //  console.log(req.responseText);
                        //oOutput.innerHTML = 'Uploaded!';
                    } else {
                        // oOutput.innerHTML = 'Error ' + res.status + ' occurred when trying to upload your file.<br />';
                    }
                }
            };
            req.send(saveFrom);
        });
    }); // end loop form.

    $(".ff-map").each(function() {
        $(this).MapForm();
    }); // end map.

    // Form field builder -----------------------.

    // Loop each builder.
    $(".lp-form-builder").each(function() {
        var builder = $(this);
        var editTpl = $(".fbf-field-tpl", builder).html();
        var inputValue = $(".ff-l-values", builder);
        var select = $(".select_field", builder);
        var fiedList = $(".lp-fb-fields", builder);
        var availableFields = $(".lp-fb-available", builder);
        // var html_template = builder.find('.list-li-template').html();
        // var compiled = _.template(html_template);
        var compiledEdit = _.template(editTpl);
        var dataKey = builder.attr("data-key") || "";
        var definedFields = builder.data("fields");
        //console.log('definedFields', definedFields);

        /**
         * Function that loads the Mustache template
         */
        var repeaterTemplate = _.memoize(function() {
            var compiled,
                /*
                 * Underscore's default ERB-style templates are incompatible with PHP
                 * when asp_tags is enabled, so WordPress uses Mustache-inspired templating syntax.
                 *
                 * @see trac ticket #22344.
                 */
                options = {
                    evaluate: /<#([\s\S]+?)#>/g,
                    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                    escape: /\{\{([^\}]+?)\}\}(?!\})/g,
                    variable: "data"
                };

            return function(data) {
                var tpl = builder.find(".list-li-template").html();
                compiled = _.template(tpl);
                return compiled(data);
            };
        });
        var compiled = repeaterTemplate();

        var get_item_data = function($item) {
            var itemData = Object.assign({}, $item.data("field-data") || {});
            if (itemData) {
                delete itemData.type;
                delete itemData.name;
                delete itemData.title;
                delete itemData.name;
                delete itemData.icon;
                delete itemData.options;
            }

            if ($item.find(".children_fields").length) {
                var children = [];
                $(".children_fields .lp-fb-g", $item).each(function() {
                    var childData = get_item_data($(this));
                    if (childData) {
                        children.push(childData);
                    }
                });
                itemData.children = children;
            }

            return itemData;
        };

        var updateFormFieldData = function(builder) {
            var fields = [];
            $(".lp-fb-fields > .lp-fb-g", builder).each(function() {
                var itemData = get_item_data($(this));
                if (itemData) {
                    fields.push(itemData);
                }
            });

            // console.log( '_saved', fields );
            inputValue.val(JSON.stringify(fields));
        };

        // When edit form changes.
        builder.on(
            "keyup change",
            ".lp-fb-edit-f input, .lp-fb-edit-f select, .lp-fb-edit-f textarea",
            function() {
                var input = $(this);
                var p = input.closest(".lp-fb-g");
                var data = p.data("field-data") || {};
                var key = input.attr("data-key") || "";
                var val = input.val() || "";

                if (input.is(":checkbox")) {
                    if (input.is(":checked")) {
                        val = "yes";
                    } else {
                        val = "";
                    }
                }

                if (key && p) {
                    if (typeof data.custom === "undefined") {
                        data.custom = {};
                    }
                    data.custom[key] = val;

                    p.data("field-data", data);
                    if (val && "label" === key) {
                        console.log("Label", val);
                        $(">.lp-fb-head .lp-fb-title", p).text(val);
                    } else {
                        $(">.lp-fb-head .lp-fb-title", p).text(data.title);
                    }
                }
                updateFormFieldData(builder);
            }
        );

        // When click done button
        builder.on("click", ".lp-fb-edit-f button", function(e) {
            e.preventDefault();
            var p = $(this).closest(".lp-fb-g");
            p.find(".lp-fb-edit-f").hide();
            updateFormFieldData(builder);
        });

        // When click to toggle and then display edit form.
        builder.on("click", ".lp-fb-g > .lp-fb-head > .lp-fb-tg", function(e) {
            e.preventDefault();
            var p = $(this).closest(".lp-fb-g");
            p.toggleClass("active");
            if (p.hasClass("active")) {
                if (p.find("> .lp-fb-edit-f").length) {
                    p.find("> .lp-fb-edit-f").show();
                } else {
                    var data = p.data("field-data") || {};
                    var editForm = $(compiledEdit(data));
                    editForm.insertAfter(p.find("> .lp-fb-head"));
                    initSelectIcons(editForm);
                }
            } else {
                p.find("> .lp-fb-edit-f").hide();
            }
        });

        // When click to remove button.
        builder.on("click", ".lp-fb-g .lp-fb-rm", function(e) {
            e.preventDefault();
            var p = $(this).closest(".lp-fb-g");
            var field = p.data("field-data");
            if (field._type === "preset") {
                builder
                    .find('.preset button[f-id="' + field.id + '"]')
                    .prop("disabled", false);
            }
            p.remove();
            updateFormFieldData(builder);
        });

        // When new item .
        builder.on("click", ".lp-fb-available button", function(e) {
            e.preventDefault();
            var btn = $(this);
            var field = _.clone($(this).data("field"));
            console.log("New", field);
            var html = compiled(field);
            var item = $(html);

            item.data("field-data", field);
            fiedList.append(item);
            if ("preset" === field._type) {
                btn.prop("disabled", true);
            }

            if (field.type === "group") {
                // Sort fields.
                item.find(".children_fields").sortable({
                    handle: ".lp-fb-lb",
                    items: "> div",
                    containment: builder,
                    connectWith: ".lp-fb-fields, .children_fields",
                    update: function(event, ui) {
                        updateFormFieldData(builder);
                    }
                });
            }

            updateFormFieldData(builder);
        });

        // Sort fields.
        fiedList.sortable({
            handle: ".lp-fb-lb",
            items: "> div",
            containment: builder,
            connectWith: ".children_fields",
            update: function(event, ui) {
                updateFormFieldData(builder);
            },
            beforeStop: function(ev, ui) {
                if (
                    $(ui.item).hasClass("g-nest") &&
                    $(ui.placeholder).parent()[0] != this
                ) {
                    $(this).sortable("cancel");
                }
            }
        });

        // fiedList.sortable('disable');

        // Load existing data.
        var existingFields = [];
        var alreadyAdded = [];
        try {
            existingFields = JSON.parse(inputValue.val() || "[]");
        } catch (e) {}

        // console.log( "Val", inputValue.val() );
        // console.log( "existingFields", existingFields );

        var addField = function($list, field) {
            var setting = _.findWhere(definedFields, { id: field.id });
            var copy = Object.assign({}, setting);
            if (!setting) {
                return;
            }
            field = Object.assign(copy, field);
            alreadyAdded.push(field.id);
            // var html_template = builder.find('.list-li-template').html();
            // var compiled = _.template(html_template);
            var html = compiled(field);
            var item = $(html);
            item.data("field-data", field);
            $list.append(item);
            return item;
        };

        $.each(existingFields, function(i, field) {
            var item = addField(fiedList, Object.assign({}, field));
            if (item && field._type === "group") {
                if (Array.isArray(field.children) && field.children.length) {
                    var childList = item.find(".children_fields");
                    $.each(field.children, function(index, childF) {
                        addField(childList, childF);
                    });
                }
            }
        });

        updateFormFieldData(builder);

        fiedList.find(".children_fields").sortable({
            handle: ".lp-fb-lb",
            items: "> div",
            containment: builder,
            connectWith: ".lp-fb-fields, .children_fields",
            update: function(event, ui) {
                updateFormFieldData(builder);
            }
        });

        // Setup available items.
        $.each(definedFields, function(key, data) {
            var item = $('<button type="button" class="lf-fa-item"></button>');
            var field = Object.assign({}, data);
            item.html(field.title);
            item.data("field", field);
            item.attr("f-id", field.id);
            if (field._type === "preset") {
                availableFields.find(".preset").append(item);
                if (alreadyAdded.indexOf(field.id) > -1) {
                    item.prop("disabled", true);
                }
            } else {
                availableFields.find(".custom").append(item);
            }
        });
    }); // end loop form builder.

    // Close builder modal.
    $(document).on("click", ".l-close-modal", function(e) {
        e.preventDefault();
        $(this)
            .closest(".l-sl-modal")
            .removeClass("active");
        $("body").removeClass("l-modal-open");
    });

    $(document).on("keydown", function(e) {
        if (e.which === 27) {
            // esc button
            $(".l-sl-modal").removeClass("active");
            $("body").removeClass("l-modal-open");
        }
    });

    $(document).click(function(e) {
        // The element.
        var container = $(".l-modal-inner");

        //Click to out side modal.
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.closest(".l-sl-modal");
        }
    });

    // Layout builder modal.
    $(document).on("click", ".sl-toggle-modal", function(e) {
        e.preventDefault();
        var a = $(this);
        var selector = a.attr("data-selector") || false;
        if (selector) {
            $(selector).toggleClass("active");
            if ($(selector).hasClass("active")) {
                $("body").addClass("l-modal-open");
            } else {
                $("body").removeClass("l-modal-open");
            }
        }
    });

    // END Form field builder -----------------------.

    
/* Premium Code Stripped by Freemius */

}); // end document ready.
