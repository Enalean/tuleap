<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
 *
 */

use Tuleap\REST\ArtifactFileBase;

/**
 * @group ArtifactFilesTest
 */
class ArtifactFilesTest extends ArtifactFileBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private static $DEFAULT_QUOTA = 67108864;

    protected $first_file;
    private $second_file;
    protected $second_chunk = 'with more data';
    private $third_file;

    protected function getResponseForDifferentUser(\Psr\Http\Message\RequestInterface $request)
    {
        return $this->getResponse($request, REST_TestDataBuilder::TEST_USER_2_NAME);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->first_file = [
            'name'        => 'my file',
            'description' => 'a very LARGE file',
            'mimetype'    => 'text/plain',
            'content'     => base64_encode('a very LARGE file'),
        ];

        $this->second_file = [
            'name'        => 'my file 2',
            'description' => 'a very small file',
            'mimetype'    => 'text/plain',
            'content'     => base64_encode('a very small file'),
        ];

        $this->third_file = [
            'name'        => 'my file 3',
            'description' => 'a very small file',
            'mimetype'    => 'text/plain',
            'content'     => base64_encode('a very small file'),
        ];
    }

    public function testOptionsArtifactFiles()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artifact_temporary_files'));
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(0, $response->getHeaderLine('X-DISK-USAGE'));
        $this->assertEquals(self::$DEFAULT_QUOTA, $response->getHeaderLine('X-QUOTA'));
    }

    public function testPostArtifactFile()
    {
        $post_resource = json_encode($this->first_file);

        $request  = $this->request_factory->createRequest('POST', 'artifact_temporary_files')->withBody(
            $this->stream_factory->createStream($post_resource)
        );
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 201);

        $file_representation = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertGreaterThan(0, $file_representation['id']);
        $this->assertEquals($file_representation['name'], 'my file');
        $this->assertEquals($file_representation['description'], 'a very LARGE file');
        $this->assertEquals($file_representation['type'], 'text/plain');
        $this->assertEquals($file_representation['size'], strlen('a very LARGE file'));
        $this->assertEquals($file_representation['submitted_by'], $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]);

        $this->assertEquals(17, $response->getHeaderLine('X-DISK-USAGE'));
        $this->assertEquals(self::$DEFAULT_QUOTA, $response->getHeaderLine('X-QUOTA'));

        return $file_representation['id'];
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testArtifactTemporaryFilesGetId($file_id)
    {
        $request  = $this->request_factory->createRequest('GET', 'artifact_temporary_files/' . $file_id);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $data = $json['data'];

        $this->assertEquals($this->first_file['content'], $data);
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testPutArtifactFileId($file_id)
    {
        $second_chunk = 'with more data';

        $put_resource = json_encode([
            'content' => base64_encode($second_chunk),
            'offset'  => "2",
        ]);

        $request  = $this->request_factory->createRequest('PUT', 'artifact_temporary_files/' . $file_id)
            ->withBody($this->stream_factory->createStream($put_resource));
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $file_representation = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($file_representation['name'], 'my file');
        $this->assertEquals($file_representation['description'], 'a very LARGE file');
        $this->assertEquals($file_representation['type'], 'text/plain');
        $this->assertEquals($file_representation['size'], strlen('a very LARGE file' . $second_chunk));
        $this->assertEquals($file_representation['submitted_by'], $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]);

        return $file_id;
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testPutArtifactIdIsForbiddenForADifferentUser($file_id)
    {
        $second_chunk = 'with more data';

        $put_resource = json_encode([
            'content' => base64_encode($second_chunk),
            'offset'  => "2",
        ]);

        $request = $this->request_factory->createRequest('PUT', 'artifact_temporary_files/' . $file_id)
            ->withBody($this->stream_factory->createStream($put_resource));

        $response = $this->getResponseForDifferentUser($request);
        $this->assertEquals($response->getStatusCode(), 401);
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testPutArtifactIdThrowsErrorForAWrongOffset($file_id)
    {
        $second_chunk = 'with more data';

        $put_resource = json_encode([
            'content' => base64_encode($second_chunk),
            'offset'  => "45",
        ]);

        $request = $this->request_factory->createRequest('PUT', 'artifact_temporary_files/' . $file_id)
            ->withBody($this->stream_factory->createStream($put_resource));

        $response = $this->getResponse($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertArrayHasKey('X-DISK-USAGE', $response->getHeaders());
    }

    public function testPutArtifactIdThrowsErrorForInvalidFile()
    {
        $file_id = 1453655565245655;
        $chunk   = 'with more data';

        $put_resource = json_encode([
            'content' => base64_encode($chunk),
            'offset'  => "2",
        ]);

        $request = $this->request_factory->createRequest('PUT', 'artifact_temporary_files/' . $file_id)
            ->withBody($this->stream_factory->createStream($put_resource));

        $response = $this->getResponse($request);
        $this->assertEquals($response->getStatusCode(), 404);
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testArtifactTemporaryFilesGet($file_id)
    {
        $request  = $this->request_factory->createRequest('GET', 'artifact_temporary_files');
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $json);
        $this->assertEquals($file_id, $json[0]['id']);
        $this->assertEquals($this->first_file['name'], $json[0]['name']);
        $this->assertEquals($this->first_file['description'], $json[0]['description']);

        $this->assertEquals(1, $response->getHeaderLine('X-PAGINATION-SIZE'));
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testOptionsArtifactTemporaryFilesId($file_id)
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artifact_temporary_files/' . $file_id));

        $this->assertEquals($response->getStatusCode(), 200);
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testOptionsArtifactIdIsAllowedForADifferentUser($file_id)
    {
        $request  = $this->request_factory->createRequest('OPTIONS', 'artifact_temporary_files/' . $file_id);
        $response = $this->getResponseForDifferentUser($request);
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testOptionsArtifactIdWithUserRESTReadOnlyAdmin($file_id)
    {
        $request  = $this->request_factory->createRequest('OPTIONS', 'artifact_temporary_files/' . $file_id);
        $response = $this->getResponse($request, REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAttachFileToPostArtifact()
    {
        $post_resource       = json_encode($this->third_file);
        $request             = $this->request_factory->createRequest('POST', 'artifact_temporary_files')
            ->withBody($this->stream_factory->createStream($post_resource));
        $response            = $this->getResponse($request);
        $file_representation = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $structure = $this->tracker_representations[$this->user_stories_tracker_id];
        foreach ($structure['fields'] as $field) {
            if ($field['type'] == 'file') {
                $field_id_file = $field['field_id'];
            }
            if ($field['label'] == 'I want to') {
                $field_id_summary = $field['field_id'];
            }
            if ($field['label'] == 'Status') {
                $field_id_status = $field['field_id'];
            }
        }
        $this->assertNotNull($field_id_file);
        $this->assertNotNull($field_id_summary);
        $this->assertNotNull($field_id_status);

        $params = json_encode([
            'tracker' => [
                'id'  => $this->user_stories_tracker_id,
                'uri' => 'trackers/' . $this->user_stories_tracker_id,
            ],
            'values' => [
                [
                    'field_id' => $field_id_summary,
                    'value'    => 'I want 2',
                ],
                [
                    'field_id'       => $field_id_status,
                    'bind_value_ids' => [205],
                ],
                [
                    'field_id' => $field_id_file,
                    'value'    => [$file_representation['id']],
                ],
            ],
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'artifacts')
            ->withBody($this->stream_factory->createStream($params)));
        $this->assertEquals($response->getStatusCode(), 201);
        $posted_artifact = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $response        = $this->getResponse($this->request_factory->createRequest('GET', 'artifacts/' . $posted_artifact['id']));
        $posted_artifact = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(10, $posted_artifact['values']);

        $file_exists = false;
        foreach ($posted_artifact['values'] as $field) {
            if ($field['type'] == 'file') {
                $this->assertCount(1, $field['file_descriptions']);
                $this->assertEquals($field['file_descriptions'][0]['description'], $this->third_file['description']);
                $this->assertEquals($field['file_descriptions'][0]['type'], $this->third_file['mimetype']);

                $file_exists = true;
            }
        }

        $this->assertTrue($file_exists);

        return $parameters = [
            'artifact_id' => $posted_artifact['id'],
            'field_id'    => $field_id_file,
            'file_id'     => $file_representation['id'],
        ];
    }

    /**
     * @depends testAttachFileToPostArtifact
     */
    public function testAttachementHasHTMLURL(array $parameters)
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'artifacts/' . $parameters['artifact_id']));
        $artifact = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $value = $this->getAttachementFieldValues($artifact['values'], $parameters);
        $this->assertNotNull($value);

        $show_url = $this->getHTMLUrl($value['file_descriptions']);

        $this->assertNotNull($show_url);
        $this->assertEquals(
            $show_url,
            '/plugins/tracker/attachments/' . $parameters['file_id'] . '-my%20file%203'
        );

        $preview_url = $this->getPreviewUrl($value['file_descriptions']);

        $this->assertNull($preview_url);
    }

    private function getPreviewUrl($files)
    {
        return $files[0]['html_preview_url'];
    }

    private function getHTMLUrl($files)
    {
        return $files[0]['html_url'];
    }

    private function getAttachementFieldValues($values, $parameters)
    {
        foreach ($values as $value) {
            if ($value['field_id'] == $parameters['field_id']) {
                return $value;
            }
        }
    }

    /**
     * @depends testPutArtifactFileId
     */
    public function testAttachFileToPutArtifact($file_id)
    {
        $artifact_id = $this->story_artifact_ids[1];

        $structure = $this->tracker_representations[$this->user_stories_tracker_id];
        foreach ($structure['fields'] as $field) {
            if ($field['type'] == 'file') {
                $field_id = $field['field_id'];
                break;
            }
        }
        $this->assertNotNull($field_id);

        $params = json_encode([
            'values' => [
                [
                    'field_id' => $field_id,
                    'value'    => [$file_id],
                ],
            ],
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artifacts/' . $artifact_id)
                ->withBody($this->stream_factory->createStream($params))
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $response        = $this->getResponse($this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id));
        $posted_artifact = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(9, $posted_artifact['values']);

        $file_exists = false;
        foreach ($posted_artifact['values'] as $field) {
            if ($field['type'] == 'file') {
                $this->assertCount(1, $field['file_descriptions']);
                $this->assertEquals($field['file_descriptions'][0]['description'], $this->first_file['description']);
                $this->assertEquals($field['file_descriptions'][0]['type'], $this->first_file['mimetype']);

                $file_exists = true;
            }
        }

        $this->assertTrue($file_exists);

        return $file_id;
    }

    /**
     * @depends testAttachFileToPutArtifact
     */
    public function testArtifactAttachedFilesGetId($file_id)
    {
        $request  = $this->request_factory->createRequest('GET', 'artifact_files/' . $file_id);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $data = $json['data'];

        $expected = base64_encode(base64_decode($this->first_file['content']) . $this->second_chunk);

        $this->assertEquals($expected, $data);
    }

    /**
     * @depends testAttachFileToPutArtifact
     */
    public function testArtifactAttachedFilesGetIdWithUserRESTReadOnlyAdmin($file_id)
    {
        $request  = $this->request_factory->createRequest('GET', 'artifact_files/' . $file_id);
        $response = $this->getResponse($request, REST_TestDataBuilder::TEST_BOT_USER_NAME);

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $data = $json['data'];

        $expected = base64_encode(base64_decode($this->first_file['content']) . $this->second_chunk);

        $this->assertEquals($expected, $data);
    }

    /**
     * @depends testAttachFileToPutArtifact
     */
    public function testOptionsArtifactAttachedFilesId($file_id)
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artifact_files/' . $file_id));

        $this->assertEquals($response->getStatusCode(), 200);
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    /**
     * @depends testAttachFileToPutArtifact
     */
    public function testOptionsArtifactAttachedFilesIdUserRESTReadOnlyAdmin($file_id)
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'artifact_files/' . $file_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testArtifactTemporaryFilesDeleteId()
    {
        $post_resource       = json_encode($this->second_file);
        $request             = $this->request_factory->createRequest('POST', 'artifact_temporary_files')
            ->withBody($this->stream_factory->createStream($post_resource));
        $response            = $this->getResponse($request);
        $file_representation = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $response = $this->getResponse($this->request_factory->createRequest('DELETE', 'artifact_temporary_files/' . $file_representation['id']));
        $this->assertEquals($response->getStatusCode(), 200);

        $request  = $this->request_factory->createRequest('GET', 'artifact_temporary_files');
        $response = $this->getResponse($request);
        $this->assertEquals(0, $response->getHeaderLine('X-PAGINATION-SIZE'));
    }
}
