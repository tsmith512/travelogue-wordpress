(function(){
  'use strict';

  L.mapbox.accessToken = tqor.mapboxApi;
  window.map = L.mapbox.map('map').fitBounds([[34.4,-100.0],[36.8,-96.2]], {animate: true, padding: [30, 30]});
  L.mapbox.styleLayer(tqor.mapboxStyle).addTo(map);

  // @TODO: NOTE! THIS CALLBACK IS EXECUTED FOR EACH LOAD at the moment. It's
  // original usecase was just to check dates anyway, but we need a way to call
  // loadAllTrips's callback _after_ all of forEach(laodTrip()) is done.
  var loadAllTrips = function(callback) {
    callback = callback || false;

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

  var mapJumpLinks = document.querySelectorAll('article a.tqor-map-jump');
  mapJumpLinks.forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      var timestamp = el.getAttribute('data-timestamp');
      mapToTimestamp(timestamp);
    });
  });

  // Decide what to show on the map given what kind of page we're on
  // @TODO: A trip category page is going to need the trip data from above,
  // may need to wrap this in a promise.
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
        loadAllTrips();
        mapToTimestamp(window.tqor.start.timestamp);
        // @TODO: On a post-only page, it'd be great to only load the trip it was on?
        break;
      default:
        var currentTimestamp = Date.now() / 1000;
        loadAllTrips(function(tripResponse){
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
