#!/usr/bin/env php
<?php echo '<?php' ?>


use OsmScripts\Core\Script;

// This script is expected to be installed globally, using `composer global require`.
// It uses globally installed Composer packages.
include __DIR__ . '/../../autoload.php';

$script = new Script(['name' => basename(__FILE__), 'cwd' => getcwd()]);

exit($script->run());