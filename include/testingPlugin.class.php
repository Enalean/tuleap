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
        $this->addHook('cssfile');
        $this->addHook('javascript_file');
        $this->addHook(Event::REST_PROJECT_RESOURCES);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICE_ICON);
    }

    public function getServiceShortname() {
        return 'plugin_testing';
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e813';
    }

    public function service_classnames($params) {
        $params['classnames'][$this->getServiceShortname()] = 'Testing\\Service';
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

    public function javascript_file() {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="scripts/move-breadcrumb.js"></script>'."\n";
        }
    }
    
    function process(Codendi_Request $request) {
        $config = new \Tuleap\Testing\Config(new \Tuleap\Testing\Dao());
        $router = new Tuleap\Testing\Router($this, $config);
        $router->route($request);
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources(array $params) {
        $injector = new Testing_REST_ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see REST_PROJECT_RESOURCES
     */
    function rest_project_resources(array $params) {
        $injector = new Testing_REST_ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }
}