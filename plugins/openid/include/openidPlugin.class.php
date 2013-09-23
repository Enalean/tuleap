<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'autoload.php';
require_once 'constants.php';
require_once 'common/plugin/Plugin.class.php';

class OpenidPlugin extends Plugin {

    /**
     * Plugin constructor
     */
    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        $this->addHook('account_pi_entry');
    }

    public function account_pi_entry(array $params) {
        $dao = new Openid_Dao();
        $dar = $dao->searchOpenidUrlsForUserId($params['user_id']);
        $params['entry_label'][$this->getId()] = 'OpenId';
        if ($dar->count()) {
            $row = $dar->getRow();
            $params['entry_value'][$this->getId()] = $row['connexion_string'];
            $params['entry_change'][$this->getId()] = '<a href="'.OPENID_BASE_URL.'/?func='.OpenId_OpenIdRouter::REMOVE_PAIR.'">['.$GLOBALS['Language']->getText('plugin_openid', 'remove_openid').']</a>';
        } else {
            $params['entry_value'][$this->getId()]  = '';
            $params['entry_change'][$this->getId()] = '<a href="'.OPENID_BASE_URL.'/?func='.OpenId_OpenIdRouter::PAIR_ACCOUNTS.'">['.$GLOBALS['Language']->getText('plugin_openid', 'add_openid').']</a>';
        }
    }

    public function process(HTTPRequest $request, Layout $response) {
        $this->loadPhpOpenId();
        $router = new OpenId_OpenIdRouter($this->getBackendLogger());
        $router->route($request, $response);
    }

    private function loadPhpOpenId() {
        $phpopenid_path = '/usr/share/php-openid';
        ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $phpopenid_path);
        include_once 'driver/openid_includes.php';
    }

    /**
     * @return OpenidPluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new OpenidPluginInfo($this);
        }
        return $this->pluginInfo;
    }
}
?>
