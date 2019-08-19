#!/usr/bin/env php
<?php echo '<?php' ?>

use OsmScripts\Core\Hints\ComposerLockHint;

call_user_func(function () {
    $name = basename(__FILE__);
    if (!is_file('composer.lock') || !($contents = file_get_contents('composer.lock'))) {
        throw new Exception("'composer.lock' not found");
    }

    /* @var ComposerLockHint $json */
    if (!($json = json_decode($contents))) {
        throw new Exception("'composer.lock' is not valid JSON file");
    }

    foreach (['packages', 'packages-dev'] as $packages) {
        foreach ($json->$packages ?? [] as $package) {
            foreach ($package->bin ?? [] as $script) {
                if (basename($script) === $name) {
                    /** @noinspection PhpIncludeInspection */
                    include "vendor/{$package->name}/{$script}";

                    return;
                }
            }
        }
    }

    throw new Exception("Script '$name' is not found in '" . getcwd() . "'");
});