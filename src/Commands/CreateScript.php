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
 * @property Files $files @required
 * @property Shell $shell @required
 * @property string[] $package_names @required
 * @property string $cwd @required
 *
 * @property string $script @required
 * @property string $package @required
 * @property string $namespace @required
 * @property string $repo_url @required
 * @property string $path @required
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
            ->addArgument('script', InputArgument::REQUIRED)
            ->addOption('package', null, InputOption::VALUE_REQUIRED)
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED)
            ->addOption('repo_url', null, InputOption::VALUE_REQUIRED);
    }

    protected function handle() {
        $this->verifyThatCurrentDirectoryIsThisProject();
        $this->verifyThatThereAreNoUncommittedChanges();
        $this->createComposerJson();
        $this->createScript();
        $this->initAndPushGitRepo();
        $this->updateComposer();
    }

    protected function verifyThatCurrentDirectoryIsThisProject() {
        /* @var Script $script */
        global $script;

        if ($script->path !== $this->cwd) {
            throw new Exception("Before running this command, change current directory to '$script->path'");
        }
    }

    protected function verifyThatThereAreNoUncommittedChanges() {
        $this->verifyThatThereIsNoUncommittedChangesInDirectory('.');

        foreach ($this->package_names as $package) {
        $this->verifyThatThereIsNoUncommittedChangesInDirectory("vendor/{$package}");
        }
    }

    protected function verifyThatThereIsNoUncommittedChangesInDirectory($path) {
        if (!is_dir("{$path}/.git")) {
            return;
        }

        $this->shell->cd($path, function() use ($path) {
            $this->shell->run('git update-index -q --refresh');
            if (!empty($output = $this->shell->output('git diff-index --name-only HEAD --'))) {
                throw new Exception("Commit and push pending changes in '{$path}' first");
            }
        });
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
            $this->shell->run('git init');
            $this->shell->run('git add .');
            $this->shell->run('git commit -am "Initial commit"');

            $this->shell->run("git remote add origin {$this->repo_url}");
            $this->shell->run('git push -u origin master');
        });
    }

    protected function updateComposer() {
    }

}