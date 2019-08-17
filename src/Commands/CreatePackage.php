<?php

namespace OsmScripts\OsmScripts\Commands;

use Exception;
use OsmScripts\Core\Commands\CreatePackage as BaseCreatePackage;

/** @noinspection PhpUnused */

/**
 * `create:package` shell command class.
 */
class CreatePackage extends BaseCreatePackage
{
    public $expect_current = true;

    protected function configure() {
        parent::configure();
        $this
            ->setDescription("Creates new Composer package for writing script commands, " .
                "pushes it to the specified Git repo and installs it locally")
            ->setHelp(<<<EOT
Run this command from {$this->script_path}.

Before running this command create empty repo on GitHub or other Git hosting provider. 
Pass URL of Git repo using --repo_url=REPO_URL syntax.

Also, before running this command commit and push all changes in all the packages in `vendor`.
directory.
EOT
            );
    }

    protected function createPackage() {
        $filename = "{$this->path}/composer.json";
        if (is_file($filename)) {
            throw new Exception("'{$filename}' already exists");
        }

        $this->files->save($filename, $this->files->render('composer_json', [
            'package' => $this->package,
            'namespace' => json_encode($this->namespace),
        ]));
    }
}