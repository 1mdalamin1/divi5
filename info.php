<?php
/*
https://github.com/1mdalamin1/divi5/tree/wpmudev-plugin-test

PHP CodeSniffer Config installed_paths set to ../../phpcompatibility/php-compatibility,../../phpcompatibility/phpcompatibility-paragonie,../../phpcompatibility/phpcompatibility-wp,../../phpcsstandards/phpcsextra,../../phpcsstandards/phpcsutils,../../wp-coding-standards/wpcs
> Google_Task_Composer::cleanup

remove clear && from package.json scripts

	"scripts": {
		"watch": "webpack --watch --mode development",
		"compile": "webpack --progress --mode production",
		"translate": "wp i18n make-pot ./ languages/wpmudev-plugin-test.pot --exclude=build,node_modules,src,tests,vendor --ignore-domain --allow-root",
		"build": "npm run compile && npm run translate && gulp build"
	},


*/
// write a function to check if the plugin is active

# npm install --save-dev del gulp-zip
# npm install --save-dev @babel/preset-react
# composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction