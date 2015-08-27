<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

namespace PHPWiki;

use PHPWikiDataBuilder;
use RestBase;

require_once dirname(__FILE__).'/bootstrap.php';

/**
 * @group PhpWikiPluginTests
 */
class ProjectTestPHPWiki extends RestBase {
    private function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(PHPWikiDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSPHPWiki() {
        $response = $this->getResponse($this->client->options('projects/'.PHPWikiDataBuilder::PROJECT_PUBLIC_ID.'/phpwiki_plugin'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETPHPWiki() {
        $response = $this->getResponse($this->client->get('projects/'.PHPWikiDataBuilder::PROJECT_PUBLIC_ID.'/phpwiki_plugin'));

        $expected_result = array(
            'pages' => array(
                0 => array(
                    'id'  => PHPWikiDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki_plugin/6097',
                    'name' => 'WithContent'
                ),
                1 => array(
                    'id'  => PHPWikiDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki_plugin/6100',
                    'name' => 'With Space'
                )
            )
        );

        $this->assertEquals($expected_result, $response->json());
    }

    public function testGETPHPWikiWithGoodPagename() {
        $response = $this->getResponse($this->client->get('projects/'.PHPWikiDataBuilder::PROJECT_PUBLIC_ID.'/phpwiki_plugin?pagename=WithContent'));

        $expected_result = array(
            'pages' => array(
                0 => array(
                    'id'  => PHPWikiDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki_plugin/6097',
                    'name' => 'WithContent'
                )
            )
        );

        $this->assertEquals($expected_result, $response->json());
    }

    public function testGETPHPWikiWithGoodPagenameAndASpace() {
        $response = $this->getResponse($this->client->get('projects/'.PHPWikiDataBuilder::PROJECT_PUBLIC_ID.'/phpwiki_plugin?pagename=With+Space'));

        $expected_result = array(
            'pages' => array(
                0 => array(
                    'id'  => PHPWikiDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki_plugin/6100',
                    'name' => 'With Space'
                )
            )
        );

        $this->assertEquals($expected_result, $response->json());
    }

    public function testGETPHPWikiWithGoodRelativePagename() {
        $response = $this->getResponse($this->client->get('projects/'.PHPWikiDataBuilder::PROJECT_PUBLIC_ID.'/phpwiki_plugin?pagename=With'));

        $expected_result = array(
            'pages' => array(
                0 => array(
                    'id'  => PHPWikiDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki_plugin/6097',
                    'name' => 'WithContent'
                ),
                1 => array(
                    'id'  => PHPWikiDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki_plugin/6100',
                    'name' => 'With Space'
                )
            )
        );

        $this->assertEquals($expected_result, $response->json());
    }

    public function testGETPHPWikiWithNotExistingPagename() {
        $response = $this->getResponse($this->client->get('projects/'.PHPWikiDataBuilder::PROJECT_PUBLIC_ID.'/phpwiki_plugin?pagename="no"'));

        $expected_result = array(
            'pages' => array()
        );

        $this->assertEquals($expected_result, $response->json());
    }
}