'use strict';

/**
 * @file build-headers.js
 *
 * Fetch RNF header images from the S3 bucket they live in,
 * (TODO) optimize them for web display across a few screen sizes,
 * generate a CSS index that matches class names to actual images, and
 * output a JS file that WordPress can include which automatically assigns one
 * on DOMContentLoaded.
 */

const fs = require('fs');
const exec = require('child_process').exec;

exec('mkdir -p ../img/headers/original/');
exec('AWS_CREDENTIAL_FILE=~/.aws/credentials && s3cmd get s3://routenotfound-assets/header_images/* ../img/headers/original/', () => {
  const headerImages = [];
  const outputCSS = [];
  const outputList = [];

  const directory = fs.readdirSync('../img/headers/original/');

  for (var i = 0; i < directory.length; i++) {
    const file = directory[i];
    headerImages.push(file.replace(/\.\w{3}/, ''));
  }

  headerImages.forEach((filename, index) => {
    outputCSS.push('.header-' + filename + ' { background-image: url("img/headers/original/' + filename + '.jpg"); }');
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
});
