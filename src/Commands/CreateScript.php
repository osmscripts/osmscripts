<?php

namespace OsmScripts\OsmScripts\Commands;

use Exception;
use OsmScripts\Core\Command;
use OsmScripts\Core\Files;
use OsmScripts\Core\Script;
use OsmScripts\Core\Shell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/** @noinspection PhpUnused */

/**
 * `create:script` shell command class.
 *
 * @property Files $files @required
 * @property Shell $shell @required
 * @property string[] $package_names @required Names of packages installed in this project
 * @property string $cwd @required Current working directory - a directory from which this script is invoked
 * @property string $script_path Directory of the Composer project containing currently executed script
 *
 * @property string $script @required Name of script to be created
 * @property string $package @required Name of package to be created
 * @property string $namespace @required PHP root namespace of the package
 * @property string $repo_url @required URL of the server Git repo
 * @property string $path @required Path to directory in `vendor` where new package is created
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
            case 'package_names': return $this->package_names = $script->package_names;
            case 'script_path': return $this->script_path = $script->path;
            case 'cwd': return $this->cwd = $script->cwd;

            // arguments
            case 'script': return $this->script = $this->input->getArgument('script');
            case 'package': return $this->package = $this->input->getOption('package');
            case 'namespace': return $this->namespace = $this->getNamespace();
            case 'repo_url': return $this->repo_url = $this->input->getOption('repo_url');

            // calculated properties
            case 'path': return $this->path = "vendor/{$this->package}";
        }

        return null;
    }

    protected function getNamespace() {
        $result = $this->input->getOption('namespace');

        if (strrpos($result, '\\') !== strlen($result) - strlen('\\')) {
            $result .= '\\';
        }

        return $result;
    }
    #endregion

    protected function configure() {
        $this
            ->setDescription("Creates new Composer package with a script in it, " .
                "pushes it to the specified Git repo and installs it locally")
            ->setHelp(<<<EOT
Before running this command create empty repo on GitHub or other Git hosting provider. 
Pass URL of Git repo using --repo_url=REPO_URL syntax.

Run this command from {$this->script_path}.
EOT
            )
            ->addArgument('script', InputArgument::REQUIRED,
                "(required) Name of script to be created")
            ->addOption('package', null, InputOption::VALUE_REQUIRED,
                "(required) Name of Composer package to be created for the script, " .
                "should be in `{vendor}/{package}` format")
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED,
                "(required) Root namespace of PHP classes in this package, use '\\' delimiter")
            ->addOption('repo_url', null, InputOption::VALUE_REQUIRED,
                "(required) URL of EMPTY server Git repo for newly created package");
    }

    protected function handle() {
        // this command is expected to run from the global Composer installation and it is expected
        // to generate files in the the global Composer installation
        $this->verifyThatCurrentDirectoryIsThisProject();

        // in the end, this command runs `composer update` which overwrites files in project's `vendor`
        // directory, so all the files in `vendor` directory are expected to be committed to their Git repos
        // and pushed to server
        $this->verifyThatThereAreNoUncommittedChanges();

        // create a directory for new Composer package in `vendor` directory and
        // `composer.json` file in it which defines the directory as valid Composer package
        $this->createComposerJson();

        // create PHP file registered in `bin` section of package `composer.json` file. This file
        // will be executed each time user types in script name in shell
        $this->createScript();

        // put package files under Git and push them to repo on server
        $this->initAndPushGitRepo();

        // run `composer update` to register new package within this project.
        //
        // Package PHP namespace will be resolved to `src` subdirectory so all PHP classes
        // in `src` subdirectory will be autoloaded.
        //
        // Newly created script PHP file will be registered in project's `vendor/bin` directory
        // which should be added to `$PATH` variable and, hence, the script should be available
        // to run in shell, from any directory
        $this->updateComposer();
    }

    protected function verifyThatCurrentDirectoryIsThisProject() {
        if ($this->script_path !== $this->cwd) {
            throw new Exception("Before running this command, change current directory to '$this->script_path'");
        }
    }

    protected function verifyThatThereAreNoUncommittedChanges() {
        foreach ($this->package_names as $package) {
        $this->verifyThatThereIsNoUncommittedChangesInDirectory("vendor/{$package}");
        }
    }


    protected function verifyThatThereIsNoUncommittedChangesInDirectory($path) {
        if (!is_dir("{$path}/.git")) {
            return;
        }

        $this->shell->cd($path, function() use ($path) {
            $this->shell->run('git update-index -q --refresh', true);

            // run a command which lists all uncommitted files and if it lists anything, stop
            if (!empty($output = $this->shell->output('git diff-index --name-only HEAD --'))) {
                throw new Exception("Commit and push pending changes in '{$path}' first");
            }

            // download missing commits from the server Git repo (if any)
            $this->shell->run('git fetch', true);

            // get the name of the current Git branch
            $branch = implode($this->shell->output('git rev-parse --abbrev-ref HEAD'));

            // count the number of Git commits local Git repo is behind (if $count is
            // positive) or ahead (if $count is negative).
            $count = intval(implode($this->shell->output(
                "git rev-list {$branch}...origin/{$branch} --ignore-submodules --count")));

            // if local and server Git repos are not the same, stop
            if ($count > 0) {
                throw new Exception("Push pending commits in '{$path}' first");
            }
            if ($count < 0) {
                throw new Exception("Pull pending commits in '{$path}' first");
            }
        }, true);
    }


    protected function createComposerJson() {
        $filename = "{$this->path}/composer.json";
        if (is_file($filename)) {
            throw new Exception("'{$filename}' already exists");
        }

        $this->files->save($filename, $this->files->render('composer_json', [
            'package' => $this->package,
            'namespace' => json_encode($this->namespace),
            'script' => $this->script,
        ]));
    }

    protected function createScript() {
        $filename = "{$this->path}/{$this->script}";

        $this->files->save($filename, $this->files->render('script', [
            'package' => $this->package,
            'namespace' => json_encode($this->namespace),
            'script' => $this->script,
        ]));
    }

    protected function initAndPushGitRepo() {
        $this->shell->cd($this->path, function() {
            // create Git repository
            $this->shell->run('git init');

            // mark all files in current directory as tracked by Git, uncommitted new files
            $this->shell->run('git add .');

            // create first Git commit
            $this->shell->run('git commit -am "Initial commit"');

            // link local Git repo we have just created with repo on GitHub (or other git hosting provider)
            $this->shell->run("git remote add origin {$this->repo_url}");

            // push the only local commit to server repo (which is expected to be empty)
            $this->shell->run('git push -u origin master');
        });
    }

    protected function updateComposer() {
        $name = strtr($this->package, '/', '_');

        // let the Composer know about server Git repo of newly created package,
        // otherwise it will not find the package in the next step
        $this->shell->run("composer config repositories.{$name} vcs {$this->repo_url}");

        // install `master` branch of newly created package. As files are already there Composer
        // will overwrite them, but in addition it will register package's PHP namespace and newly
        // created script
        $this->shell->run("composer require {$this->package}:dev-master@dev");
    }

}