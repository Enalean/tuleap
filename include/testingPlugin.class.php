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
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function process(Codendi_Request $request) {
        $project = $request->getProject();
        $service = $project->getService('plugin_testing');
        if (! $service) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    $GLOBALS['Language']->getText('plugin_testing', 'service_lbl_key'))
            );
        }

        $title       = $GLOBALS['Language']->getText('plugin_testing', 'title');
        $toolbar     = array();
        $breadcrumbs = array();
        $service->displayHeader($title, $breadcrumbs, $toolbar);

        $renderer  = TemplateRendererFactory::build()->getRenderer(TESTING_TEMPLATE_DIR);
        $renderer->renderToPage('testing', array());

        $GLOBALS['HTML']->footer(array());
    }
}