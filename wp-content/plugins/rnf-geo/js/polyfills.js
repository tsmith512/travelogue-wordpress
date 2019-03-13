(function(){
  // Polyfill for forEach from MDN at
  // https://developer.mozilla.org/en-US/docs/Web/API/NodeList/forEach
  // I don't particlarly want to get into "old browser support" land
  // on this project, but this is a pretty low effort fix and restores
  // IE 10/11.
  'use strict';
  if (window.NodeList && !NodeList.prototype.forEach) {
    console.log('Adding polyfill for NodeList.forEach');
    NodeList.prototype.forEach = function (callback, thisArg) {
      thisArg = thisArg || window;
      for (var i = 0; i < this.length; i++) {
        callback.call(thisArg, this[i], i, this);
      }
    };
  }
})();
