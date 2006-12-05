
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * <?=$class_name?>PluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class');

$GLOBALS['Language']->loadLanguageMsg('<?=$short_name?>', '<?=$short_name?>');

class <?=$class_name?>PluginDescriptor extends PluginDescriptor {
    
    function <?=$class_name?>PluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_<?=$short_name?>', 'descriptor_name'), '<?=$version?>', $GLOBALS['Language']->getText('plugin_<?=$short_name?>', 'descriptor_description'));
    }
}
