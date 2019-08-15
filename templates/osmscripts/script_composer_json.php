<?php
/* @var string $package */
/* @var string $namespace */
/* @var string $script */
?>
{
    "name": "<?php echo $package ?>",
    "autoload": {
        "psr-4": {
            <?php echo $namespace ?>: "src/"
        }
    },
    "require": {
        "php": ">=7.1",
        "osmscripts/core": "1.*"
    },
    "extra": {
        "branch-alias": {
            "dev-master": "1.x-dev"
        }
    },
    "bin": [
        "<?php echo $script ?>"
    ]
}