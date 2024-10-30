jQuery(document).ready(function($) {
    $(".no-js")
        .removeClass("no-js")
        .addClass("js");

    // Listing Map.
    $(".l-single-map").SingleMap();
    // Listing Map.
    $(".listings-map").ListingMap();
    // Sticky Map.
    $(".listings-map").StickyMap();
    // Rateit

    $(document).on("over", ".rateit", function(event, value) {
        $(this).attr("data-rating", parseInt(value));
    });
    $(document).on("rated", ".rateit", function(event, value) {
        $(this).attr("data-rated", parseInt(value));
    });

    // Toogle more content.
    $(document).on("click", ".l-more-btn", function(e) {
        e.preventDefault();
        var button = $(this);
        var p = $(this).parent();
        var txt_less = button.attr("data-less") || "Show less";
        var txt_more = button.attr("data-more") || "Read more";
        if ($(".l-des-full", p).length) {
            $(".l-des-full", p)
                .removeClass("l-des-full")
                .addClass("l-des-more");
            button.html(txt_more);
        } else {
            $(".l-des-more", p)
                .removeClass("l-des-more")
                .addClass("l-des-full");
            button.html(txt_less);
        }
    });

    $(".lightSlider").lightSlider({
        gallery: true,
        autoWidth: true,
        loop: false,
        pager: false,
        enableDrag: true,
        prevHtml: ListPlus_Front.nav[0],
        nextHtml: ListPlus_Front.nav[1],
        onSliderLoad: function(el) {
            el.lightGallery({
                share: false,
                download: false,
                selector: ".img-inner:not(.last)"
            });
        }
    });

    // Single photos page.
    if ($(".l-single-photos").length) {
        $(".l-single-photos").lightGallery({
            share: false,
            download: false,
            selector: ".img-inner:not(.last)"
        });
    }

    // Review rating.
    var rateText = function(event, value) {
        var $el = $(this);

        var text = "";
        try {
            text = ListPlus_Front.reviews[value];
        } catch (e) {
            text = "";
        }

        var p = $el.parent();
        if (text) {
            p.find(".l-rating-text").text(text);
        } else {
            p.find(".l-rating-text").text("");
        }

        if ("rated" === event.type) {
            var link = $el.attr("data-link") || false;
            if (link) {
                link = link.replace("__NUMBER__", value);
                window.location = link;
            }
        }
    };

    $("#l-current-user-rating").bind("rated reset over", rateText);

    $(".l-ajax-form").on("submit", async function(e) {
        e.preventDefault();
        var form = $(this);

        if (typeof grecaptcha !== "undefined") {
            try {
                await grecaptcha
                    .execute(ListPlus.recaptcha_key, { action: "homepage" })
                    .then(function(token) {
                        if (!$("input.ljs-capt", $form).length) {
                            $form.append(
                                '<input type="hidden" name="recaptcha_respond" class="ljs-capt">'
                            );
                        }
                        $("input.ljs-capt", $form).val(token);
                    });
            } catch (e) {
                console.log("recaptcha_key");
            }
        }

        form.addClass("loading");
        var saveFrom = new FormData(form[0]);
        var req = new XMLHttpRequest();
        req.open("POST", ListPlus_Front.ajax_url, true);

        var canSubmit = true;
        if ($(".alert_required", form).length) {
            $(".alert_required", form).each(function() {
                var val = $(this).val();
                if (!val) {
                    var msg = $(this).attr("data-validate-msg") || false;
                    if (!msg) {
                        msg = "Please fill the required field.";
                    }
                    alert(msg);
                    canSubmit = false;
                }
            });
        }

        if (canSubmit) {
            req.onload = function() {
                if (req.readyState === req.DONE) {
                    form.removeClass("loading");
                    if (req.status == 200) {
                        var respond = JSON.parse(req.response);
                        console.log(respond);
                        console.log("Saved!");

                        form.find(".lp-success").remove();
                        form.find(".lp-errors").remove();
                        if (!respond.success) {
                            form.prepend(respond.error_html);
                        } else {
                            form.prepend(respond.success_html);
                            // form[0].reset();
                            try {
                                $(".rateit", form).rateit("reset");
                            } catch (e) {}
                        }

                        if (ListPlus_Front.debug) {
                            // console.log('here');
                            if (!form.find(".lp-debug-form").length) {
                                form.prepend(
                                    '<div class="lp-debug-form"></div>'
                                );
                            }

                            form.find(".lp-debug-form").html(
                                respond.debug_html
                            );
                        } else {
                            if (respond.redirect_url) {
                                window.location = respond.redirect_url;
                            }
                        }
                    }
                }
            };
            req.send(saveFrom);
        } else {
            form.removeClass("loading");
        }
    });

    $(document).on("click", ".l-review-pagination a", function(e) {
        e.preventDefault();
        var link = $(this).attr("href");
        var p = $(this).closest(".l-review-pagination");
        var id = p.attr("data-id") || false;
        var matches = null;
        if (link.indexOf("/r_paged/") > 0) {
            matches = link.match(/r_paged\/(\d+)/);
        } else if (link.indexOf("/reviews/") > 0) {
            matches = link.match(/reviews\/(\d+)/);
        } else {
            matches = link.match(/r_paged\=(\d+)/);
        }

        var page = 1;

        if (matches && matches.length) {
            page = matches[1];
        }

        $.ajax({
            url: ListPlus_Front.ajax_url,
            type: "GET",
            dataType: "html",
            data: {
                action: "listplus_load_reviews",
                page: page,
                listing_id: id,
                link: link
            },
            success: function(res) {
                var $res = $(res);
                $(".l-reviews-wrapper").replaceWith($res);
                $("div.rateit, span.rateit", $res).rateit();
            }
        });
    });

    $(document).on("click", ".lm-trigger", function(e) {
        e.preventDefault();
        var button = $(this);
        var trigger = button.attr("data-selector");
        var modal = $(".l-modal" + trigger);
        modal.toggleClass("open");
        if (modal.hasClass("open")) {
            $("body").addClass("lm-b-opening");
        } else {
            $("body").removeClass("lm-b-opening");
        }
    });

    $(document).on("click", ".lm-close, .lm-drop", function(e) {
        e.preventDefault();
        var modal = $(this).closest(".l-modal");
        modal.removeClass("open");
        $("body").removeClass("lm-b-opening");
    });

    /** FIlter  */
    var userAction = false;
    function getNavigator() {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                console.log("GetLOc_HERE");
                $("input.l-your-loaction").val(lat + "," + lng);
                if (userAction) {
                    $(".ls-search.l-where").val(ListPlus_Front.current_loc_txt);
                    if (userAction) {
                        //  $(".ls-search.l-where").trigger("change");

                        $("form.l-filters")
                            .eq(0)
                            .submit();
                    }
                }
            },
            function(positionError) {
                $(".ls-search.l-where").val("");
                $("input.l-your-loaction").val("");
                try {
                    if (positionError.code == PositionError.PERMISSION_DENIED) {
                        if (userAction) {
                            alert("Error: Permission Denied!");
                        }
                    } else if (
                        positionError.code == PositionError.POSITION_UNAVAILABLE
                    ) {
                        if (userAction) {
                            alert("Error: Position Unavailable!");
                        }
                    } else if (positionError.code == PositionError.TIMEOUT) {
                        if (userAction) {
                            alert("Error: Timeout!");
                        }
                    }
                } catch (e) {
                    if (userAction) {
                        alert(positionError.message);
                    } else {
                        console.log(positionError.message);
                    }
                }
            }
        );
    }
    // Auto get current location.
    // getNavigator();

    if ($("input.l-your-loaction").val()) {
        $(".ls-search.l-where").val(ListPlus_Front.current_loc_txt);
    }

    $(document).on("change", ".ls-search.l-where", function() {
        $("input.l-your-loaction").val("");
    });

    var fdd_labels = function(dd) {
        labels = [];
        if (!dd.data("text")) {
            dd.data("text", $(".l-quick-label", dd).text());
        }
        $(".l-checkbox input:checkbox:checked", dd).each(function() {
            var p = $(this).parent();
            var span = p.find(".cb-name");
            var name = span.text();
            labels.push(name);
        });

        if ($('input[name="l_min"]', dd).length) {
            var min = $('input[name="l_min"]', dd).attr("data-label");
            if (min) {
                labels.push(min);
            }
        }

        if ($('input[name="l_max"]', dd).length) {
            var max = $('input[name="l_max"]', dd).attr("data-label");
            if (max) {
                labels.push(max);
            }
        }

        if (labels.length) {
            dd.addClass("selected");
            $(".l-quick-label", dd)
                .addClass("selected")
                .html(labels.join(" - "));
        } else {
            dd.removeClass("selected");
            $(".l-quick-label", dd)
                .removeClass("selected")
                .html(dd.data("text"));
        }
    };

    // When focus main input.
    $(document)
        .on("focus search", ".lf-main-f .l-visible", function() {
            var mf = $(this).closest(".lf-main-f");
            var mainInput = $(".l-visible", mf);
            mainInput.attr("placeholder", mainInput.attr("data-placeholder"));
            $(".lf-main-f").removeClass("active");
            mf.addClass("active");
            var p = mainInput.parent();
            if (!mainInput.val()) {
                $(".l-builtin-hidden", p).val("");
            }
        })
        .on("keyup", function(e) {
            var $input = $(e.target);
            var p = $input.parent();
            $(".l-builtin-hidden", p).val("");
        })
        .on("change", function() {
            var $input = $(this);
            var p = $input.parent();
            $(".l-builtin-hidden", p).val("");
        });

    $(document).on("click", ".l-main-dropdown a", function(e) {
        e.preventDefault();
        var $a = $(this);
        var mf = $(this).closest(".lf-main-f");
        var mainInput = $(".l-visible", mf);
        var form = $(this).closest("form");
        var text = $a.attr("title") || $a.text().trim();
        if ($a.hasClass("builtin")) {
            $(".l-builtin-hidden", mf).val($a.attr("data-value"));
            mainInput.val(text).trigger("l_change");
        } else {
            $(".l-builtin-hidden", mf).val("");
            mainInput.val(text).trigger("l_change");
        }

        mf.removeClass("active");
        if (!form.hasClass("l-filters-all")) {
            form.submit();
        }
    });

    // Add current location.
    $(document).on("click", ".l-ask-location", function(e) {
        e.preventDefault();
        userAction = true;
        getNavigator();
    });

    // When click ouside main input.
    $(document).on("click", function(e) {
        var mf = $(e.target).closest(".lf-main-f ");
        if (!mf || !mf.length) {
            $(".lf-main-f ").removeClass("active");
        } else {
            if (!$(e.target).is(mf) && mf.has(e.target).length === 0) {
                mf.removeClass("active");
            }
        }
    });

    // When press delete in where input.
    $(document).on("keydown", "input.l-where", function(e) {
        if (e.which === 8) {
            // Delete key.
            var v = $(this).val();
            if (v === ListPlus_Front.current_loc_txt) {
                $(this)
                    .val("")
                    .trigger("change");
            }
        }
    });

    // Filter Quick dropdown.
    $(document).on("l_change", ".l-quick-dropdown input", function() {
        var dd = $(this).closest(".l-quick-dropdown");
        fdd_labels(dd);
    });
    $(".l-quick-dropdown input").trigger("l_change");

    // When click on quick dropdown button.
    $(document).on("click", "form.l-filters-more .l-q-btn", function() {
        $(this)
            .closest("form")
            .submit();
    });

    // Filter all.
    $(document).on("click", ".lf-quick .l-filter-all", function(e) {
        e.preventDefault();
        var $a = $(this);
        var filerForm = $a.closest(".l-filters");
        $a.toggleClass("selected");
        filerForm.find(".lf-subs").toggleClass("active");
    });

    // Filter check.
    $(document).on(
        "change l_change",
        ".l-quick-btn input:checkbox",
        function() {
            if ($(this).is(":checked")) {
                $(this)
                    .parent()
                    .addClass("selected");
            } else {
                $(this)
                    .parent()
                    .removeClass("selected");
            }
        }
    );

    $(".l-quick-btn input:checkbox").trigger("l_change");
    $(
        ".lm-filter-atts input, .lm-filter-atts select, .l-quick-dropdown input"
    ).addClass("no-submit");

    /**
     * When filter form submit
     * Colect all data from other filter form.
     */
    $(document).on("submit", "form.l-filters", function(e) {
        var $currentForm = $(this);
        var action = $currentForm.attr("action") || "";
        if (action) {
            // Check current window location.
            if (window.location.toString().indexOf(action) < 0) {
                return true;
            }
        }

        e.preventDefault();

        $(".l-modal.lm-filter-atts").removeClass("open");
        $("body").removeClass("lm-b-opening");
        $("form.l-filters").addClass("loading");
        $("#l-listings").addClass("loading");

        var submitFormData = new FormData($currentForm[0]);
        var $otherForm = false;
        // Current main form.
        if ($currentForm.hasClass("l-filters-main")) {
            if ($("form.l-filters.l-filters-more").length) {
                $otherForm = $("form.l-filters.l-filters-more").eq(0);
            }
        } else if ($currentForm.hasClass("l-filters-more")) {
            if ($("form.l-filters.l-filters-main").length) {
                $otherForm = $("form.l-filters.l-filters-main").eq(0);
            }
        }

        if ($otherForm) {
            var formData = new FormData($otherForm[0]);
            for (var pair of formData.entries()) {
                submitFormData.append(pair[0], pair[1]);
            }
        }

        submitFormData.append("link", ListPlus_Front.listings_url);
        submitFormData.append("action", "ajax_filter_listings");
        var req = new XMLHttpRequest();
        req.open("POST", ListPlus_Front.ajax_url, true);
        req.onload = function() {
            if (req.readyState === req.DONE) {
                $("form.l-filters").removeClass("loading");
                $("#l-listings").removeClass("loading");
                if (req.status == 200) {
                    var respond = JSON.parse(req.response);
                    var $html = $(respond.html);
                    $list = $html.find(".l-listings-wrapper");
                    $(".l-listings-wrapper").replaceWith($list);

                    // Sync data between form.
                    if ($currentForm.hasClass("l-filters-main")) {
                        $(".l-filters.l-filters-main")
                            .not($currentForm)
                            .html(respond.main_form);
                        $(".l-filters.l-filters-more").html(respond.more_form);
                        $(".l-filters.l-filters-all")
                            .find(".lf-main")
                            .replaceWith(respond.main_form);
                        $(".l-filters.l-filters-all")
                            .find(".lf-more-wrapper")
                            .replaceWith(respond.more_mobile_form);
                    } else if ($currentForm.hasClass("l-filters-more")) {
                        $(".l-filters.l-filters-main").html(respond.main_form);
                        $(".l-filters.l-filters-more").html(respond.more_form);
                        $(".l-filters.l-filters-all")
                            .find(".lf-main")
                            .replaceWith(respond.main_form);
                        $(".l-filters.l-filters-all")
                            .find(".lf-more-wrapper")
                            .replaceWith(respond.more_mobile_form);
                    } else {
                        $(".l-filters.l-filters-main").html(respond.main_form);
                        $(".l-filters.l-filters-more").html(respond.more_form);

                        $(".l-filters.l-filters-all")
                            .find(".lf-main")
                            .replaceWith(respond.main_form);
                        $(".l-filters.l-filters-all")
                            .find(".lf-more-wrapper")
                            .replaceWith(respond.more_mobile_form);
                    }

                    $(".l-quick-dropdown input").trigger("l_change");
                    $(".l-quick-btn input:checkbox").trigger("l_change");
                    $("div.rateit, span.rateit", $list).rateit();
                    // console.log(respond.url);
                    $(
                        ".lm-filter-atts input, .lm-filter-atts select, .l-quick-dropdown input"
                    ).addClass("no-submit");

                    $(document).trigger("listings_filter_done", [respond]);

                    try {
                        history.pushState(
                            respond.url,
                            respond.title,
                            respond.url
                        );
                    } catch (e) {}
                } else {
                }
            }
        };
        req.send(submitFormData);
    });

    $(document).on(
        "change",
        "form.l-filters-more input, form.l-filters-more select",
        function() {
            if (!$(this).hasClass("no-submit")) {
                $("form.l-filters")
                    .eq(0)
                    .submit();
            }
        }
    );

    // Mobile Filter
    $(document).on("click", ".l-trigger-filter", function(e) {
        e.preventDefault();
        $("#l-mobile-filter").toggleClass("mobile-active");
    });
    // Cancel filter
    $(document).on("click", "#l-mobile-filter .l-btn-secondary", function(e) {
        e.preventDefault();
        $("#l-mobile-filter").removeClass("mobile-active");
    });
    // Search
    $(document).on("click", "#l-mobile-filter .l-btn-primary", function(e) {
        e.preventDefault();
        $("#l-mobile-filter form").submit();
        $("#l-mobile-filter").removeClass("mobile-active");
    });

    // Modal ------------------
    var modalOpeningURL = false;
    // Close builder modal.
    $(document).on("click", ".lm-close", function(e) {
        e.preventDefault();
        $(this)
            .closest(".l-modal")
            .removeClass("active");
        $("body").removeClass("l-modal-open");

        if (modalOpeningURL) {
            modalOpeningURL = false;
            window.history.pushState("", "", ListPlus_Front.current_permalink);
        }
    });

    $(document).on("keydown", function(e) {
        if (e.which === 27) {
            // ESC button
            $(".l-modal").removeClass("active");
            $("body").removeClass("l-modal-open");
            if (modalOpeningURL) {
                modalOpeningURL = false;
                window.history.pushState(
                    "",
                    "",
                    ListPlus_Front.current_permalink
                );
            }
        }
    });

    $(document).on("click", ".l-modal", function(e) {
        // The element.
        var modal = $(this);
        var container = modal.find(".l-modal-inner");

        //Click to out side modal.
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            $(".l-modal").removeClass("active");
            $("body").removeClass("l-modal-open");
            if (modalOpeningURL) {
                modalOpeningURL = false;
                window.history.pushState(
                    "",
                    "",
                    ListPlus_Front.current_permalink
                );
            }
        }
    });

    // Open modal
    $(document).on("click", ".l-toggle-modal", function(e) {
        e.preventDefault();
        var a = $(this);
        var selector = a.attr("data-selector") || false;
        var url = a.attr("href") || false;
        if (selector) {
            $(selector).toggleClass("active");
            if ($(selector).hasClass("active")) {
                $("body").addClass("l-modal-open");
                if (url) {
                    modalOpeningURL = url;
                    window.history.pushState("", "", url);
                }
            } else {
                $("body").removeClass("l-modal-open");
                modalOpeningURL = false;
                window.history.pushState(
                    "",
                    "",
                    ListPlus_Front.current_permalink
                );
            }
        }
    });

    // When page load.
    if (ListPlus_Front.current_action) {
        $(".l-toggle-modal.act-" + ListPlus_Front.current_action)
            .eq(0)
            .click();
    }

    // End Modal --------------

    
/* Premium Code Stripped by Freemius */

}); // End document ready.
