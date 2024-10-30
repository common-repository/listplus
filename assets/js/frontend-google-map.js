(function($) {
    var iconSize = [30, 30]; // x, y
    var MapStyle = [
        {
            featureType: "administrative",
            elementType: "geometry",
            stylers: [
                {
                    visibility: "off"
                }
            ]
        },
        {
            featureType: "poi",
            stylers: [
                {
                    visibility: "off"
                }
            ]
        },
        {
            featureType: "transit",
            stylers: [
                {
                    visibility: "off"
                }
            ]
        }
    ];

    $.fn.ListingMap = function(options) {
        

        var Map = function($el) {
            var that = this;
            this.map = null;
            this.markers = [];
            this.infowindows = [];

            this.iconNormal = {
                url: ListPlus_Front.marker_icons.red, // url
                scaledSize: new google.maps.Size(iconSize[0], iconSize[1]), // Scaled size
                origin: new google.maps.Point(0, 0), // origin
                anchor: new google.maps.Point(iconSize[0] / 2, iconSize[1]) // Anchor.
            };

            this.iconHover = {
                url: ListPlus_Front.marker_icons.blue, // url
                scaledSize: new google.maps.Size(iconSize[0], iconSize[1]), // scaled size
                origin: new google.maps.Point(0, 0), // origin
                anchor: new google.maps.Point(iconSize[0] / 2, iconSize[1]) // anchor
            };

            var HtmlElement = $el[0];

            that.map = new google.maps.Map(HtmlElement, {
                center: { lat: 59.325, lng: 18.07 },
                styles: MapStyle,
            });

            google.maps.event.addListener(that.map, "tilesloaded", function() {
                // do something only the first time the map is loaded
                $el.trigger("map_ready");
            });

            that.addMarkers(ListPlus_Front.markers);
            $(document).on("listings_filter_done", function(e, respond) {
                that.addMarkers(respond.markers);
            });

            $(document).on("mouseover", ".l-listings .l-loop-item", function(
                e
            ) {
                var index = $(this).attr("data-index");
                index = parseInt(index);
                for (var i = 0; i < that.markers.length; i++) {
                    if (index !== i) {
                        that.markers[i].setIcon(that.iconNormal);
                        that.markers[i].setZIndex(10 + i);
                        that.infowindows[i].close();
                    }
                }
                that.markers[index].setZIndex(index + 230);
                that.markers[index].setIcon(that.iconHover);
                that.markers[index].setAnimation(google.maps.Animation.BOUNCE);

                // that.map.setCenter(that.markers[index].position);
            });

            $(document).on("mouseout", ".l-listings .l-loop-item", function(e) {
                var index = $(this).attr("data-index");
                index = parseInt(index);
                that.markers[index].setIcon(that.iconNormal);
                that.infowindows[index].close();
                that.markers[index].setAnimation(null);

                for (var i = 0; i < that.markers.length; i++) {
                    that.markers[i].setZIndex(10 + i);
                }
            });
        };

        Map.prototype.removeMarkers = function() {
            if (this.markers.length) {
                for (var i = 0; i < this.markers.length; i++) {
                    this.markers[i].setMap(null);
                    this.infowindows[i].close();
                }
            }
            // Reset the maker list.
            this.markers.length = 0;
            // Reset the infowindow list.
            this.infowindows.length = 0;
        };

        Map.prototype.addMarkers = function(markers) {
            var that = this;
            //create empty LatLngBounds object
            var bounds = new google.maps.LatLngBounds();
            that.removeMarkers();

            $.each(markers, function(index, location) {
                var contentString =
                    '<div class="l-map-info">\
                    <div class="l-map-in">\
                        <div class="l-info-thumbnail">' +
                    location.thumbnail_html +
                    "</div>\
                        <h3>" +
                    location.title +
                    "</h3>\
                    </div>\
                </div>";

                var infowindow = new google.maps.InfoWindow({
                    content: contentString
                });

                var marker = new google.maps.Marker({
                    animation: google.maps.Animation.DROP,
                    position: location.loc,
                    map: that.map,
                    //title: location.title,
                    icon: that.iconNormal
                });

                marker.addListener("click", function() {
                    // infowindow.open(map, marker);
                    // marker.setIcon(iconHover);
                    window.location = location.url;
                });

                marker.addListener("mouseover", function() {
                    for (var i = 0; i < that.infowindows.length; i++) {
                        if (index !== i) {
                            marker.setIcon(that.iconNormal);
                            infowindow.close();
                        }
                    }
                    marker.setIcon(that.iconHover);
                    infowindow.open(that.map, marker);
                });

                marker.addListener("mouseout", function() {
                    marker.setIcon(that.iconNormal);
                    infowindow.close();
                });

                that.markers.push(marker);
                that.infowindows.push(infowindow);

                //extend the bounds to include each marker's position
                bounds.extend(marker.position);
            });

            //now fit the map to the newly inclusive bounds
            that.map.fitBounds(bounds);
        };

        Map.prototype.setAnimation = function(marker) {};

        // This is the easiest way to have default options.
        var settings = $.extend(
            {
                // These are the defaults.
                zoom: 13,
                center: { lat: 59.325, lng: 18.07 },
                color: "#556b2f"
            },
            options
        );

        // Greenify the collection based on the settings variable.
        return this.each(function() {
            var $el = $(this);
            new Map($el);
        });
    };

    /**
     * @see https://developers-dot-devsite-v2-prod.appspot.com/maps/documentation/javascript/examples/places-autocomplete-hotelsearch
     * @see https://developers-dot-devsite-v2-prod.appspot.com/maps/documentation/javascript/examples/places-autocomplete
     */

    //------------------------------------------------------
    var SingleMap = function($el) {
        this.lat = 0;
        this.lng = 0;
        this.mapEl = $el[0] || false;

        var mapData = $el.data("map") || {};
        mapData = Object.assign(
            {
                lat: 0,
                lng: 0,
                address: ""
            },
            mapData
        );

        this.marker = false;
        this.$el = $el;
        var lat = mapData.lat;
        var lng = mapData.lng;
        this.address = mapData.address;
        if (lat) {
            lat = parseFloat(lat);
        }
        if (lng) {
            lng = parseFloat(lng);
        }
        this.lat = lat || 38.86664205612048;
        this.lng = lng || -77.11638246823065;
        this.initMap($el);
    };

    SingleMap.prototype.initMap = function($el) {
        if (!this.mapEl) {
            return;
        }

        var that = this;

        var map = new google.maps.Map(this.mapEl, {
            center: { lat: that.lat, lng: that.lng },
            zoom: 13,
            styles: MapStyle,
        });

        var infowindow = new google.maps.InfoWindow();
        var infoContent =
            '<div class="infowindow-content">\
                    <span class="place-address"></span>\
                </div>';

        var $infoContent = $(infoContent);
        $infoContent.find(".place-address").text(that.address);
        infowindow.setContent($infoContent[0]);

        that.marker = new google.maps.Marker({
            map: map,
            title: that.address,
            animation: google.maps.Animation.DROP,
            position: { lat: that.lat, lng: that.lng },
            icon: {
                url: ListPlus_Front.marker_icons.red, // url
                scaledSize: new google.maps.Size(iconSize[0], iconSize[1]), // Scaled size
                origin: new google.maps.Point(0, 0), // origin
                anchor: new google.maps.Point(iconSize[0] / 2, iconSize[1]) // Anchor.
            }
        });

        that.marker.setVisible(true);
        that.marker.addListener("click", function() {
            infowindow.open(map, that.marker);
        });
    }; // end init method.

    //------------------------------------------------------
    $.fn.SingleMap = function() {
        return this.each(function() {
            return new SingleMap($(this));
        });
    };
})(jQuery);
