(function(){
  'use strict';

  L.mapbox.accessToken = tqor.mapboxApi;
  window.map = L.mapbox.map('map').fitBounds([[50.79204, -97.20703],[15.02968, -125.77148]], {animate: true, padding: [10, 10]});
  L.mapbox.styleLayer(tqor.mapboxStyle).addTo(map);

  // @TODO: NOTE! THIS CALLBACK IS EXECUTED FOR EACH LOAD at the moment. It's
  // original usecase was just to check dates anyway, but we need a way to call
  // loadAllTrips's callback _after_ all of forEach(laodTrip()) is done.
  var loadAllTrips = function(tripsToLoad, callback) {
    tripsToLoad = (Array.isArray(tqor.trips_with_content) && tripsToLoad.length) ? tripsToLoad : false;
    callback = callback || false;

    // If we don't have a list of trips to load provided by WordPress, request them all.
    if (!tripsToLoad) {
      // Request from the location server API all of the Trips in the database.
      var xhr = new XMLHttpRequest();
      xhr.open('GET', tqor.locationApi + '/api/trips');
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.onload = function () {
        if (xhr.status === 200) {
          var response = JSON.parse(xhr.responseText);
          // For every trip identified in the database, fetch its information and
          // add the LineString to the map.
          response.forEach(function (trip) {
            loadTrip(trip.id, callback);
          });
        }
      };
      xhr.send();
    } else {
      tripsToLoad.forEach(function (trip) {
        loadTrip(trip, callback);
      });
    }
  }

  var loadTrip = function(trip_id, callback) {
    callback = callback || false;

    var tripXhr = new XMLHttpRequest();
    tripXhr.open('GET', tqor.locationApi + '/api/trips/' + trip_id);
    tripXhr.setRequestHeader('Content-Type', 'application/json');
    tripXhr.onload = function () {
      if (tripXhr.status === 200) {
        var tripResponse = JSON.parse(tripXhr.responseText);
        window.tqor.trips[trip_id] = tripResponse;
        window.tqor.trips[trip_id].line = L.mapbox.featureLayer(tripResponse.line).addTo(map);

        if (callback) {
          callback(tripResponse);
        }
      }
    };
    tripXhr.send();
  }

  var mapToTimestamp = function(timestamp) {
    if (!window.tqor.cache.hasOwnProperty(timestamp)) {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', tqor.locationApi + '/api/location/history/timestamp/' + timestamp);
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.onload = function () {
        if (xhr.status === 200) {
          var response = JSON.parse(xhr.responseText);
          if (response.hasOwnProperty('lat')) {
            map.setView([response.lat, response.lon], 10);
            window.tqor.cache[timestamp] = [response.lat, response.lon];
          }
        }
      };
      xhr.send();
    }
    else {
      map.setView(window.tqor.cache[timestamp], 10);
    }
    map.invalidateSize();
  }

  var rnfCustomMapControl = L.Control.extend({
    options: {
      position: 'topright',
    },
    onAdd: function (map) {
      var container = L.DomUtil.create('div', 'leaflet-bar rnfmap-custom-controls');
      L.DomEvent.disableClickPropagation(container);

      var closeMapLink = L.DomUtil.create('a', 'icon-close rnfmap-close', container);
      closeMapLink.href = '#';
      closeMapLink.text = 'Close';
      L.DomEvent.addListener(closeMapLink, 'click', this._closeMapClick, this);

      return container;
    },

    _closeMapClick: function (e) {
      L.DomEvent.stop(e);
      map._container.classList.toggle('visible');
    }
  });
  map.addControl(new rnfCustomMapControl());

  var mapJumpLinks = document.querySelectorAll('article a.tqor-map-jump');
  mapJumpLinks.forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      var timestamp = el.getAttribute('data-timestamp');
      mapToTimestamp(timestamp);

      // And add the visible class to the container so it opens on mobile.
      map._container.classList.toggle('visible');
      map.invalidateSize();
    });
  });

  // Decide what to show on the map given what kind of page we're on
  // @TODO: A trip category page is going to need the trip data from above,
  // may need to wrap this in a promise.
  var tripsToLoad = false;

  if (tqor.hasOwnProperty('trips_with_content') && tqor.trips_with_content.length) {
    tripsToLoad = tqor.trips_with_content;
  }

  if (tqor.hasOwnProperty('start')) {
    switch (window.tqor.start.type) {
      case 'trip':
        loadTrip(window.tqor.start.trip_id, function(trip) {
          if (trip.hasOwnProperty('boundaries')) {
            window.map.fitBounds(trip.boundaries, {animate: true, padding: [10, 10]});
          }
        });
        break;
      case 'post':
        if (window.tqor.start.hasOwnProperty('trip_id')) {
          loadTrip(window.tqor.start.trip_id, function(trip) {
            if (trip.hasOwnProperty('boundaries')) {
              window.map.fitBounds(trip.boundaries, {animate: true, padding: [10, 10]});
            }
          });
        } else {
          loadAllTrips(tripsToLoad);
        }
        mapToTimestamp(window.tqor.start.timestamp);
        // @TODO: On a post-only page, it'd be great to only load the trip it was on?
        break;
      default:
        var currentTimestamp = Date.now() / 1000;
        loadAllTrips(tripsToLoad, function(tripResponse){
          if (tripResponse.starttime <= currentTimestamp && currentTimestamp <= tripResponse.endtime) {
            // This trip is happening now, zoom to it.
            if (tripResponse.hasOwnProperty('boundaries')) {
              window.map.fitBounds(tripResponse.boundaries, {animate: true, padding: [10, 10]});
            }
          }
        });
        // @TODO: Where should we center the map in this case?
    }
  }


})();
