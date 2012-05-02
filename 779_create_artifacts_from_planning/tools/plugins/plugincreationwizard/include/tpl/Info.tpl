
require_once 'common/plugin/PluginInfo.class.php';
require_once '<?=$class_name?>PluginDescriptor.class.php';


/**
 * <?=$class_name?>PluginInfo
 */
class <?=$class_name?>PluginInfo extends PluginInfo {

    function __construct($plugin) {
        parent::__construct($plugin);
        $this->setPluginDescriptor(new <?=$class_name?>PluginDescriptor());
    }
}
