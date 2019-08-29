<?php

namespace OsmScripts\OsmScripts\Commands;

use OsmScripts\Core\Command;
use OsmScripts\Core\Project;
use OsmScripts\Core\Script;

/** @noinspection PhpUnused */

/**
 * `scripts` shell command class.
 *
 * @property Project $project @required
 */
class Scripts extends Command
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
        $this->setDescription("Lists installed scripts and packages they are defined in");
    }

    protected function handle() {
        foreach ($this->project->packages as $package) {
            foreach ($package->lock->bin ?? [] as $script) {
                $this->output->writeln(sprintf("%-20s%s", basename($script), $package->name));
            }
        }
    }
}