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
    public function default($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'project': return new Project(['path' => $script->cwd]);
        }

        return parent::default($property);
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