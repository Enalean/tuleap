<?php

require_once 'common/plugin/Plugin.class.php';

class TemplatePlugin extends Plugin {

    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('site_admin_option_hook', 'site_admin_option_hook', false);
    }

    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'TemplatePluginInfo')) {
            include_once 'TemplatePluginInfo.class.php';
            $this->pluginInfo = new TemplatePluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function site_admin_option_hook($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Template</a></li>';
    }

    function process() {
        echo '<h1>Template</h1>';
        echo $this->getPluginInfo()->getpropVal('answer');
    }
}

?>
