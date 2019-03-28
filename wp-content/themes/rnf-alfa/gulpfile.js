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

const gulp = require('gulp');
const gutil = require('gulp-util');

const exec = require('child_process').exec;
const fs = require('fs');
const imagemin = require('gulp-imagemin');
const resize = require('gulp-image-resize');

gulp.task('header-images-fetch', (cb) => {
  exec('mkdir -p sources/img/headers/original/'); // Make sure the image download directory exists
  exec('mkdir -p dist/css dist/js'); // Make sure the output directories exist
  exec('AWS_CREDENTIAL_FILE=~/.aws/credentials && s3cmd get s3://routenotfound-assets/header_images/* sources/img/headers/original/', () => {
    const headerImages = [];
    const outputCSS = [];
    const outputList = [];

    const directory = fs.readdirSync('sources/img/headers/original/');

    for (var i = 0; i < directory.length; i++) {
      const file = directory[i];
      headerImages.push(file.replace(/\.\w{3}/, ''));
    }

    headerImages.forEach((filename, index) => {
      outputCSS.push('.header-' + filename + ' { background-image: url("../img/headers/SIZE/' + filename + '.jpg"); }');
      outputList.push('header-' + filename);
    });

    fs.writeFileSync('dist/js/header-images.js', [
      '(function(){',
      '  \'use strict\';',
      '  document.addEventListener("DOMContentLoaded", function() {',
      '    var headerImages = ' + JSON.stringify(outputList),
      '    var selectedImage = headerImages[Math.floor(Math.random() * headerImages.length)];',
      '    document.querySelector(\'.custom-header\').classList.add(selectedImage);',
      '  });',
      '})();'
    ].join('\n'));

    fs.writeFileSync('dist/css/header-images.css', [
      outputCSS.join('\n').replace(/SIZE/g, 'tiny'),
      ("@media (min-width: 480px) {" + outputCSS.join('\n').replace(/SIZE/g, 'narrow') + "}"),
      ("@media (min-width: 960px) {" + outputCSS.join('\n').replace(/SIZE/g, 'medium') + "}"),
      ("@media (min-width: 1280px) {" + outputCSS.join('\n').replace(/SIZE/g, 'wide') + "}"),
    ].join('\n'));

    cb();
  });
});

gulp.task('header-images-sizes', () => {
  return gulp.src('sources/img/headers/original/*.jpg')
    .pipe(imagemin([imagemin.jpegtran({progressive: true})]))
    .pipe(gulp.dest('dist/img/headers/wide/'))
    .pipe(resize({width: 1280, height: 1280, crop: false, upscale: false, quality: 1}))
    .pipe(imagemin([imagemin.jpegtran({progressive: true})]))
    .pipe(gulp.dest('dist/img/headers/medium/'))
    .pipe(resize({width: 720, height: 720, crop: false, upscale: false, quality: 1}))
    .pipe(imagemin([imagemin.jpegtran({progressive: true})]))
    .pipe(gulp.dest('dist/img/headers/narrow/'))
    .pipe(resize({width: 360, height: 360, crop: false, upscale: false, quality: 1}))
    .pipe(imagemin([imagemin.jpegtran({progressive: true})]))
    .pipe(gulp.dest('dist/img/headers/tiny/'));
});

gulp.task('build', gulp.series('header-images-fetch', 'header-images-sizes'));
