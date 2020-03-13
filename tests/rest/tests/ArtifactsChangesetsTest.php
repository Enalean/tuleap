<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

use Guzzle\Http\Message\Response;

/**
 * @group ArtifactsChangesets
 */
class ArtifactsChangesetsTest extends RestBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    private $artifact_resource;

    public function setUp() : void
    {
        parent::setUp();

        $artifacts  = $this->getArtifactIdsIndexedByTitle('private-member', 'task');
        $artifact_id = $artifacts['A task to do'];
        $this->artifact_resource['uri'] = 'artifacts/' . $artifact_id;
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6371
     */
    public function testOptionsArtifactId()
    {
        $response = $this->getResponse($this->client->options($this->artifact_resource['uri'] . '/changesets'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6371
     */
    public function testOptionsArtifactIdWithUserRESTReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->client->options($this->artifact_resource['uri'] . '/changesets'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(
            ['OPTIONS', 'GET'],
            $response->getHeader('Allow')->normalize()->toArray()
        );
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6371
     */
    public function testGetChangesetsHasPagination()
    {
        $response = $this->getResponse($this->client->get($this->artifact_resource['uri'] . '/changesets?offset=2&limit=10'));

        $this->assertChangeset($response);
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6371
     */
    public function testGetChangesetsHasPaginationWithUserRESTReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->client->get($this->artifact_resource['uri'] . '/changesets?offset=2&limit=10'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertChangeset($response);
    }

    /**
     * @param $response
     * @throws Exception
     */
    private function assertChangeset(Response $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $changesets = $response->json();
        $this->assertCount(1, $changesets);
        $this->assertEquals("Awesome changes", $changesets[0]['last_comment']['body']);

        $fields = $changesets[0]['values'];
        foreach ($fields as $field) {
            switch ($field['type']) {
                case 'string':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_string($field['value']));
                    break;
                case 'cross':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_array($field['value']));
                    break;
                case 'text':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_string($field['value']));
                    $this->assertTrue(is_string($field['format']));
                    $this->assertTrue($field['format'] == 'text' || $field['format'] == 'html');
                    break;
                case 'msb':
                case 'sb':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_array($field['values']));
                    $this->assertTrue(is_array($field['bind_value_ids']));
                    break;
                case 'computed':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_int($field['value']) || is_null($field['value']));
                    break;
                case 'aid':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_int($field['value']));
                    break;
                case 'luby':
                case 'subby':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_array($field['value']));
                    $this->assertTrue(array_key_exists('display_name', $field['value']));
                    $this->assertTrue(array_key_exists('avatar_url', $field['value']));
                    break;
                case 'lud':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(DateTime::createFromFormat('Y-m-d\TH:i:sT', $field['value']) !== false);
                    break;
                case 'subon':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(DateTime::createFromFormat('Y-m-d\TH:i:sT', $field['value']) !== false);
                    break;
                default:
                    throw new Exception('You need to update this test for the field: ' . print_r($field, true));
            }
        }

        $pagination_offset = $response->getHeader('X-PAGINATION-OFFSET')->normalize()->toArray();
        $this->assertEquals($pagination_offset[0], 2);

        $pagination_size = $response->getHeader('X-PAGINATION-SIZE')->normalize()->toArray();
        $this->assertEquals($pagination_size[0], 3);
    }
}
