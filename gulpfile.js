const { src, dest, series, parallel } = require('gulp');
const del = require('del');
const zip = require('gulp-zip');
const fs = require('fs');
const path = require('path');

// Check if file exists
function fileExists(filePath) {
    try {
        return fs.existsSync(filePath);
    } catch (err) {
        return false;
    }
}

// Clean build directory
function clean() {
  return del(['build/**', '!build']);
}


// Copy only necessary files for production
function copyFiles() {
  const filesToCopy = [
    'app/**',
    'core/**',
    'languages/**',
    'assets/css/**',
    'assets/fonts/**',
    'assets/images/**',
    'assets/js/**',
    'src/**',
    'vendor/**',
    'wpmudev-plugin-test.php',
    '!**/*.map',
    '!**/QUESTIONS.md',
    '!**/README.md',
    '!**/CHANGELOG.md',
    '!**/LICENSE.md',
    '!**/package.json',
    '!**/package-lock.json',
    '!**/composer.json',
    '!**/composer.lock',
    '!**/webpack.config.js',
    '!**/Gruntfile.js',
    '!**/gulpfile.js',
    '!**/phpcs.ruleset.xml',
    '!**/phpunit.xml.dist',
    '!src/**',
    '!tests/**',
    '!node_modules/**',
    '!**/.git',
    '!**/.gitignore',
    '!**/.DS_Store',
    // Exclude vendor tests, docs, examples
    '!vendor/**/test/**',
    '!vendor/**/tests/**',
    '!vendor/**/doc/**',
    '!vendor/**/docs/**',
    '!vendor/**/example/**',
    '!vendor/**/examples/**',
    '!vendor/**/.git',
    '!vendor/**/.gitignore',
    '!vendor/**/.DS_Store'
  ];

  
    // 'QUESTIONS.md',
    // 'README.md',
    // 'composer.json',
  // Add optional files if they exist
  const optionalFiles = [
    'uninstall.php',
    'changelog.txt'
  ];

  optionalFiles.forEach(file => {
    if (fileExists(file)) {
      filesToCopy.push(file);
    }
  });

  return src(filesToCopy, { 
    base: '.',
    allowEmpty: true // Allow missing files
  })
  .pipe(dest('build/wpmudev-plugin-test/'));
}


// Create zip package
function createZip() {
  const pkg = require('./package.json');
  return src('build/wpmudev-plugin-test/**/*', { allowEmpty: true })
        .pipe(zip(`${pkg.name}-${pkg.version}.zip`))
        .pipe(dest('build/'));
}


// Verify build
function verifyBuild() {
    const buildDir = 'build/wpmudev-plugin-test/';
    
    // Check if main plugin file exists
    if (!fileExists(path.join(buildDir, 'wpmudev-plugin-test.php'))) {
        throw new Error('Main plugin file not found in build!');
    }

    // Check if assets were built
    if (!fileExists(path.join(buildDir, 'assets/js/drivetestpage.min.js'))) {
        throw new Error('Compiled JavaScript not found!');
    }

    console.log('✓ Build verification passed');
    return Promise.resolve();
}

function cleanUnzipped() {
  return del(['build/wpmudev-plugin-test']);
}


// Main build task
// exports.default = series(clean, createZip);

exports.build = series(clean, copyFiles, verifyBuild, createZip, cleanUnzipped);
exports.default = exports.build;