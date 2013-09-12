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

class OpenId_LoginController {

    /** @var HTTPRequest */
    private $request;

    /** @var Layout */
    private $response;

    public function __construct(HTTPRequest $request, Layout $response) {
        $this->request  = $request;
        $this->response = $response;
    }

    public function finish_pair_accounts() {
        $return_to_url = "http://google.fr";
        $driver = new Openid_Driver_ConnexionDriver('?func=finish_auth_pair_accounts');
        $dao    = new Openid_Dao();

        $manager = new Openid_ConnexionManager($dao, $driver);

        $connexion_string = $manager->finishAuthentication($return_to_url);
        if ($dao->addConnexionStringForUserId($connexion_string, $this->request->getCurrentUser()->getId())) {
            $this->response->addFeedback(Feedback::INFO, 'OpenID updated');
        } else {
            $this->response->addFeedback(Feedback::ERROR, 'OpenId not update, something failed');
        }

        $this->response->redirect('/account/');
    }

    public function pair_accounts() {
        $this->authenticate('?func=finish_pair_accounts');
    }

    public function login() {
        $this->authenticate('?func=finish_login');
    }

    private function authenticate($finish_url) {
        $openid_url = "https://www.google.com/accounts/o8/id";
        $return_url = get_server_url().'/my';
        if ($this->request->existAndNonEmpty('return_to')) {
            $return_url = $this->request->getValidated('return_to', 'string', '');
        }

        $dao    = new Openid_Dao();
        $driver = new Openid_Driver_ConnexionDriver($finish_url);
        $manager = new Openid_ConnexionManager($dao, $driver);
        $manager->startAuthentication($openid_url, $return_url);
    }

    public function finish_login() {
        $return_url = get_server_url().'/my';
        if ($this->request->existAndNonEmpty('return_to')) {
            $return_url = $this->request->getValidated('return_to', 'string', '');
        }
        $driver = new Openid_Driver_ConnexionDriver('?func=finish_auth');
        $dao    = new Openid_Dao();

        $manager = new Openid_ConnexionManager($dao, $driver);

        $identity_url = $manager->finishAuthentication($return_url);
        $user = $manager->authenticateCorrespondingUser($identity_url);
        if ($user->isLoggedIn()) {
            $this->response->redirect($return_url);
        } else {
            $this->response->redirect('/account/login');
        }
    }

    public function remove_pair() {
        $dao    = new Openid_Dao();
        $user_id = $this->request->getCurrentUser()->getId();
        $dar = $dao->searchOpenidUrlsForUserId($user_id);
        if ($dar && $dar->count()) {
            $row = $dar->getRow();
            $dao->removeConnexionStringForUserId($row['connexion_string'], $user_id);
        }
        $this->response->redirect('/account/');
    }
}

?>
