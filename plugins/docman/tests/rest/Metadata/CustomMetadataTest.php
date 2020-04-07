<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Guzzle\Http\Client;
use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanHardcodedMetadataExecutionHelper;

class CustomMetadataTest extends DocmanHardcodedMetadataExecutionHelper
{
    public function testGetMetadataForProject(): array
    {
        $response = $this->getResponse($this->client->get('projects/' . $this->project_id . '/docman_metadata'));

        $this->assertSame(200, $response->getStatusCode());

        $json_result = $response->json();

        $this->assertMetadataForProject($json_result);

        return $json_result;
    }

    public function testGetMetadataForProjectWithRESTReadOnlyUser(): array
    {
        $response = $this->getResponse(
            $this->client->get('projects/' . $this->project_id . '/docman_metadata'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertSame(200, $response->getStatusCode());

        $json_result = $response->json();

        $this->assertMetadataForProject($json_result);

        return $json_result;
    }

    private function assertMetadataForProject(array $json_result): void
    {
        $text_metadata = $this->findMetadataByName($json_result, "text metadata");
        $list_metadata = $this->findMetadataByName($json_result, "list metadata");

        $this->assertEquals("text metadata", $text_metadata["name"]);
        $this->assertEquals("text", $text_metadata["type"]);
        $this->assertEquals(null, $text_metadata["allowed_list_values"]);

        $this->assertEquals("list metadata", $list_metadata["name"]);
        $this->assertEquals("list", $list_metadata["type"]);

        $list_values = $list_metadata["allowed_list_values"];
        $value = $this->findValueByValueName($list_values, "value 1");
        $value_two = $this->findValueByValueName($list_values, "value 2");

        $this->assertEquals("value 1", $value["value"]);
        $this->assertEquals("value 2", $value_two["value"]);
    }

    /**
     * @depends testGetRootId
     * @depends testGetMetadataForProject
     */
    public function testEmptyCanManipulateMetadata(int $root_id, array $project_metadata): void
    {
        $text_metadata = $this->findMetadataByName($project_metadata, "text metadata");
        $list_metadata = $this->findMetadataByName($project_metadata, "list metadata");
        $other_list_metadata = $this->findMetadataByName($project_metadata, "other list metadata");

        $list_values   = $list_metadata["allowed_list_values"];
        $value         = $this->findValueByValueName($list_values, "value 1");
        $updated_value = $this->findValueByValueName($list_values, "value 2");

        $query = json_encode(
            [
                'title'    => 'empty with custom metadata',
                'metadata' => [
                    [
                        'short_name' => $text_metadata['short_name'],
                        'value' => 'aaaaa'
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value' => $value['id']
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => []
                    ]
                ]
            ]
        );

        $response1_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_folders/' . $root_id . '/empties', null, $query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response1_with_rest_read_only_user->getStatusCode());

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/empties', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());

        $updated_query = json_encode(
            [
                'title'    => 'empty with custom metadata',
                'owner_id' => 101,
                'metadata' => [
                    [
                        'short_name' => $text_metadata['short_name'],
                        'value'      => 'updated value'
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value'      => $updated_value['id']
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => []
                    ]
                ]
            ]
        );

        $created_document_id = $response1->json()['id'];

        $response2_with_rest_read_only_user = $this->getResponse(
            $this->client->put('docman_empty_documents/' . $created_document_id . '/metadata', null, $updated_query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response2_with_rest_read_only_user->getStatusCode());

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->put('docman_empty_documents/' . $created_document_id . '/metadata', null, $updated_query)
        );
        $this->assertEquals(200, $response2->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $created_document_id),
            DocmanDataBuilder::ADMIN_USER_NAME
        );

        $updated_content  = $response->json();
        $updated_metadata = $updated_content['metadata'];

        $updated_text_metadata       = $this->findMetadataByName($updated_metadata, "text metadata");
        $updated_list_metadata       = $this->findMetadataByName($updated_metadata, "list metadata");
        $updated_other_list_metadata = $this->findMetadataByName($updated_metadata, "other list metadata");

        $this->assertEquals('updated value', $updated_text_metadata['value']);
        $this->assertEquals($updated_value['id'], $updated_list_metadata['list_value'][0]['id']);
        $this->assertEquals([], $updated_other_list_metadata['list_value']);

        $response_with_rest_read_only_user = $this->getResponse(
            $this->client->delete('docman_empty_documents/' . $created_document_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_empty_documents/' . $created_document_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     * @depends testGetMetadataForProject
     */
    public function testFileCanManipulateMetadata(int $root_id, array $project_metadata): void
    {
        $text_metadata       = $this->findMetadataByName($project_metadata, "text metadata");
        $list_metadata       = $this->findMetadataByName($project_metadata, "list metadata");
        $other_list_metadata = $this->findMetadataByName($project_metadata, "other list metadata");

        $other_list_values = $other_list_metadata["allowed_list_values"];
        $other_value       = $this->findValueByValueName($other_list_values, "list A");
        $other_updated_value       = $this->findValueByValueName($other_list_values, "list B");

        $file_name = 'file_' . random_int(0, 100000);
        $file_size = 123;
        $query     = json_encode(
            [
                'title'           => $file_name,
                'file_properties' => ['file_name' => 'NEW F', 'file_size' => $file_size],
                'metadata'        => [
                    [
                        'short_name' => $text_metadata['short_name'],
                        'value'      => 'bbbbb'
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value'      => null
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => [(int) $other_value['id']]
                    ]
                ]
            ]
        );

        $response1_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response1_with_rest_read_only_user->getStatusCode());

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertNotEmpty($response1->json()['file_properties']['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertSame(
            $response1->json()['file_properties']['upload_href'],
            $response2->json()['file_properties']['upload_href']
        );

        $general_use_http_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $general_use_http_client->setCurlMulti($this->client->getCurlMulti());
        $general_use_http_client->setSslVerification(false, false, false);
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->patch(
                $response1->json()['file_properties']['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                $file_content
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset')->toArray());

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);

        $file_content_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->get($file_item_response->json()['file_properties']['download_href'])
        );
        $this->assertEquals(200, $file_content_response->getStatusCode());
        $this->assertEquals($file_content, $file_content_response->getBody());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $file_item_response->json()['id']),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $item     = $response->json();

        $text_metadata       = $this->findMetadataByName($item["metadata"], "text metadata");
        $list_metadata       = $this->findMetadataByName($item["metadata"], "list metadata");
        $other_list_metadata = $this->findMetadataByName($item["metadata"], "other list metadata");

        $this->assertEquals($text_metadata['value'], 'bbbbb');
        $this->assertEquals($text_metadata['list_value'], null);

        $this->assertEquals($list_metadata['value'], null);
        $this->assertEquals($list_metadata['list_value'], []);

        $this->assertEquals($other_list_metadata['value'], null);
        $this->assertEquals($other_list_metadata['list_value'][0]['id'], (int) $other_value['id']);

        $updated_query = json_encode(
            [
                'title'    => 'file with custom metadata',
                'owner_id' => 101,
                'metadata' => [
                    [
                        'short_name' => $text_metadata['short_name'],
                        'value'      => 'updated value'
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value'      => null
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => [(int) $other_updated_value['id']]
                    ]
                ]
            ]
        );

        $created_document_id = $response1->json()['id'];

        $response2_with_rest_read_only_user = $this->getResponse(
            $this->client->put('docman_files/' . $created_document_id . '/metadata', null, $updated_query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response2_with_rest_read_only_user->getStatusCode());

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->put('docman_files/' . $created_document_id . '/metadata', null, $updated_query)
        );
        $this->assertEquals(200, $response2->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $created_document_id),
            DocmanDataBuilder::ADMIN_USER_NAME
        );

        $updated_content  = $response->json();
        $updated_metadata = $updated_content['metadata'];

        $updated_text_metadata       = $this->findMetadataByName($updated_metadata, "text metadata");
        $updated_list_metadata       = $this->findMetadataByName($updated_metadata, "list metadata");
        $updated_other_list_metadata = $this->findMetadataByName($updated_metadata, "other list metadata");

        $this->assertEquals('updated value', $updated_text_metadata['value']);
        $this->assertEquals([], $updated_list_metadata['list_value']);
        $this->assertEquals($other_updated_value['id'], $updated_other_list_metadata['list_value'][0]['id']);

        $response_delete_with_rest_read_only_user = $this->getResponse(
            $this->client->delete('docman_files/' . $created_document_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_delete_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_files/' . $created_document_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     * @depends testGetMetadataForProject
     */
    public function testFolderCanManipulateMetadata(int $root_id, array $project_metadata): void
    {
        $text_metadata = $this->findMetadataByName($project_metadata, "text metadata");
        $list_metadata = $this->findMetadataByName($project_metadata, "list metadata");
        $other_list_metadata = $this->findMetadataByName($project_metadata, "other list metadata");

        $query = json_encode(
            [
                'title'    => 'new folder'
            ]
        );

        $folder_response_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_folders/' . $root_id . '/folders', null, $query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $folder_response_with_rest_read_only_user->getStatusCode());

        $folder_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/folders', null, $query)
        );
        $this->assertEquals(201, $folder_response->getStatusCode());
        $created_document_id = $folder_response->json()['id'];

        $query = json_encode(
            [
                'title'    => 'an empty document'
            ]
        );

        $empty_response_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_folders/' . $created_document_id . '/empties', null, $query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $empty_response_with_rest_read_only_user->getStatusCode());

        $empty_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $created_document_id . '/empties', null, $query)
        );
        $this->assertEquals(201, $empty_response->getStatusCode());
        $empty_id = $folder_response->json()['id'];

        $updated_query = json_encode(
            [
                'title'    => 'folder with custom metadata',
                'owner_id' => 101,
                'status'   => [
                    'value' => 'none',
                    'recursion' => 'none'
                ],
                'metadata' => [
                    [
                        'short_name' => $text_metadata['short_name'],
                        'value'      => 'updated value',
                        'recursion' => 'all_items'
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value'      => "",
                        'recursion' => 'none'
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => [],
                        'recursion' => 'none'
                    ]
                ]
            ]
        );

        $response2_with_rest_read_only_user = $this->getResponse(
            $this->client->put('docman_folders/' . $created_document_id . '/metadata', null, $updated_query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response2_with_rest_read_only_user->getStatusCode());

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->put('docman_folders/' . $created_document_id . '/metadata', null, $updated_query)
        );
        $this->assertEquals(200, $response2->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $created_document_id),
            DocmanDataBuilder::ADMIN_USER_NAME
        );

        $folder_content  = $response->json();
        $folder_content = $folder_content['metadata'];

        $folder_text_metadata       = $this->findMetadataByName($folder_content, "text metadata");
        $this->assertEquals('updated value', $folder_text_metadata['value']);

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $empty_id),
            DocmanDataBuilder::ADMIN_USER_NAME
        );

        $empty_content  = $response->json();
        $empty_content = $empty_content['metadata'];

        $empty_text_metadata       = $this->findMetadataByName($empty_content, "text metadata");
        $this->assertEquals('updated value', $empty_text_metadata['value']);

        $response_delete_with_rest_read_only_user = $this->getResponse(
            $this->client->delete('docman_folders/' . $created_document_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_delete_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_folders/' . $created_document_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsProjectMetadata(): void
    {
        $response = $this->getResponse(
            $this->client->options('projects/' . $this->project_id . '/docman_metadata'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsProjectMetadataWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->options('projects/' . $this->project_id . '/docman_metadata'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @return array | null Found item. null otherwise.
     */
    private function findValueByValueName(array $list_values, string $name): ?array
    {
        $index = array_search($name, array_column($list_values, 'value'));
        if ($index === false) {
            return null;
        }
        return $list_values[$index];
    }
}
