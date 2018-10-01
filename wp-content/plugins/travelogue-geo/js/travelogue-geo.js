(function(){
  'use strict';

  L.mapbox.accessToken = tqor.mapboxApi;
  window.map = L.mapbox.map('map').fitBounds([[34.4,-100.0],[36.8,-96.2]], {animate: true, padding: [30, 30]});
  L.mapbox.styleLayer(tqor.mapboxStyle).addTo(map);

  // Request from the location server API all of the Trips in the database.
  var xhr = new XMLHttpRequest();
  xhr.open('GET', tqor.locationApi + '/api/trips');
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.onload = function () {
    if (xhr.status === 200) {
      var response = JSON.parse(xhr.responseText);
      window.tqor.tripLines = [];
      // For every trip identified in the database, fetch its information and
      // add the LineString to the map.
      response.forEach(function (trip) {
        var tripXhr = new XMLHttpRequest();
        tripXhr.open('GET', tqor.locationApi + '/api/trips/' + trip.id);
        tripXhr.setRequestHeader('Content-Type', 'application/json');
        tripXhr.onload = function () {
          if (tripXhr.status === 200) {
            var tripResponse = JSON.parse(tripXhr.responseText);
            window.tqor.tripLines[trip.id] = L.mapbox.featureLayer(tripResponse.line).addTo(map);
          }
        };
        tripXhr.send();
      });
    }
  };
  xhr.send();
})();