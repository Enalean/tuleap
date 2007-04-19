/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * <?=$class_name?>Plugin
 */
require_once('common/plugin/Plugin.class.php');

class <?=$class_name?>Plugin extends Plugin {
	
	function <?=$class_name?>Plugin($id) {
		$this->Plugin($id);
<?php if ($use_web_space) { ?>
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
<?php 	if ($use_css) { ?>
        $this->_addHook('cssfile', 'cssFile', false);
<?php 	} ?>
<?php } ?>
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, '<?=$class_name?>PluginInfo')) {
            require_once('<?=$class_name?>PluginInfo.class.php');
            $this->pluginInfo =& new <?=$class_name?>PluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
<?php if ($use_web_space) { ?>
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->_getPluginPath().'/"><?=$class_name?></a></li>';
    }
    
<?php   if ($use_css) { ?>
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the <?=$class_name?> pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->_getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->_getThemePath().'/css/style.css" />';
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

