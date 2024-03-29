#!/usr/bin/env php
<?php echo '<?php' ?>

use OsmScripts\Core\Script;

// This script is expected to be installed globally, using `composer global require`.
// It uses globally installed Composer packages.
/** @noinspection PhpIncludeInspection */
include 'vendor/autoload.php';

// Create new Script object which contains all the objects: helpers which provide useful APIs,
// console application with its commands, knowledge about this project's packages and more.
//
// Script configures itself from the section of `composer.json` files having the same name as the script.
//
// We intentionally put the script object into global `$script` variable so that it can be
// easily accessed from any part of the code base
global $script;
$script = new Script(['name' => basename(__FILE__)]);

// Run the script. The script is Symfony console application. It expects command name and
// other input to be passed in script's additional arguments
exit($script->run());