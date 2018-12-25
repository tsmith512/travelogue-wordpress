(function(){
  'use strict';

  L.mapbox.accessToken = tqor.mapboxApi;
  window.map = L.mapbox.map('map').fitBounds([[34.4,-100.0],[36.8,-96.2]], {animate: true, padding: [30, 30]});
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

  var mapDotTimestamp = function(timestamp) {
    if (window.tqor.cache.hasOwnProperty(timestamp)) { return; }

    // @TODO: DRY this up a little, it repeats entirely from above.
    if (!window.tqor.cache.hasOwnProperty(timestamp)) {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', tqor.locationApi + '/api/location/history/timestamp/' + timestamp);
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.onload = function () {
        if (xhr.status === 200) {
          var response = JSON.parse(xhr.responseText);
          console.log(response);
          if (response.hasOwnProperty('lat')) {
            window.tqor.markers[timestamp] = L.circle([response.lat, response.lon], {color: 'black', radius: 300}).addTo(map);
            window.tqor.cache[timestamp] = [response.lat, response.lon];
          }
        }
      };
      xhr.send();
    }
    else {
      window.tqor.markers[timestamp] = L.circle(window.tqor.cache[timestamp], {color: 'black', radius: 300}).addTo(map);
    }
  }

  window.setInterval(function(){
    jQuery('article:onScreen').each(function(i, el){
      mapDotTimestamp(jQuery('.tqor-map-jump', el).attr('data-timestamp'));
    });
  }, 1000);

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
