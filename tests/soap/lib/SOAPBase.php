<?php
/**
 * Copyright (c) Enalean, 2015 - 2019. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class SOAPBase extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    protected $server_base_url;
    protected $base_wsdl;
    protected $project_wsdl;
    protected $server_name;
    protected $server_port;
    protected $login;
    protected $password;
    protected $context;

    /** @var SoapClient */
    protected $soap_base;

    /** @var SoapClient */
    protected $soap_project;

    private static $user_ids;

    public function setUp(): void
    {
        parent::setUp();

        $this->login              = SOAP_TestDataBuilder::TEST_USER_1_NAME;
        $this->password           = SOAP_TestDataBuilder::TEST_USER_1_PASS;
        $this->server_base_url    = 'https://localhost/soap/?wsdl';
        $this->server_project_url = 'https://localhost/soap/project/?wsdl';
        $this->base_wsdl          = '/soap/codendi.wsdl.php';
        $this->server_name        = 'localhost';
        $this->server_port        = '443';

        $this->context = stream_context_create([
            'ssl' => [
                // set some SSL/TLS specific options
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);

        // Connecting to the soap's tracker client
        $this->soap_base = new SoapClient(
            $this->server_base_url,
            array('cache_wsdl' => WSDL_CACHE_NONE, 'exceptions' => 1, 'trace' => 1, 'stream_context' => $this->context)
        );

        // Connecting to the soap's tracker client
        $this->soap_project = new SoapClient(
            $this->server_project_url,
            array('cache_wsdl' => WSDL_CACHE_NONE, 'stream_context' => $this->context)
        );

        if (self::$user_ids === null) {
            $this->initUserIds();
        }
    }

    /**
     * @return string
     */
    protected function getSessionHash()
    {
        // Establish connection to the server
        return $this->soap_base->login($this->login, $this->password)->session_hash;
    }

    private function initUserIds()
    {
        $session_hash = $this->getSessionHash();
        $all_user_information = $this->soap_base->checkUsersExistence($session_hash, [SOAP_TestDataBuilder::TEST_USER_1_NAME, SOAP_TestDataBuilder::TEST_USER_2_NAME]);

        self::$user_ids = [];

        foreach ($all_user_information as $user_information) {
            self::$user_ids[$user_information->username] = (int) $user_information->id;
        }
    }

    /**
     * @return null|int
     */
    protected function getUserID($username)
    {
        if (! isset(self::$user_ids[$username])) {
            return null;
        }
        return self::$user_ids[$username];
    }
}
