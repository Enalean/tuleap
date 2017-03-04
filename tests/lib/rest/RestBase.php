<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All rights reserved
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

require_once dirname(__FILE__).'/../autoload.php';
require_once 'common/autoload.php';

use \Guzzle\Http\Client;
use \Test\Rest\RequestWrapper;

class RestBase extends PHPUnit_Framework_TestCase {

    protected $base_url  = 'http://localhost/api/v1';
    private   $setup_url = 'http://localhost/api/v1';

    /**
     * @var Client
     */
    protected $client;

    /**
    * @var Client
    */
    private $setup_client;

    /**
     * @var Client
     */
    protected $xml_client;

    /**
     * @var RequestWrapper
     */
    protected $rest_request;

    protected $project_private_member_id;
    protected $project_private_id;
    protected $project_public_id;

    protected $project_ids = array();

    public function __construct() {
        parent::__construct();
        if (isset($_ENV['TULEAP_HOST'])) {
            $this->base_url  = $_ENV['TULEAP_HOST'].'/api/v1';
            $this->setup_url = $_ENV['TULEAP_HOST'].'/api/v1';
        }

        $this->rest_request = new RequestWrapper();
    }

    public function setUp() {
        parent::setUp();

        $this->client       = new Client($this->base_url, array('ssl.certificate_authority' => 'system'));
        $this->setup_client = new Client($this->setup_url, array('ssl.certificate_authority' => 'system'));
        $this->xml_client   = new Client($this->base_url, array('ssl.certificate_authority' => 'system'));

        $this->client->setDefaultOption('headers/Accept', 'application/json');
        $this->client->setDefaultOption('headers/Content-Type', 'application/json');

        $this->xml_client->setDefaultOption('headers/Accept', 'application/xml');
        $this->xml_client->setDefaultOption('headers/Content-Type', 'application/xml; charset=UTF8');

        $this->setup_client->setDefaultOption('headers/Accept', 'application/json');
        $this->setup_client->setDefaultOption('headers/Content-Type', 'application/json');

        if (! $this->project_ids) {
            $this->initProjectIds();
        }

        $this->project_private_member_id = $this->getProjectId(REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_SHORTNAME);
        $this->project_private_id        = $this->getProjectId(REST_TestDataBuilder::PROJECT_PRIVATE_SHORTNAME);
        $this->project_public_id         = $this->getProjectId(REST_TestDataBuilder::PROJECT_PUBLIC_SHORTNAME);
    }

    protected function getResponseWithoutAuth($request) {
        return $this->rest_request->getResponseWithoutAuth($request);
    }

    protected function getResponseByName($name, $request) {
        return $this->rest_request->getResponseByName($name, $request);
    }

    protected function getResponseByToken(Rest_Token $token, $request) {
        return $this->rest_request->getResponseByToken($token, $request);
    }

    protected function getTokenForUserName($user_name) {
        return $this->rest_request->getTokenForUserName($user_name);
    }

    protected function getResponseByBasicAuth($username, $password, $request) {
        return $this->rest_request->getResponseByBasicAuth($username, $password, $request);
    }

    private function initProjectIds()
    {
        $query = http_build_query(
            array('limit' => 50)
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->setup_client->get("projects/?$query")
        );

        $projects = $response->json();

        foreach ($projects as $project) {
            $project_name = $project['shortname'];
            $project_id   = $project['id'];

            $this->project_ids[$project_name] = $project_id;
        }

    }

    private function getProjectId($project_short_name)
    {
        return $this->project_ids[$project_short_name];
    }
}
