
require_once 'common/plugin/PluginDescriptor.class.php';

/**
 * <?=$class_name?>PluginDescriptor
 */
class <?=$class_name?>PluginDescriptor extends PluginDescriptor {

    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_<?=$short_name?>', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_<?=$short_name?>', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
