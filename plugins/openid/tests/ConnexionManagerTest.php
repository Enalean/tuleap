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

require_once 'bootstrap.php';

class Openid_ConnexionManagerTest extends TuleapTestCase {

    /** @var Openid_Dao */
    private $dao;

    /** @var Openid_Driver_ConnexionDriver */
    private $driver;

    /** @var Openid_ConnexionManager */
    private $connexion_manager;

    public function setUp() {
        parent::setUp();
        $this->dao               = mock('Openid_Dao');
        $this->driver            = mock('Openid_Driver_ConnexionDriver');
        $this->connexion_manager = new Openid_ConnexionManager($this->driver);
    }

    public function itCallsTheDriverForConnexion() {
        $openid_url     = "https://www.google.com/accounts/o8/id";
        $return_to_url  = "http://example.net";

        expect($this->driver)->connect($openid_url, $return_to_url)->once();
        $this->connexion_manager->startAuthentication($openid_url, $return_to_url);
    }

}
