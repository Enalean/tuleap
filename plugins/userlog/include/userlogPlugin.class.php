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
require_once('common/validator/Validator.class.php');
require_once('UserLogManager.class.php');

class userlogPlugin extends Plugin {

	function userlogPlugin($id) {
		$this->Plugin($id);
        //$this->_addHook('hook_name');
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

        $request = new HTTPRequest();

        $offset = 0;
        if($request->existAndNonEmpty('offset')) {
            $vOffset = new IntValidator();
            $vOffset->biggerOrEqualThan(0);
            if($request->valid('offset', $vOffset)) {
                $offset = intval($request->get('offset'));
            } else {
                $GLOBALS['Response']->addFeedback('warning', 'Invalid offset submitted. Force it to 0 (zero).');
            }
        }

        $day = date('Y-n-j');
        if($request->existAndNonEmpty('day')) {
            $vDay = new DateValidator();
            if($request->valid('day', $vDay)) {
                $day = $request->get('day');
            } else {
                $GLOBALS['Response']->addFeedback('warning', 'Invalid date submitted. Force it to today.');
            }
        }

        $userLogManager = new UserLogManager();
        $userLogManager->displayLogs($offset, $day);
    }

}

?>
