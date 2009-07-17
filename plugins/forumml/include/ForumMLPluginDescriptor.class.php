<?php

require_once('common/plugin/PluginDescriptor.class.php');

class ForumMLPluginDescriptor extends PluginDescriptor {
    
    function ForumMLPluginDescriptor() {
        $this->PluginDescriptor('ForumML', 'v1.0', $GLOBALS['Language']->getText('plugin_forumml', 'descriptor_description'));
    }
}
?>
