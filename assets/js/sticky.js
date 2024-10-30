(function($) {
    $.fn.StickyMap = function(options) {
        
        var Sticky = function($el) {
            var scope = this;
            scope.$el = $el;
            scope.isSticky = false;
            scope.offset = {};
            scope.width = 0;
            scope.height = 0;

            var $mainList = $("#l-listings");
            if (!$mainList.length) {
                return;
            }

            var stickyType = $el.attr("data-sticky") || "widget"; // sidebar.
            if ("no" === stickyType) {
                return;
            }

            if ($el.hasClass("inside-widget")) {
                var $widget = $el.closest(".widget-listings-map");

                if ("sidebar" === stickyType) {
                    var widgetWrapper = $widget.parent();
                    widgetWrapper.wrapInner(
                        '<div class="map-sticky-inner"></div>'
                    );
                    widgetWrapper.wrapInner(
                        '<div class="map-sticky-wrapper"></div>'
                    );
                    scope.$inner = widgetWrapper.find(".map-sticky-inner");
                    scope.$wrapper = scope.$inner.parent();
                } else {
                    $widget.wrap('<div class="map-sticky-wrapper"></div>');
                    $widget.wrap('<div class="map-sticky-inner"></div>');
                    scope.$inner = $widget.parent();
                    scope.$wrapper = scope.$inner.parent();

                    // Ensure this widget not is last child.
                    $clone = $widget.clone();
                    $clone.html("");
                    $clone.attr("id", $clone.attr("id") + "-clone");
                    $clone.addClass("widget-clone");
                    scope.$inner.append($clone);
                }

                //return;
            } else {
                $el.wrap('<div class="map-sticky-wrapper"></div>');
                $el.wrap('<div class="map-sticky-inner"></div>');
                scope.$inner = $el.parent();
                scope.$wrapper = scope.$inner.parent();
            }

            $el.on("map_ready", function() {
                scope.checkSticky();
            });

            $(document).on("listings_filter_done", function() {
                scope.removeSticky();
                scope.checkSticky();
                $(window).trigger( 'resize' );
            });

            $(window).resize(function(event) {
                scope.removeSticky();
                scope.$wrapper.css({ height: "auto" });
                scope.checkSticky();
            });
        };

        Sticky.prototype.removeSticky = function() {
            this.$wrapper.css({
                height: "auto"
            });
            this.$inner.css({
                width: "auto",
                display: "block",
                position: "relative",
                zIndex: "auto",
                top: "auto"
            });
        };

        Sticky.prototype.checkSticky = function() {
            var scope = this;
            scope.offset = scope.$wrapper.offset();
            scope.width = scope.$wrapper.width();
            scope.height = scope.$wrapper.height();
            windowWidth = $(window).width();
            windowHeight = $(window).height();
            scope.isSticky = false;

            // Left sidebar.
            if (scope.offset.left > scope.width) {
                scope.isSticky = true;
            }

            // Right sidebar
            if (windowWidth - (scope.offset.left + scope.width) > scope.width) {
                scope.isSticky = true;
            }

            var $mainList = $("#l-listings");

            if ( $mainList.height() <= scope.height ) {
                scope.isSticky = false;
            }

            
            var mainOffset = $mainList.offset();
            var mainBottom = mainOffset.top + $mainList.height();

            // If the map bellow or above the listings list then false.
            if (Math.abs(scope.offset.top - mainOffset.top) > scope.height) {
                scope.isSticky = false;
            }

            if (!scope.isSticky) {
                scope.removeSticky();
                return;
            }

            scope.$wrapper.css({
                height: scope.$wrapper.height() + "px",
                display: "block"
            });

            var addt = 0;

            if ($("#wpadminbar").length) {
                addt = $("#wpadminbar").height();
            }

            $(window).scroll(function(event) {
                var scroll = $(window).scrollTop();

                if (scroll + addt > scope.offset.top) {
                    var top = addt;
                    if (scroll + scope.height > mainBottom) {
                        top = -(scroll + scope.height - mainBottom - addt);
                    }

                    if (top < addt) {
                        top = addt;
                    }

                    if (scroll + scope.height > mainBottom) {
                        var diff = ( scroll + scope.height ) - mainBottom;
                        top = - diff;
                    }

                    scope.$inner.css({
                        width: scope.width,
                        display: "block",
                        position: "fixed",
                        zIndex: 699,
                        top: top
                    });
                } else {
                    scope.$inner.css({
                        width: scope.width,
                        display: "block",
                        position: "relative",
                        zIndex: "auto",
                        top: "auto"
                    });
                }
            });

            $(window).trigger("scroll");
        };

        // This is the easiest way to have default options.
        var settings = $.extend(
            {
                // These are the defaults.
            },
            options
        );

        // Greenify the collection based on the settings variable.
        return this.each(function() {
            var $el = $(this);

            new Sticky($el);
        });
    };
})(jQuery);
