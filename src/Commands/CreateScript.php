<?php

namespace OsmScripts\OsmScripts\Commands;

use Exception;
use OsmScripts\Core\Command;
use OsmScripts\Core\Files;
use OsmScripts\Core\Git;
use OsmScripts\Core\Hints\PackageHint;
use OsmScripts\Core\Project;
use OsmScripts\Core\Script;
use OsmScripts\Core\Shell;
use OsmScripts\Core\Utils;
use OsmScripts\Core\Variables;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/** @noinspection PhpUnused */

/**
 * `create:script` shell command class.
 *
 * @property Files $files @required Helper for generating files.
 * @property Shell $shell @required Helper for running comands in local shell
 * @property string $script_path Directory of the Composer project containing currently executed script
 * @property Project $project Information about Composer project in current working directory
 * @property Git $git Git helper
 * @property Variables $variables Helper for managing script variables
 * @property Utils $utils @required various helper functions
 * @property string $script_name @required Name of currently executed script
 *
 * @property string $script @required Name of script to be created
 * @property string $package @required Name of package to be created
 * @property bool $no_update @required If set, skips creation and push of Git repo and Composer update
 * @property string $path @required Package path in `vendor` directory
 */
class CreateScript extends Command
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            // dependencies
            case 'files': return $this->files = $script->singleton(Files::class);
            case 'shell': return $this->shell = $script->singleton(Shell::class);
            case 'project': return $this->project = new Project(['path' => $script->cwd]);
            case 'git': return $this->git = $script->singleton(Git::class);
            case 'variables': return $this->variables = $script->singleton(Variables::class);
            case 'utils': return $this->utils = $script->singleton(Utils::class);
            case 'script_path': return $this->script_path = $script->path;
            case 'script_name': return $this->script_name = $script->name;

            // arguments and options
            case 'script': return $this->script = $this->input->getArgument('script');
            case 'package': return $this->package = $this->input->getOption('package');
            case 'no_update': return $this->no_update = $this->input->getOption('no-update');

            // calculated properties
            case 'path': return $this->path = "vendor/{$this->package}";
        }

        return null;
    }
    #endregion

    protected function configure() {
        $this
            ->setDescription("Creates new script, pushes updated package to server Git repo and " .
                "registers the changes with Composer")
            ->setHelp(<<<EOT
Run this command from {$this->script_path}.

Before running this command commit and push all changes in all the packages in `vendor`.
directory.
EOT
            )
            ->addArgument('script', InputArgument::REQUIRED,
                "Name of script to be created")
            ->addOption('package', null, InputOption::VALUE_REQUIRED,
                "Name of Composer package to be created for the script, " .
                "should be in `{vendor}/{package}` format. if not set, \$package script variable is used",
                $this->variables->get('package'))
            ->addOption('no-update', null, InputOption::VALUE_NONE,
                "Skip Git repo commit, push and Composer update");
    }

    protected function handle() {
        // this command is expected to run from the global Composer installation and it is expected
        // to generate files in the the global Composer installation
        $this->project->verifyCurrent();

        if (!$this->no_update) {
            // in the end, this command runs `composer update` which overwrites files in project's `vendor`
            // directory, so all the files in `vendor` directory are expected to be committed to their Git repos
            // and pushed to server
            $this->project->verifyNoUncommittedChanges();
        }

        // create PHP file registered in `bin` section of package `composer.json` file. This file
        // will be executed each time user types in script name in shell
        $this->createScript();

        // create a directory for new Composer package in `vendor` directory and
        // `composer.json` file in it which defines the directory as valid Composer package
        $this->updateComposerJson();

        if (!$this->no_update) {
            // put package files under Git and push them to repo on server
            $this->shell->cd($this->path, function() {
                $this->git->commit("`{$this->script}` script created");
                $this->git->push();
            });

            // run `composer update` to register new script within this project.
            //
            // Newly created script PHP file will be registered in project's `vendor/bin` directory
            // which should be added to `$PATH` variable and, hence, the script should be available
            // to run in shell, from any directory
            $this->project->update();
        }

        $this->shell->run("{$this->script_name} var script={$this->script}");
    }

    protected function createScript() {
        $filename = "{$this->path}/{$this->script}";

        $this->files->save($filename, $this->files->render('script'));
    }

    protected function updateComposerJson() {
        $filename = "{$this->path}/composer.json";

        /* @var PackageHint $package */
        $package = $this->utils->readJsonOrFail($filename);

        if (!isset($package->bin)) {
            $package->bin = [];
        }
        $package->bin[] = $this->script;

        $this->files->save($filename, json_encode($package, JSON_PRETTY_PRINT));
    }
}