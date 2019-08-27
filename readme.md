`osmscripts` is command-line utility for creating OS-agnostic shell scripts in PHP.

## Prerequisites ##

Prior using `osmscripts` install the following software locally:

* PHP 7.1.3
* Composer
* Git

Also, have an account on [GitHub](https://github.com/) or other Git hosting provider.

## Installation ##

1. Install this package:

		composer -g config repositories.osmscripts_core vcs git@github.com:osmscripts/core.git
		composer -g config repositories.osmscripts_osmscripts vcs git@github.com:osmscripts/osmscripts.git
		composer -g require osmscripts/osmscripts

2. Place composer's system-wide `vendor/bin` directory in your `$PATH`. This directory exists in different locations based on your operating system; however, some common locations include:

	* macOS and GNU / Linux Distributions: `$HOME/.composer/vendor/bin`
	* Windows: `%USERPROFILE%\AppData\Roaming\Composer\vendor\bin`

## Usage ##

### Looking Around ###

Once installed, `osmscripts` command-line script should be globally available. It is full-fledged [Symfony Console](https://symfony.com/doc/current/components/console.html) application. 

First, get acquainted with it. You can run it without arguments to get the list of commands or you can run every command with `-h` argument to read full help on its usage, arguments and options:

	osmscripts   
	osmscripts create:package -h

### Creating Global Scripts ###

`osmscripts` allows you to create your own globally available scripts, just as `osmscripts` itself and add commands to it:

1. Create a new repository in your GitHub account (or other Git hosting provider) and then create new Composer package in global Composer installation:

		osmscripts -g create:package {vendor}/{package} --repo_url={repo_url}
	
	This command creates a package in `vendor/{vendor}/{package}` directory, puts it under Git, pushes it to specified server repository and registers it in global `composer.json`.

	`osmscripts` also marks newly created package as "currently being developed", so that all consequent commands will apply to this package. 

2. Create new script in your package:

		osmscripts -g create:script {script}

	This command creates new scripts without any commands in it, pushes file changes to server repository and runs `composer update` to register new script in global `composer.json`.

	`osmscripts` also marks newly created script as "currently being developed", so that all consequent commands will apply to this script. 

	After running this command, newly created script should be globally available, so you can run it from any directory:

		{script} 

3. Create new command in your package and add it to your script:

		osmscripts -g create:command {command}
	
	After running this command, it is immediately availablene, so you can run it from any directory:

		{script} {command}

4. Write command logic and description in PHP command class. Use helper classes which come with [`osmscripts/core`](https://github.com/osmscripts/core) package.

**Note**. You may create a package which adds commands to scripts defined in other packages. To do that, modify script variables as described in next section.

### Script Variables ###

You may see currently being developed package and script:

	osmscripts -g var

You may also change these variables: 

	osmscripts -g var package={vendor}/{package}
	osmscripts -g var script={script}

You can also list all globally installed packages and scripts:

	osmscripts -g packages
	osmscripts -g scripts

**Note**. Every script you created with `osmscripts create:script` command also have `var` command included, so you can define and use variables in your scripts too! 

### Project Script Runners ###

You may want to define scripts and command not globally but in every single project, so that scripts on different project versions may behave differently:

1. Create global Composer package with "runner" script in it. Runner scripts don't have commands of their own, they just execute script from a project in current directory:

		osmscripts -g create:package {vendor}/{package} --repo_url={repo_url}
		osmscripts -g create:script {script} --runner

2. Create Composer package in project and script with the same name in it: 

		cd {project_dir}
		osmscripts create:package {vendor}/{package} --repo_url={repo_url}
		osmscripts create:script {script}

	You can run project-specific scripts from the project directory:

		cd {project_dir}
		{script}

3. Add and implement commands:

		cd {project_dir}
		osmscripts create:command {command}

## License And Credits ##

Copyright (C) 2019 - present UAB "Softnova".

All files of this package are licensed under [GPL-3.0](/LICENSE).

The idea of globally available PHP scripts first came while reading Laravel documentation. One paragraph explaining configuration of `$PATH` variable is taken from [Laravel installation instruction](https://laravel.com/docs#installing-laravel).