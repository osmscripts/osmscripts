<?php

namespace OsmScripts\OsmScripts\Commands;

use OsmScripts\Core\Command;
use OsmScripts\Core\Files;
use OsmScripts\Core\Hints\PackageHint;
use OsmScripts\Core\Package;
use OsmScripts\Core\Project;
use OsmScripts\Core\Script;
use OsmScripts\Core\Utils;
use OsmScripts\Core\Variables;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/** @noinspection PhpUnused */

/**
 * `create:command` shell command class.
 *
 * @property Files $files @required Helper for generating files.
 * @property string $script_path Directory of the Composer project containing currently executed script
 * @property Project $project Information about Composer project in current working directory
 * @property Variables $variables Helper for managing script variables
 * @property Utils $utils @required various helper functions
 *
 * @property string $command @required Name of the command to be created
 * @property string $namespace @required PHP class sub-namespace
 * @property string $class @required Name of the PHP class handling the command
 * @property string $script @required Script under which the command is registered
 * @property string $package @required Package in which command is created

 * @property Package $package_ @required
 * @property string $path @required Package path in `vendor` directory
 */
class CreateCommand extends Command
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            // dependencies
            case 'files': return $this->files = $script->singleton(Files::class);
            case 'project': return $this->project = new Project(['path' => $script->cwd]);
            case 'variables': return $this->variables = $script->singleton(Variables::class);
            case 'utils': return $this->utils = $script->singleton(Utils::class);
            case 'script_path': return $this->script_path = $script->path;

            // arguments and options
            case 'command': return $this->command = $this->input->getArgument('cmd');
            case 'namespace': return $this->namespace = $this->getNamespace();
            case 'class': return $this->class = $this->getClass();
            case 'script': return $this->script = $this->input->getOption('script');
            case 'package': return $this->package = $this->input->getOption('package');

            // calculated properties
            case 'package_': return $this->package_ = $this->project->getPackage($this->package);
        }

        return null;
    }

    protected function getClass() {
        if ($result = $this->input->getOption('class')) {
            if (($pos = mb_strrpos($result, '\\')) !== false) {
                return mb_substr($result, $pos + 1);
            }
            return $result;
        }

        return implode(array_map('ucfirst', explode(' ', strtr($this->command, ':_-', '   '))));
    }

    protected function getNamespace() {
        $result = $this->package_->namespace;

        if ($this->input->getOption('namespace')) {
            $result .= "\\{$this->input->getOption('namespace')}";
        }

        $result .= "\\Commands";

        if ($class = $this->input->getOption('class') && ($pos = mb_strrpos($class, '\\')) !== false) {
            $result .= "\\" . mb_substr($class, 0, $pos);
        }

        return $result;
    }
    #endregion

    protected function configure() {
        $this
            ->setDescription("Creates new script command")
            ->setHelp(<<<EOT
Run this command from {$this->script_path}.
EOT
            )
            ->addArgument('cmd', InputArgument::REQUIRED,
                "Name of command to be created")
            ->addOption('package', null, InputOption::VALUE_REQUIRED,
                "Name of Composer package in which command will be created, " .
                "should be in `{vendor}/{package}` format. If not set, \$package script variable is used",
                $this->variables->get('package'))
            ->addOption('script', null, InputOption::VALUE_REQUIRED,
                "Name of the script under which the command will be registered. " .
                "If not set, \$script script variable is used",
                $this->variables->get('script'))
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL,
                "Package sub-namespace. In most cases, leave empty")
            ->addOption('class', null, InputOption::VALUE_OPTIONAL,
                "Name of command PHP class. If omitted, inferred from command name");
    }

    protected function handle() {
        // this command is expected to run from the global Composer installation and it is expected
        // to generate files in the the global Composer installation
        $this->project->verifyCurrent();

        // create command PHP class.
        $this->createCommand();

        // register the command in `composer.json` file of the package
        $this->updateComposerJson();

    }

    protected function createCommand() {
        $namespace = strtr(mb_substr($this->namespace, mb_strlen($this->package_->namespace)), '\\', '/');
        $filename = "{$this->package_->path}/src{$namespace}/{$this->class}.php";

        $this->files->save($filename, $this->files->render('command_class', [
            'command' => $this->command,
            'namespace' => $this->namespace,
            'class' => $this->class,
        ]));
    }

    protected function updateComposerJson() {
        $filename = "{$this->package_->path}/composer.json";

        /* @var PackageHint $package */
        $package = $this->utils->readJsonOrFail($filename);

        $package = $this->utils->merge($package, (object)[
            'extra' => (object)[
                $this->script => (object)[
                    'commands' => (object)[
                        $this->command => "{$this->namespace}\\{$this->class}",
                    ],
                ],
            ],
        ]);

        $this->files->save($filename, json_encode($package, JSON_PRETTY_PRINT));
    }

}