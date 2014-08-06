<?php

require_once 'common/plugin/Plugin.class.php';
require_once 'constants.php';

/**
 * TestingPlugin
 */
class TestingPlugin extends Plugin {

    /**
     * Plugin constructor
     */
    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('cssfile', 'cssfile', false);
    }

    public function getServiceShortname() {
        return 'plugin_testing';
    }

    /**
     * @return TestingPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once 'TestingPluginInfo.class.php';
            $this->pluginInfo = new TestingPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function cssfile($params) {
        // Only show the stylesheet if we're actually in the Testing pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getPluginPath().'/scripts/angular/bin/assets/testing.css" />';
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function process(Codendi_Request $request) {
        $config = new \Tuleap\Testing\Config(new \Tuleap\Testing\Dao());
        $router = new Tuleap\Testing\Router($this, $config);
        $router->route($request);
    }
}