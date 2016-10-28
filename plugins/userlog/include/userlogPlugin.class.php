<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

use Tuleap\Admin\AdminPageRenderer;

require_once 'constants.php';

class userlogPlugin extends Plugin {

    function __construct($id)
    {
        parent::__construct($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', true);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook('logger_after_log_hook', 'logUser', false);
        $this->addHook(Event::IS_IN_SITEADMIN);

        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
    }

    public function burning_parrot_get_stylesheets($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/userlog') === 0) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'UserLogPluginInfo')) {
            require_once('UserLogPluginInfo.class.php');
            $this->pluginInfo = new UserLogPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }

    /**
     * $params['HTML']
     */
    function siteAdminHooks($hook, $params)
    {
        $site_url  = $this->getPluginPath() . '/';
        $site_name = $GLOBALS['Language']->getText('plugin_userlog', 'descriptor_name');
        echo '<li><a href="' . $site_url . '">' . $site_name . '</a></li>';
    }

    /** @see Event::IS_IN_SITEADMIN */
    public function is_in_siteadmin($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $params['is_in_siteadmin'] = true;
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

            $cookie_manager = new CookieManager();

            $userLogManager = new UserLogManager(new AdminPageRenderer(), UserManager::instance());
            $userLogManager->logAccess($params['time'],
                                       $params['groupId'],
                                       $uid,
                                       $cookie_manager->getCookie('session_hash'),
                                       $request->getFromServer('HTTP_USER_AGENT'),
                                       $request->getFromServer('REQUEST_METHOD'),
                                       $request->getFromServer('REQUEST_URI'),
                                       HTTPRequest::instance()->getIPAddress(),
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

        $userLogManager = new UserLogManager(new AdminPageRenderer(), UserManager::instance());
        $userLogManager->displayLogs($offset, $day);
    }

}

?>
