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

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanHardcodedMetadataExecutionHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class CustomMetadataTest extends DocmanHardcodedMetadataExecutionHelper
{
    public function testGetMetadataForProject(): array
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_id . '/docman_metadata'));

        self::assertSame(200, $response->getStatusCode());

        $json_result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertMetadataForProject($json_result);

        return $json_result;
    }

    public function testGetMetadataForProjectWithRESTReadOnlyUser(): array
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_id . '/docman_metadata'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(200, $response->getStatusCode());

        $json_result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertMetadataForProject($json_result);

        return $json_result;
    }

    private function assertMetadataForProject(array $json_result): void
    {
        $text_metadata = $this->findMetadataByName($json_result, 'text metadata');
        $list_metadata = $this->findMetadataByName($json_result, 'list metadata');

        $this->assertEquals('text metadata', $text_metadata['name']);
        $this->assertEquals('text', $text_metadata['type']);
        $this->assertEquals(null, $text_metadata['allowed_list_values']);

        $this->assertEquals('list metadata', $list_metadata['name']);
        $this->assertEquals('list', $list_metadata['type']);

        $list_values   = $list_metadata['allowed_list_values'];
        $value         = $this->findValueByValueName($list_values, 'value 1');
        $value_two     = $this->findValueByValueName($list_values, 'value 2');
        $value_deleted = $this->findValueByValueName($list_values, 'value 3');

        $this->assertEquals('value 1', $value['value']);
        $this->assertEquals('value 2', $value_two['value']);
        $this->assertNull($value_deleted);
    }

    /**
     * @depends testGetRootId
     * @depends testGetMetadataForProject
     */
    public function testEmptyCanManipulateMetadata(int $root_id, array $project_metadata): void
    {
        $text_metadata       = $this->findMetadataByName($project_metadata, 'text metadata');
        $list_metadata       = $this->findMetadataByName($project_metadata, 'list metadata');
        $other_list_metadata = $this->findMetadataByName($project_metadata, 'other list metadata');

        $list_values   = $list_metadata['allowed_list_values'];
        $value         = $this->findValueByValueName($list_values, 'value 1');
        $updated_value = $this->findValueByValueName($list_values, 'value 2');

        $query = json_encode(
            [
                'title'    => 'empty with custom metadata',
                'metadata' => [
                    [
                        'short_name' => $text_metadata['short_name'],
                        'value' => 'aaaaa',
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value' => $value['id'],
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => [],
                    ],
                ],
            ]
        );

        $response1_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/empties')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response1_with_rest_read_only_user->getStatusCode());

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/empties')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(201, $response1->getStatusCode());

        $updated_query = json_encode(
            [
                'title'    => 'empty with custom metadata',
                'owner_id' => 101,
                'metadata' => [
                    [
                        'short_name' => $text_metadata['short_name'],
                        'value'      => 'updated value',
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value'      => $updated_value['id'],
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => [],
                    ],
                ],
            ]
        );

        $created_document_id = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $response2_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_empty_documents/' . $created_document_id . '/metadata')->withBody($this->stream_factory->createStream($updated_query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response2_with_rest_read_only_user->getStatusCode());

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_empty_documents/' . $created_document_id . '/metadata')->withBody($this->stream_factory->createStream($updated_query))
        );
        $this->assertEquals(200, $response2->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $created_document_id),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $updated_content  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $updated_metadata = $updated_content['metadata'];

        $updated_text_metadata       = $this->findMetadataByName($updated_metadata, 'text metadata');
        $updated_list_metadata       = $this->findMetadataByName($updated_metadata, 'list metadata');
        $updated_other_list_metadata = $this->findMetadataByName($updated_metadata, 'other list metadata');

        $this->assertEquals('updated value', $updated_text_metadata['value']);
        $this->assertEquals($updated_value['id'], $updated_list_metadata['list_value'][0]['id']);
        $this->assertEquals([], $updated_other_list_metadata['list_value']);

        $response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $created_document_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $created_document_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     * @depends testGetMetadataForProject
     */
    public function testFileCanManipulateMetadata(int $root_id, array $project_metadata): void
    {
        $text_metadata       = $this->findMetadataByName($project_metadata, 'text metadata');
        $list_metadata       = $this->findMetadataByName($project_metadata, 'list metadata');
        $other_list_metadata = $this->findMetadataByName($project_metadata, 'other list metadata');

        $other_list_values   = $other_list_metadata['allowed_list_values'];
        $other_value         = $this->findValueByValueName($other_list_values, 'list A');
        $other_updated_value = $this->findValueByValueName($other_list_values, 'list B');

        $file_name = 'file_' . random_int(0, 100000);
        $file_size = 123;
        $query     = json_encode(
            [
                'title'           => $file_name,
                'file_properties' => ['file_name' => 'NEW F', 'file_size' => $file_size],
                'metadata'        => [
                    [
                        'short_name' => $text_metadata['short_name'],
                        'value'      => 'bbbbb',
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value'      => null,
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => [(int) $other_value['id']],
                    ],
                ],
            ]
        );

        $response1_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response1_with_rest_read_only_user->getStatusCode());

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response1->getStatusCode());
        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($response1_json['file_properties']['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(201, $response1->getStatusCode());
        self::assertSame(
            $response1_json['file_properties']['upload_href'],
            json_decode($response2->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['file_properties']['upload_href']
        );

        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', $response1_json['file_properties']['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $response1_json['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $file_item_response_json = json_decode($file_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('file', $file_item_response_json['type']);

        $file_content_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $file_item_response_json['file_properties']['download_href'])
        );
        $this->assertEquals(200, $file_content_response->getStatusCode());
        $this->assertEquals($file_content, $file_content_response->getBody());

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_item_response_json['id']),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $item     = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $text_metadata       = $this->findMetadataByName($item['metadata'], 'text metadata');
        $list_metadata       = $this->findMetadataByName($item['metadata'], 'list metadata');
        $other_list_metadata = $this->findMetadataByName($item['metadata'], 'other list metadata');

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
                        'value'      => 'updated value',
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value'      => null,
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => [(int) $other_updated_value['id']],
                    ],
                ],
            ]
        );

        $created_document_id = $response1_json['id'];

        $response2_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_files/' . $created_document_id . '/metadata')->withBody($this->stream_factory->createStream($updated_query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response2_with_rest_read_only_user->getStatusCode());

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_files/' . $created_document_id . '/metadata')->withBody($this->stream_factory->createStream($updated_query))
        );
        $this->assertEquals(200, $response2->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $created_document_id),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $updated_content  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $updated_metadata = $updated_content['metadata'];

        $updated_text_metadata       = $this->findMetadataByName($updated_metadata, 'text metadata');
        $updated_list_metadata       = $this->findMetadataByName($updated_metadata, 'list metadata');
        $updated_other_list_metadata = $this->findMetadataByName($updated_metadata, 'other list metadata');

        $this->assertEquals('updated value', $updated_text_metadata['value']);
        $this->assertEquals([], $updated_list_metadata['list_value']);
        $this->assertEquals($other_updated_value['id'], $updated_other_list_metadata['list_value'][0]['id']);

        $response_delete_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $created_document_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_delete_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $created_document_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     * @depends testGetMetadataForProject
     */
    public function testFolderCanManipulateMetadata(int $root_id, array $project_metadata): void
    {
        $text_metadata       = $this->findMetadataByName($project_metadata, 'text metadata');
        $list_metadata       = $this->findMetadataByName($project_metadata, 'list metadata');
        $other_list_metadata = $this->findMetadataByName($project_metadata, 'other list metadata');

        $query = json_encode(
            [
                'title'    => 'new folder',
            ]
        );

        $folder_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/folders')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $folder_response_with_rest_read_only_user->getStatusCode());

        $folder_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/folders')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(201, $folder_response->getStatusCode());
        $folder_response_json = json_decode($folder_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $created_document_id  = $folder_response_json['id'];

        $query = json_encode(
            [
                'title'    => 'an empty document',
            ]
        );

        $empty_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $created_document_id . '/empties')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $empty_response_with_rest_read_only_user->getStatusCode());

        $empty_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $created_document_id . '/empties')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(201, $empty_response->getStatusCode());
        $empty_id = $folder_response_json['id'];

        $updated_query = json_encode(
            [
                'title'    => 'folder with custom metadata',
                'owner_id' => 101,
                'status'   => [
                    'value' => 'none',
                    'recursion' => 'none',
                ],
                'metadata' => [
                    [
                        'short_name' => $text_metadata['short_name'],
                        'value'      => 'updated value',
                        'recursion' => 'all_items',
                    ],
                    [
                        'short_name' => $list_metadata['short_name'],
                        'value'      => '',
                        'recursion' => 'none',
                    ],
                    [
                        'short_name' => $other_list_metadata['short_name'],
                        'list_value' => [],
                        'recursion' => 'none',
                    ],
                ],
            ]
        );

        $response2_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_folders/' . $created_document_id . '/metadata')->withBody($this->stream_factory->createStream($updated_query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response2_with_rest_read_only_user->getStatusCode());

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_folders/' . $created_document_id . '/metadata')->withBody($this->stream_factory->createStream($updated_query))
        );
        $this->assertEquals(200, $response2->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $created_document_id),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $folder_content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $folder_content = $folder_content['metadata'];

        $folder_text_metadata = $this->findMetadataByName($folder_content, 'text metadata');
        $this->assertEquals('updated value', $folder_text_metadata['value']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $empty_id),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $empty_content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $empty_content = $empty_content['metadata'];

        $empty_text_metadata = $this->findMetadataByName($empty_content, 'text metadata');
        $this->assertEquals('updated value', $empty_text_metadata['value']);

        $response_delete_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . $created_document_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_delete_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . $created_document_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsProjectMetadata(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_id . '/docman_metadata'),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsProjectMetadataWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_id . '/docman_metadata'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
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
