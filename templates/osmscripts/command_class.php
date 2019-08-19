<?php
/* @var string $command */
/* @var string $namespace */
/* @var string $class */
?>
<?php echo '<?php' ?>


namespace <?php echo $namespace ?>;

use OsmScripts\Core\Command;

/** @noinspection PhpUnused */

/**
 * `<?php echo $command ?>` shell command class.
 *
 * TODO: declare @properties here
 */
class <?php echo $class ?> extends Command
{
    #region Properties
    public function __get($property) {
        // TODO: calculate lazy properties here. Use this template:
//        /* @var Script $script */
//        global $script;
//
//        switch ($property) {
//            case 'property': return $this->property = 'value';
//        }

        return null;
    }
    #endregion

    protected function configure() {
        // TODO: describe the command usage, arguments and options
    }

    protected function handle() {
        // TODO: execute command logic
    }
}