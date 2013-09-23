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

require_once 'common/include/Config.class.php';

/**
 * This object is responsible for the communication during the
 * authentication process through OpenID protocol.
 */
class Openid_Driver_ConnexionDriver {
    /** @var Logger */
    private $logger;

    private $finish_auth_path;

    const OPENID_STORE_PATH = "/var/tmp/codendi_cache/tuleap_openid_consumer_store";
    const FINISH_AUTH_PATH  = "update_link.php";

    public function __construct(Logger $logger, $finish_auth_path) {
        $this->logger = $logger;
        $this->finish_auth_path = $finish_auth_path;
    }

    /**
     * Connect to an OpenId provider
     *
     * @param String $openid_url
     * @param String $redirect_url
     *
     * @throws OpenId_OpenIdException
     */
    public function connect($openid_url, $redirect_url) {
        $openid_consumer = $this->getConsumer();
        $auth_request    = $openid_consumer->begin($openid_url);

        if (! $auth_request) {
            throw new OpenId_OpenIdException($GLOBALS['Language']->getText('plugin_openid', 'error_openid_connect'));
        }

        if ($this->isOpenid1($auth_request)) {
            $this->issueOpenid1Connexion($auth_request, $redirect_url);
        } else {
            $this->issueOpenid2Connexion($auth_request, $redirect_url);
        }
    }

    private function issueOpenid1Connexion(Auth_OpenID_AuthRequest $auth_request, $redirect_url) {
        $redirect_url = $auth_request->redirectURL($this->getTrustRoot(), $this->getReturnTo($redirect_url));

        if (Auth_OpenID::isFailure($redirect_url)) {
            throw new OpenId_OpenIdException($GLOBALS['Language']->getText('plugin_openid', 'error_openid_connect'));
        }

        header("Location: ".$redirect_url);
    }

    private function issueOpenid2Connexion(Auth_OpenID_AuthRequest $auth_request, $redirect_url) {
        $form_id   = "openid_message";
        $form_html = $auth_request->htmlMarkup($this->getTrustRoot(), $this->getReturnTo($redirect_url), false, array('id' => $form_id));

        if (Auth_OpenID::isFailure($form_html)) {
            throw new OpenId_OpenIdException($GLOBALS['Language']->getText('plugin_openid', 'error_openid_connect'));
        }

        echo $form_html;
    }

    private function isOpenid1(Auth_OpenID_AuthRequest $auth_request) {
        return $auth_request->shouldSendRedirect();
    }

    private function getStore() {
        $store_path = self::OPENID_STORE_PATH;
        if (!file_exists($store_path) && !mkdir($store_path)) {
            $this->logger->error("OPENID DRIVER - Unable to create Filestore self::OPENID_STORE_PATH unsufficient permissions");
            throw new OpenId_OpenIdException($GLOBALS['Language']->getText('plugin_openid', 'error_openid_store', Config::get('sys_name')));
        }

        return new Auth_OpenID_FileStore($store_path);
    }

    public function getConsumer() {
        return new Auth_OpenID_Consumer($this->getStore());
    }

    private function getScheme() {
        $scheme = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $scheme = 'https';
        }
        return $scheme;
    }

    public function getReturnTo($return_to_url) {
        return $this->getTrustRoot().$this->finish_auth_path.'&return_to='.$return_to_url;
    }

    private function getTrustRoot() {
        $scheme                 = $this->getScheme();
        $server_name            = $_SERVER['SERVER_NAME'];
        $current_page_directory = dirname($_SERVER['PHP_SELF']);

        return "$scheme://$server_name$current_page_directory/";
    }
}
?>
