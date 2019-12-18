<?php
/* @var string $package */
/* @var string $namespace */
/* @var string $version_constraint */
?>
{
    "name": "<?php echo $package ?>",
    "autoload": {
        "psr-4": {
            <?php echo $namespace ?>: "src/"
        }
    },
    "require": {
        "php": "^7.2",
        "osmscripts/core": "<?php echo $version_constraint ?>"
    }
}