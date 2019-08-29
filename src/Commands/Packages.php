<?php

namespace OsmScripts\OsmScripts\Commands;

use OsmScripts\Core\Command;
use OsmScripts\Core\Project;
use OsmScripts\Core\Script;

/** @noinspection PhpUnused */

/**
 * `packages` shell command class.
 *
 * @property Project $project @required
 */
class Packages extends Command
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'project': return $this->project = new Project(['path' => $script->cwd]);
        }

        return parent::__get($property);
    }
    #endregion

    protected function configure() {
        $this->setDescription("Lists installed packages");
    }

    protected function handle() {
        foreach ($this->project->packages as $package) {
            $this->output->writeln($package->name);
        }
    }
}