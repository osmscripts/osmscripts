<?php
/* @var string $package */
/* @var string $namespace */
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
        "osmscripts/core": "dev-master"
    }
}