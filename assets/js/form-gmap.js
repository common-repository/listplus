(function($) {
    /**
     * @see https://developers-dot-devsite-v2-prod.appspot.com/maps/documentation/javascript/examples/places-autocomplete-hotelsearch
     * @see https://developers-dot-devsite-v2-prod.appspot.com/maps/documentation/javascript/examples/places-autocomplete
     */

    //------------------------------------------------------
    var Map = function($el) {
        this.marker = false;
        this.$el = $el;
        this.lat = 0;
        this.lng = 0;
        this.address = false;

        this.mapEl = $el.find('.lp-map')[0] || false;
        this.$search = $el.find('.f-address');
        this.search = this.$search[0] || false;

        this.$inputLat = $el.find('.lat-val');
        this.$inputLng = $el.find('.lng-val');
        this.$inputZipcode = $el.find('.zipcode-val');
        this.$inputCity = $el.find('.city-val');
        this.$inputState = $el.find('.state-val');
        this.$inputCountry = $el.find('.country-val');

        var lat = this.$inputLat.val();
        var lng = this.$inputLng.val();
        this.address = this.$search.val();
        if (lat) {
            lat = parseFloat(lat);
        }
        if (lng) {
            lng = parseFloat(lng);
        }

        this.lat = lat || 38.86664205612048;
        this.lng = lng || -77.11638246823065;
        console.log(  this.lat, this.lng  );

        this.initMap($el);
    };

    Map.prototype.initMap = function($el) {
        if (!this.mapEl) {
            return;
        }

        var that = this;

        var map = new google.maps.Map(this.mapEl, {
            center: { lat: that.lat, lng: that.lng },
            zoom: 13
        });

        // map.controls[google.maps.ControlPosition.TOP_RIGHT].push(this.mapInputWrapper);

        var autocomplete = new google.maps.places.Autocomplete(this.search);

        // Bind the map's bounds (viewport) property to the autocomplete object,
        // so that the autocomplete requests use the current map bounds for the
        // bounds option in the request.
        autocomplete.bindTo('bounds', map);

        // Set the data fields to return when the user selects a place.
        autocomplete.setFields([ 'address_components', 'geometry', 'icon', 'name' ]);
        autocomplete.setTypes([ 'address' ]);

        var infowindow = new google.maps.InfoWindow();
        infowindow.setContent('');
        that.marker = new google.maps.Marker({
            map: map,
            anchorPoint: new google.maps.Point(0, -29),
            animation: google.maps.Animation.DROP,
            draggable: true
        });

        that.marker.addListener('click', function() {
            infowindow.open(map, that.marker);
        });

        var setInfoWindow = function(marker, place) {
            that.$inputLat.val(marker.position.lat());
            that.$inputLng.val(marker.position.lng());
            var infoContent =
                '<div class="infowindow-content">\
                        <span class="place-name" class="title"></span><br>\
                        <span class="place-address"></span>\
                    </div>';

            var $infoContent = $(infoContent);
            $infoContent.find('.place-name').text(place.name);
            $infoContent.find('.place-address').text(place.address);
            infowindow.setContent($infoContent[0]);
            infowindow.open(map, marker);
        };

       //  console.log( {lat: that.lat, lng: that.lng } );

        if (that.address) {
            that.marker.setVisible(true);
            that.marker.setPosition( { lat: that.lat, lng: that.lng });
            setInfoWindow(that.marker, { name: that.address, address: '' });
        } else {
            that.marker.setVisible(false);
        }

        google.maps.event.addListener(that.marker, 'dragend', function() {
            that.$inputLat.val(that.marker.position.lat());
            that.$inputLng.val(that.marker.position.lng());
        });

        autocomplete.addListener('place_changed', function() {
            infowindow.close();
            that.marker.setVisible(false);
            var place = autocomplete.getPlace();
            if (!place.geometry) {
                // User entered the name of a Place that was not suggested and
                // pressed the Enter key, or the Place Details request failed.
                window.alert("No details available for input: '" + place.name + "'");
                return;
            }

            // If the place has a geometry, then present it on a map.
            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17); // Why 17? Because it looks good.
            }
            that.marker.setPosition(place.geometry.location);
            that.marker.setVisible(true);

            var address = '';
            var countryCode = '';
            var postalCode = '';
            var city = '';
            if (place.address_components) {
                console.log('place.address_components', place.address_components);
                address = [
                    (place.address_components[0] && place.address_components[0].short_name) || '',
                    (place.address_components[1] && place.address_components[1].short_name) || '',
                    (place.address_components[2] && place.address_components[2].short_name) || ''
                ].join(' ');

                $.each(place.address_components, function(key, cargs) {
                    if (cargs.types.indexOf('country') > -1) {
                        countryCode = cargs.short_name;
                    }
                    if (cargs.types.indexOf('postal_code') > -1) {
                        postalCode = cargs.short_name;
                    }
                    if (cargs.types.indexOf('administrative_area_level_1') > -1) {
                        city = cargs.long_name;
                    }
                });
            }

            // Set country code
            if (countryCode) {
                try {
                    that.$inputCountry.val(countryCode).trigger('change');
                } catch (e) {}
            }

            if (postalCode) {
                that.$inputZipcode.val(postalCode).trigger('change');
            }
            if (city) {
                that.$inputCity.val(city).trigger('change');
            }

            setInfoWindow(that.marker, place);
        });

        google.maps.event.addListener(map, 'click', function(event) {
            if (!that.marker.getVisible()) {
                that.marker.setPosition(event.latLng);
                that.marker.setVisible(true);
                var name = that.$search.val() || 'Untitled';
                setInfoWindow(that.marker, { name: name, address: '' });
            }
        });
    }; // end init method.

    //------------------------------------------------------
    $.fn.MapForm = function() {
        return new Map(this);
    };
})(jQuery);
