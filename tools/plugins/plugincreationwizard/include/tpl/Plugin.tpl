
require_once 'common/plugin/Plugin.class.php';

/**
 * <?=$class_name?>Plugin
 */
class <?=$class_name?>Plugin extends Plugin {

    /**
     * Plugin constructor
     */
    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
<?php if ($use_web_space) { ?>
        $this->addHook('site_admin_option_hook', 'site_admin_option_hook', false);
<?php if ($use_css) { ?>
        $this->addHook('cssfile', 'cssfile', false);
<?php } ?>
<?php } ?>
    }

    /**
     * @return <?=$class_name?>PluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once '<?=$class_name?>PluginInfo.class.php';
            $this->pluginInfo = new <?=$class_name?>PluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
<?php if ($use_web_space) { ?>
    function site_admin_option_hook($params) {
        echo '<li><a href="'.$this->getPluginPath().'/"><?=$class_name?></a></li>';
    }
    
<?php   if ($use_css) { ?>
    function cssfile($params) {
        // Only show the stylesheet if we're actually in the <?=$class_name?> pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
<?php   } ?>
<?php   if ($use_mvc) { ?>
    function process() {
        require_once('<?=$class_name?>.class.php');
        $controler =& new <?=$class_name?>();
        $controler->process();
    }
<?php   } ?>
<?php } ?>
}

