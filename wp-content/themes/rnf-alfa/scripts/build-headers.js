'use strict';

/**
 * @file build-headers.js
 *
 * (TODO) Fetch RNF header images from the S3 bucket they live in,
 * (TODO) optimize them for web display across a few screen sizes,
 * generate a CSS index that matches class names to actual images, and
 * output a JS file that WordPress can include which automatically assigns one
 * on DOMContentLoaded.
 */

const fs = require('fs');

const buildList = (path) => {
  const items = [];
  const directory = fs.readdirSync(path);
  for (var i = 0; i < directory.length; i++) {
    const file = directory[i];
    items.push(file.replace(/\.\w{3}/, ''));
  }

  return items;
}

const headerImages = buildList('../img');
const outputCSS = [];
const outputList = [];

headerImages.forEach((filename, index) => {
  outputCSS.push('.header-' + filename + ' { background-image: url("img/' + filename + '.jpg"); }');
  outputList.push('header-' + filename);
});

fs.writeFileSync('../js/header-images.js', [
  '(function(){',
  '  \'use strict\';',
  '  document.addEventListener("DOMContentLoaded", function() {',
  '    var headerImages = ' + JSON.stringify(outputList),
  '    var selectedImage = headerImages[Math.floor(Math.random() * headerImages.length)];',
  '    document.querySelector(\'.custom-header\').classList.add(selectedImage);',
  '  });',
  '})();'
].join('\n'));

fs.writeFileSync('../header-images.css', outputCSS.join('\n'));
