<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('common/plugin/Plugin.class.php');

class userlogPlugin extends Plugin {

	function userlogPlugin($id) {
		$this->Plugin($id);
        //$this->_addHook('hook_name');
        //$this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
	}

    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'UserLogPluginInfo')) {
            require_once('UserLogPluginInfo.class.php');
            $this->pluginInfo =& new UserLogPluginInfo($this);
        }
        return $this->pluginInfo;
    }

}

?>
