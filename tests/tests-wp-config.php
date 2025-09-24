<?php
// Path to your WordPress installation
define( 'ABSPATH', 'D:/laragon/www/d5/' );

// Path to the tests directory
define( 'WP_TESTS_DIR', 'C:/Users/Tanvir/AppData/Local/Temp/wordpress-tests-lib/' );

// Path to main WordPress tests directory
define( 'WP_ROOT_DIR', 'D:/laragon/www/d5/' );

// Test database configuration
define( 'DB_NAME', 'divi5' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'AUTH_KEY',         'PxO5nOLT9IIjKo9CupTiTopYCkdtqOR3FzANL0CFyCUvqHmmIBaHEtUk2i6dcFHv' );
define( 'SECURE_AUTH_KEY',  '805zkCl7RmzwxZmtmw2cVTUeebnYI2foZwjDYFNkP4fZHE6xkrKfREhdoHDksSfA' );
define( 'LOGGED_IN_KEY',    'lakK5garZkbOilZRG07znIEFY4Pg2Bb1PkeoxXUzHYIvTsLr1wVkgtO4fvqdcHzv' );
define( 'NONCE_KEY',        'wYZxgTlDjYrQRgF2GVlnQPn13hLSUiqWCBeBEirHuKtAUgEgNJ3fGXURLA0eBWM5' );
define( 'AUTH_SALT',        'XgIX3m6H7xfen0rOKnAvCOp4BKIGSOGq9g5sj7IUjJVOFWpa2A1riHdQtzsnbGri' );
define( 'SECURE_AUTH_SALT', 'qifkwDKnmttOTRGAoCIewsSW6vEgtYyDORVXqFVn4yRP3zLYlKlz8kfxca30Fj89' );
define( 'LOGGED_IN_SALT',   'UpkPHKlFdHFNaXG1TtvJrjuQO7nFTGtPK1XkupoHSTPDd2pZwsXfErfSaAX6WMaZ' );
define( 'NONCE_SALT',       'ZhTEs0YX5nj76d9hY5Og0Mi1IiW4Vf5OwmSu5ZwkK2Hik1HiuiSWvrkWNdE2fZzL' );

// Test-specific configuration
define( 'WP_TESTS_DOMAIN', 'localhost' );
define( 'WP_TESTS_EMAIL', 'admin@example.com' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

// Use test database
define( 'WP_TESTS_MULTISITE', false );

// Debug mode for testing
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

// Test environment
define( 'WP_ENVIRONMENT_TYPE', 'testing' );

