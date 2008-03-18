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
require_once('UserLogManager.class.php');

class userlogPlugin extends Plugin {

	function userlogPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_menu_hook',  'siteAdminHooks', true);
        $this->_addHook('site_admin_option_hook','siteAdminHooks', true);
        $this->_addHook('cssfile',               'cssFile', false);
        $this->_addHook('javascript_file',       'jsFile',  false);
        $this->_addHook('logger_after_log_hook', 'logUser', false);
	}

    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'UserLogPluginInfo')) {
            require_once('UserLogPluginInfo.class.php');
            $this->pluginInfo =& new UserLogPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->_getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->_getThemePath().'/css/style.css" />'."\n";
        }
    }

    function jsFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->_getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="/scripts/calendar_js.php"></script>'."\n";
        }
    }

    /**
     * $params['HTML']
     */
    function siteAdminHooks($hook, $params) {
        $GLOBALS['Language']->loadLanguageMsg('userlog', 'userlog');
        $site_url  = $this->_getPluginPath().'/';
        $site_name = $GLOBALS['Language']->getText('plugin_userlog','descriptor_name');
        switch ($hook) {
            case 'site_admin_menu_hook':
                $HTML =& $params['HTML'];
                $HTML->menu_entry($site_url, $site_name);
                break;
            case 'site_admin_option_hook':
                echo '<li><a href="'.$site_url.'">'.$site_name.'</a></li>';
                break;
            default:
                break;
        }
    }

    /**
     * $params['isScript']
     * $params['groupId']
     * $params['time']
     */
    function logUser($params) {
        if(!$params['isScript']) {
            $uid = 0;
            $uid = user_getid();

            $request = HTTPRequest::instance();

            $cookie_manager =& new CookieManager();

            $userLogManager = new UserLogManager();
            $userLogManager->logAccess($params['time'],
                                       $params['groupId'],
                                       $uid,
                                       $cookie_manager->getCookie('session_hash'),
                                       $request->getFromServer('HTTP_USER_AGENT'),
                                       $request->getFromServer('REQUEST_METHOD'),
                                       $request->getFromServer('REQUEST_URI'),
                                       $request->getFromServer('REMOTE_ADDR'),
                                       $request->getFromServer('HTTP_REFERER'));
        }
    }

    function process() {
        session_require(array('group'=>'1','admin_flags'=>'A'));

        $request =& HTTPRequest::instance();

        $valid = new Valid('offset');
        $valid->setErrorMessage('Invalid offset submitted. Force it to 0 (zero).');
        $valid->addRule(new Rule_Int());
        $valid->addRule(new Rule_GreaterOrEqual(0));
        if($request->valid($valid)) {
            $offset = $request->get('offset');
        } else {
            $offset = 0;
        }

        $valid = new Valid('day');
        $valid->addRule(new Rule_Date(), 'Invalid date submitted. Force it to today.');
        if($request->valid($valid)) {
            $day = $request->get('day');
        } else {
            $day = date('Y-n-j');
        }

        $userLogManager = new UserLogManager();
        $userLogManager->displayLogs($offset, $day);
    }

}

?>
