
/*
 * Copyright (c) Xerox, <?=date('Y')?>. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, <?=date('Y')?>. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/plugin/Plugin.class.php');

/**
 * <?=$class_name?>Plugin
 */
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
        echo '<li><a href="'.$this->getPluginPath().'/"><?=$class_name?></a></li>';
    }
    
<?php   if ($use_css) { ?>
    function cssFile($params) {
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

