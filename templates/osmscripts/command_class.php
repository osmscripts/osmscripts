<?php
/* @var string $command */
/* @var string $namespace */
/* @var string $class */
?>
<?php echo '<?php' ?>


namespace <?php echo $namespace ?>;

use OsmScripts\Core\Command;
use OsmScripts\Core\Script;

/** @noinspection PhpUnused */

/**
 * `<?php echo $command ?>` shell command class.
 *
 * @property
 */
class <?php echo $class ?> extends Command
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
        }

        return parent::__get($property);
    }
    #endregion

    protected function configure() {
        // TODO: describe the command usage, arguments and options
    }

    protected function handle() {
        // TODO: execute command logic
    }
}