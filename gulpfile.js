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

// Optimized file copying - directly to zip without intermediate folder
function createZip() {
    const pkg = require('./package.json');
    
    // Define files to include (optimized for minimal size)
    const filesToInclude = [
        // Core plugin files
        'wpmudev-plugin-test.php',
        'uninstall.php',
        'changelog.txt',
        
        // App and core directories
        'app/**',
        'core/**',
        'languages/**',
        
        // Assets (only compiled files)
        'assets/css/**',
        'assets/js/**',
        'assets/fonts/**',
        'assets/images/**',
        
        // Vendor - ONLY essential Google Drive files
        'vendor/autoload.php',
        'vendor/composer/**',

        // Google API dependencies
        'vendor/google/apiclient/**',
        'vendor/google/apiclient-services/autoload.php',
        'vendor/google/apiclient-services/src/Drive/**',
        'vendor/google/apiclient-services/src/Drive.php',
        'vendor/google/apiclient-services/src/DriveActivity/**',
        'vendor/google/apiclient-services/src/DriveActivity.php',
        'vendor/google/apiclient-services/src/DriveLabels/**',
        'vendor/google/apiclient-services/src/DriveLabels.php',
        'vendor/google/auth/**',
        // 'vendor/google/apiclient-services/src/Google/Service/Drive.php',
        // 'vendor/google/apiclient-services/src/Google/Service/Drive/*',
        
        // Guzzle HTTP dependencies
        'vendor/guzzlehttp/**',
        
        // PSR standards
        'vendor/psr/**',
        
        // Monolog logging
        'vendor/monolog/**',
        
        // Firebase JWT
        'vendor/firebase/**',
        
        // Security libraries
        'vendor/paragonie/**',
        'vendor/phpseclib/**',
        
        // HTTP utilities (this was missing!)
        'vendor/ralouphie/**',
        
        // Symfony components (often required by other packages)
        'vendor/symfony/**',
        
        // Exclude development files but keep essential source
        '!vendor/**/test/**',
        '!vendor/**/tests/**',
        '!vendor/**/doc/**',
        '!vendor/**/docs/**',
        '!vendor/**/example/**',
        '!vendor/**/examples/**',
        '!vendor/**/.git/**',
        '!vendor/**/.github/**',
        '!vendor/**/*.md',
        '!vendor/**/*.txt',
        '!vendor/**/*.xml',
        '!vendor/**/*.dist',
        '!vendor/**/LICENSE',
        '!vendor/**/CHANGELOG',
        
        // Exclude development tools but keep essential packages
        '!vendor/squizlabs/**',
        '!vendor/wp-coding-standards/**',
        '!vendor/phpcompatibility/**',
        '!vendor/phpcsstandards/**',
        '!vendor/dealerdirect/**',
        '!vendor/bin/**',
        
        // Exclude other Google services we don't need
        // '!vendor/google/apiclient-services/src/!(Drive|Google/Service/Drive.php|Google/Service/Drive)/**',

        // Exclude source files and development artifacts
        '!src/**',
        '!tests/**',
        '!node_modules/**',
        '!**/*.map',
        '!**/package*.json',
        '!**/composer*.json',
        '!**/webpack.config.js',
        '!**/Gruntfile.js',
        '!**/gulpfile.js',
        '!**/phpcs.ruleset.xml',
        '!**/phpunit.xml.dist',
        '!**/.git',
        '!**/.gitignore',
        '!**/.DS_Store',
        '!**/README.md',
        '!**/CHANGELOG.md',
        '!**/LICENSE.md',
        '!**/QUESTIONS.md'
    ];

    // Add optional files if they exist
    const optionalFiles = [
        'uninstall.php',
        'changelog.txt'
    ];

    optionalFiles.forEach(file => {
        if (fileExists(file)) {
            filesToInclude.push(file);
        }
    });

    return src(filesToInclude, { 
        base: '.',
        allowEmpty: true
    })
    .pipe(zip(`${pkg.name}-${pkg.version}.zip`))
    .pipe(dest('build/'));
}

// Verify build
function verifyBuild() {
    const zipFile = `build/wpmudev-plugin-test-${require('./package.json').version}.zip`;
    
    if (!fileExists(zipFile)) {
        throw new Error('Zip file was not created!');
    }

    // Get zip file size
    const stats = fs.statSync(zipFile);
    const fileSizeMB = (stats.size / (1024 * 1024)).toFixed(2);
    
    console.log(`✓ Build verification passed - Zip size: ${fileSizeMB} MB`);
    
    if (fileSizeMB > 10) {
        console.warn('⚠️  Warning: Zip file is larger than 10MB. Consider optimizing vendor dependencies.');
    }
    
    return Promise.resolve();
}


// Main build task - direct to zip without intermediate folder
exports.build = series(clean, createZip, verifyBuild);
exports.default = exports.build;