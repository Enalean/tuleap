<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

require_once dirname(__FILE__).'/../lib/autoload.php';
require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group ArtifactFilesTest
 */
class ArtifactFilesTest extends RestBase {

    private $first_file;

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    protected function getResponseForDifferentUser($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_2_NAME),
            $request
        );
    }

    public function setUp() {
        parent::setUp();

        $this->first_file = array(
            'name'        => 'my file',
            'description' => 'a very LARGE file',
            'mimetype'    => 'text/plain',
            'content'     => base64_encode('a very LARGE file'),
        );
    }

    public function testOptionsArtifactFiles() {
        $response = $this->getResponse($this->client->options('artifact_temporary_files'));
        $this->assertEquals(array('OPTIONS', 'GET', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPostArtifactFile() {
        $post_resource = json_encode($this->first_file);

        $request  = $this->client->post('artifact_temporary_files', null, $post_resource);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $file_representation = $response->json();

        $this->assertGreaterThan(0, $file_representation['id']);
        $this->assertEquals($file_representation['name'], 'my file');
        $this->assertEquals($file_representation['description'], 'a very LARGE file');
        $this->assertEquals($file_representation['type'], 'text/plain');
        $this->assertEquals($file_representation['size'], strlen('a very LARGE file'));
        $this->assertEquals($file_representation['submitted_by'], TestDataBuilder::TEST_USER_1_ID);

        return $file_representation['id'];
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testPutArtifactFileId($file_id) {
        $second_chunk = 'with more data';

        $put_resource = json_encode(array(
            'content' => base64_encode($second_chunk),
            'offset'  => "2",
        ));

        $request  = $this->client->put('artifact_temporary_files/'.$file_id, null, $put_resource);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $file_representation = $response->json();

        $this->assertEquals($file_representation['name'], 'my file');
        $this->assertEquals($file_representation['description'], 'a very LARGE file');
        $this->assertEquals($file_representation['type'], 'text/plain');
        $this->assertEquals($file_representation['size'], strlen('a very LARGE file'.$second_chunk));
        $this->assertEquals($file_representation['submitted_by'], TestDataBuilder::TEST_USER_1_ID);

        return $file_id;
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testPutArtifactId_isForbiddenForADifferentUser($file_id) {
        $second_chunk = 'with more data';

        $put_resource = json_encode(array(
            'content' => base64_encode($second_chunk),
            'offset'  => "2",
        ));

        $request = $this->client->put('artifact_temporary_files/'.$file_id, null, $put_resource);

        $unauthorised = false;
        try {
            $this->getResponseForDifferentUser($request);
        } catch (Exception $e) {
            $unauthorised = true;
            $this->assertEquals($e->getResponse()->getStatusCode(), 401);
        }

        $this->assertTrue($unauthorised);
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testPutArtifactId_throwsErrorForAWrongOffset($file_id) {
        $second_chunk = 'with more data';

        $put_resource = json_encode(array(
            'content' => base64_encode($second_chunk),
            'offset'  => "45",
        ));

        $request = $this->client->put('artifact_temporary_files/'.$file_id, null, $put_resource);

        $error = false;
        try {
            $this->getResponse($request);
        } catch (Exception $e) {
            $error = true;
            $this->assertEquals($e->getResponse()->getStatusCode(), 406);
        }

        $this->assertTrue($error);
    }

    public function testPutArtifactId_throwsErrorForInvalidFile() {
        $file_id = 1453655565245655;
        $chunk   = 'with more data';

        $put_resource = json_encode(array(
            'content' => base64_encode($chunk),
            'offset'  => "2",
        ));

        $request = $this->client->put('artifact_temporary_files/'.$file_id, null, $put_resource);

        $error = false;
        try {
            $this->getResponse($request);
        } catch (Exception $e) {
            $error = true;
            $this->assertEquals($e->getResponse()->getStatusCode(), 404);
        }

        $this->assertTrue($error);
    }
//
// UNCOMMENT AS SOON AS ATTACHING FILES TO ARTIFACT IS WORKING
//
//    /**
//     * @depends testPutArtifactFileId
//     */
//    public function testAttachFileToArtifact($file_id) {
//        $artifact_id = TestDataBuilder::STORY_1_ARTIFACT_ID;
//
//        $request = $this->client->get('trackers/'. TestDataBuilder::USER_STORIES_TRACKER_ID);
//        $structure = json_decode($this->getResponse($request)->getBody(true), true);
//        foreach ($structure['fields'] as $field) {
//            if ($field['type'] == 'file') {
//                $field_id = $field['field_id'];
//                break;
//            }
//        }
//        $this->assertNotNull($field_id);
//
//        $this->client->put('artifact/'. $artifact_id, null, json_encode(array(
//            'values' => array(
//                array(
//                    'field_id' => $field_id,
//                    'value'    => $file_id,
//                ),
//            ),
//        )));
//
//        return $file_id;
//    }
//
//    /**
//     * @depends testAttachFileToArtifact
//     */
//    public function testOptionsArtifactFilesId($file_id) {
//        $response = $this->getResponse($this->client->options('artifact_files/'.$file_id));
//
//        $this->assertEquals($response->getStatusCode(), 200);
//        $this->assertEquals(array('OPTIONS', 'GET', 'PUT', 'DELETE'), $response->getHeader('Allow')->normalize()->toArray());
//    }
//
//    /**
//     * @depends testAttachFileToArtifact
//     */
//    public function testOptionsArtifactId_isForbiddenForADifferentUser($file_id) {
//        $request = $this->client->options('artifact_files/'.$file_id);
//
//        $unauthorised = false;
//        try {
//            $response = $this->getResponseForDifferentUser($request);
//            var_dump($response->getBody(true));
//        } catch (Exception $e) {
//            $unauthorised = true;
//            $this->assertEquals($e->getResponse()->getStatusCode(), 401);
//        }
//
//        $this->assertTrue($unauthorised);
//    }
}