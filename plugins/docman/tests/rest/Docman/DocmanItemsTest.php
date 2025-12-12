<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use ProjectUGroup;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RESTTestDataBuilder;
use function Psl\Json\decode as json_decode;
use function Psl\Json\encode as json_encode;

#[DisableReturnValueGenerationForTestDoubles]
final class DocmanItemsTest extends DocmanTestExecutionHelper
{
    #[Depends('testGetRootId')]
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items_file    = $this->loadFolderContent($root_id, 'Folder');
        $folder_files  = $this->findItemByTitle($root_folder, 'Folder');
        $items_file_id = $folder_files['id'];
        $get           = $this->loadFolderContent($items_file_id, 'GET FO');

        $items_folder_1 = array_merge(
            $root_folder,
            $folder_files,
            $items_file,
            $get
        );

        $folder   = $this->findItemByTitle($items_folder_1, 'GET FO');
        $empty    = $this->findItemByTitle($items_folder_1, 'GET EM');
        $file     = $this->findItemByTitle($items_folder_1, 'GET F');
        $embedded = $this->findItemByTitle($items_folder_1, 'GET E');
        $link     = $this->findItemByTitle($items_folder_1, 'GET L');
        $wiki     = $this->findItemByTitle($items_folder_1, 'GET W');

        $response       = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $folder['id'] . '/docman_items')
        );
        $items_folder_2 = json_decode($response->getBody()->getContents());

        $items = array_merge($items_folder_1, $items_folder_2);

        $this->assertGetDocumentItems($items, $folder, $empty, $file, $link, $embedded, $wiki);

        return $items;
    }

    #[Depends('testGetRootIdWithUserRESTReadOnlyAdmin')]
    public function testGetDocumentItemsWithUserRESTReadOnlyAdmin(int $root_id): array
    {
        $root_folder = $this->loadRootFolderContent($root_id, RESTTestDataBuilder::TEST_BOT_USER_NAME);

        $items_file    = $this->loadFolderContent($root_id, 'Folder', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $folder_files  = $this->findItemByTitle($root_folder, 'Folder');
        $items_file_id = $folder_files['id'];
        $get           = $this->loadFolderContent($items_file_id, 'GET FO', RESTTestDataBuilder::TEST_BOT_USER_NAME);

        $items_folder_1 = array_merge(
            $root_folder,
            $folder_files,
            $items_file,
            $get
        );

        $folder   = $this->findItemByTitle($items_folder_1, 'GET FO');
        $empty    = $this->findItemByTitle($items_folder_1, 'GET EM');
        $file     = $this->findItemByTitle($items_folder_1, 'GET F');
        $embedded = $this->findItemByTitle($items_folder_1, 'GET E');
        $link     = $this->findItemByTitle($items_folder_1, 'GET L');
        $wiki     = $this->findItemByTitle($items_folder_1, 'GET W');

        $response       = $this->getResponseByName(
            RESTTestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $folder['id'] . '/docman_items')
        );
        $items_folder_2 = json_decode($response->getBody()->getContents());

        $items = array_merge($items_folder_1, $items_folder_2);

        $this->assertGetDocumentItems($items, $folder, $empty, $file, $link, $embedded, $wiki);

        return $items;
    }

    #[Depends('testGetRootId')]
    public function testRegularUserCantSeeFolderHeCantRead(int $root_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $root_id . '/docman_items')
        );
        $folder   = json_decode($response->getBody()->getContents());

        $allowed_folder = $this->findItemByTitle($folder, 'Folder');
        $this->assertNotNull($allowed_folder);
        $denied_folder = $this->findItemByTitle($folder, 'Folder RO');
        $this->assertNull($denied_folder);
    }

    #[Depends('testGetRootId')]
    public function testOPTIONSDocmanItemsId($root_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_items/' . $root_id . '/docman_items'),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[Depends('testGetRootId')]
    public function testOPTIONSId($root_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_items/' . $root_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[Depends('testGetRootId')]
    public function testAllOPTIONSDocmanItemsWithUserRESTReadOnlyAdmin($root_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_items/' . $root_id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_items/' . $root_id . '/docman_items'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[Depends('testGetRootId')]
    public function testGetId($root_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $root_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $item     = json_decode($response->getBody()->getContents());

        $this->assertEquals('Project Documentation', $item['title']);
        $this->assertEquals($root_id, $item['id']);
        $this->assertEquals('folder', $item['type']);
        $this->assertNull($item['permissions_for_groups']);
    }

    #[Depends('testGetRootId')]
    public function testGetIdWithUserRESTReadOnlyAdmin($root_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $root_id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $item     = json_decode($response->getBody()->getContents());

        $this->assertEquals('Project Documentation', $item['title']);
        $this->assertEquals($root_id, $item['id']);
        $this->assertEquals('folder', $item['type']);
        $this->assertIsArray($item['permissions_for_groups']);
    }

    #[Depends('testGetRootId')]
    public function testGetFolderWithSize(int $root_id): void
    {
        $root_folder        = $this->loadRootFolderContent($root_id);
        $folder_to_download = $this->findItemByTitle($root_folder, 'Download me as a zip');

        $request  = $this->request_factory->createRequest('GET', 'docman_items/' . $folder_to_download['id'] . '/?with_size=true');
        $response = $this->getResponse(
            $request,
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $folder = json_decode($response->getBody()->getContents());

        $this->assertEquals(
            $folder['folder_properties'],
            [
                'total_size' => 6,
                'nb_files'   => 3,
            ]
        );
    }

    #[Depends('testGetDocumentItemsForAdminUser')]
    public function testGetAllItemParents(array $items): void
    {
        $embedded_2 = $this->findItemByTitle($items, 'GET EM');

        $project_response = $this->getResponse($this->request_factory->createRequest('GET', 'docman_items/' . $embedded_2['id'] . '/parents'));
        $json_parents     = json_decode($project_response->getBody()->getContents());
        $this->assertEquals(count($json_parents), 3);
        $this->assertEquals($json_parents[0]['title'], 'Project Documentation');
        $this->assertEquals($json_parents[1]['title'], 'Folder');
        $this->assertEquals($json_parents[2]['title'], 'GET FO');
    }

    #[Depends('testGetDocumentItemsForAdminUser')]
    public function testGetAllItemParentsWithUserRESTReadOnlyAdmin(array $items): void
    {
        $embedded_2 = $this->findItemByTitle($items, 'GET EM');

        $project_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $embedded_2['id'] . '/parents'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $json_parents = json_decode($project_response->getBody()->getContents());
        $this->assertEquals(count($json_parents), 3);
        $this->assertEquals($json_parents[0]['title'], 'Project Documentation');
        $this->assertEquals($json_parents[1]['title'], 'Folder');
        $this->assertEquals($json_parents[2]['title'], 'GET FO');
    }

    #[Depends('testGetRootId')]
    public function testLog(int $root_id): void
    {
        $uri = 'docman_items/' . $root_id . '/logs';

        $options_response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', $uri),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(
            ['OPTIONS', 'GET'],
            explode(', ', $options_response->getHeaderLine('Allow'))
        );


        $get_response = $this->getResponse(
            $this->request_factory->createRequest('GET', $uri),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertIsArray(
            json_decode($get_response->getBody()->getContents())
        );
    }

    #[Depends('testGetRootId')]
    public function testGetAllApprovalTables(int $root_id): void
    {
        // Create embedded file
        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_folders/$root_id/embedded_files")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'title'               => 'Test all approval tables',
                    'embedded_properties' => [
                        'content' => 'Hello World!',
                    ],
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(201, $post_response->getStatusCode());
        $post_body = json_decode($post_response->getBody()->getContents());
        $item_id   = $post_body['id'];
        // With an approval table
        $post_table_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_items/$item_id/approval_table")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'users'       => [],
                    'user_groups' => [ProjectUGroup::PROJECT_ADMIN],
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(201, $post_table_response->getStatusCode());

        // Create new version of file
        $new_version_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_embedded_files/$item_id/versions")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'embedded_properties'   => [
                        'content' => 'No longer "Hello World!"',
                    ],
                    'should_lock_file'      => false,
                    'approval_table_action' => 'reset',
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $new_version_response->getStatusCode());

        // Get all approval tables
        $get_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "docman_items/$item_id/approval_tables"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $get_response->getStatusCode());
        $get_body = json_decode($get_response->getBody()->getContents());
        self::assertIsArray($get_body);
        self::assertCount(2, $get_body);
        self::assertSame(2, $get_body[0]['version_number']);
        self::assertSame('Not yet', $get_body[0]['approval_state']);
        self::assertSame('disabled', $get_body[0]['notification_type']);
        self::assertCount(0, $get_body[0]['reviewers']);
        self::assertSame(1, $get_body[1]['version_number']);
        self::assertSame('Not yet', $get_body[1]['approval_state']);
        self::assertSame('disabled', $get_body[1]['notification_type']);
        self::assertCount(0, $get_body[1]['reviewers']);

        // Cleanup (delete file)
        $delete_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', "docman_embedded_files/$item_id"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $delete_response->getStatusCode());
    }

    #[Depends('testGetRootId')]
    public function testApprovalTableWorkflow(int $root_id): void
    {
        // Create embedded file...
        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_folders/$root_id/embedded_files")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'title'               => 'Test one approval table',
                    'embedded_properties' => [
                        'content' => 'Hello World!',
                    ],
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(201, $post_response->getStatusCode());
        $post_body = json_decode($post_response->getBody()->getContents());
        $item_id   = $post_body['id'];
        // ...with an approval table...
        $reviewer_id         = $this->user_ids[BaseTestDataBuilder::TEST_USER_1_NAME];
        $post_table_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_items/$item_id/approval_table")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'users'       => [$reviewer_id],
                    'user_groups' => [],
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(201, $post_table_response->getStatusCode());
        // ...enabled...
        $put_response = $this->getResponse(
            $this->request_factory->createRequest('PUT', "docman_items/$item_id/approval_table")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'owner'                  => $this->user_ids[DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME],
                    'status'                 => 'enabled',
                    'comment'                => '',
                    'notification_type'      => 'all_at_once',
                    'reviewers'              => [$reviewer_id],
                    'reviewers_group_to_add' => [],
                    'reminder_occurence'     => 0,
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $put_response->getStatusCode());
        // (send reminder)
        $send_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_items/$item_id/approval_table/reminder"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $send_response->getStatusCode());
        // ...and reviewed
        $put_response = $this->getResponse(
            $this->request_factory->createRequest('PUT', "docman_items/$item_id/approval_table/review")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'review'       => 'rejected',
                    'comment'      => 'I do not like it',
                    'notification' => false,
                ]))),
            BaseTestDataBuilder::TEST_USER_1_NAME,
        );
        self::assertSame(200, $put_response->getStatusCode());

        // Send reminder to BaseTestDataBuilder::TEST_USER_1_NAME
        $send_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_items/$item_id/approval_table/reminder/$reviewer_id"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $send_response->getStatusCode());

        // Get approval table
        $get_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "docman_items/$item_id/approval_table/1"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $get_response->getStatusCode());
        $table = json_decode($get_response->getBody()->getContents());
        self::assertSame('Not yet', $table['approval_state']);
        self::assertSame(1, $table['version_number']);
        self::assertSame('all_at_once', $table['notification_type']);
        self::assertSame(false, $table['is_closed']);
        self::assertCount(1, $table['reviewers']);
        self::assertSame(BaseTestDataBuilder::TEST_USER_1_NAME, $table['reviewers'][0]['user']['username']);

        // Delete table
        $delete_table_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', "docman_items/$item_id/approval_table"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $delete_table_response->getStatusCode());
        $get_all_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "docman_items/$item_id/approval_tables"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $get_all_response->getStatusCode());
        $get_all_body = json_decode($get_all_response->getBody()->getContents());
        self::assertIsArray($get_all_body);
        self::assertCount(0, $get_all_body);

        // Cleanup (delete file)
        $delete_item_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', "docman_embedded_files/$item_id"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $delete_item_response->getStatusCode());
    }

    #[Depends('testGetRootId')]
    public function testPatchApprovalTable(int $root_id): void
    {
        // Create embedded file...
        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_folders/$root_id/embedded_files")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'title'               => 'Test one approval table',
                    'embedded_properties' => [
                        'content' => 'Hello World!',
                    ],
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(201, $post_response->getStatusCode());
        $post_body = json_decode($post_response->getBody()->getContents());
        $item_id   = $post_body['id'];
        // ...with an approval table
        $reviewer_id         = $this->user_ids[BaseTestDataBuilder::TEST_USER_1_NAME];
        $post_table_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_items/$item_id/approval_table")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'users'       => [$reviewer_id],
                    'user_groups' => [],
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(201, $post_table_response->getStatusCode());

        // Create new version of file
        $new_version_response = $this->getResponse(
            $this->request_factory->createRequest('POST', "docman_embedded_files/$item_id/versions")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'embedded_properties'   => [
                        'content' => 'No longer "Hello World!"',
                    ],
                    'should_lock_file'      => false,
                    'approval_table_action' => 'reset',
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $new_version_response->getStatusCode());

        // Get all approval tables
        $get_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "docman_items/$item_id/approval_tables"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $get_response->getStatusCode());
        $get_body = json_decode($get_response->getBody()->getContents());
        self::assertIsArray($get_body);
        self::assertCount(2, $get_body);

        // Delete approval table
        $delete_table_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', "docman_items/$item_id/approval_table"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $delete_table_response->getStatusCode());

        // Get remaining table
        $get_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "docman_items/$item_id/approval_tables"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $get_response->getStatusCode());
        $get_body = json_decode($get_response->getBody()->getContents());
        self::assertIsArray($get_body);
        self::assertCount(1, $get_body);

        // Recreate a table
        $patch_response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', "docman_items/$item_id/approval_table")
                ->withBody($this->stream_factory->createStream(json_encode([
                    'action' => 'copy',
                ]))),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $patch_response->getStatusCode());

        // Get all approval tables
        $get_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "docman_items/$item_id/approval_tables"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $get_response->getStatusCode());
        $get_body = json_decode($get_response->getBody()->getContents());
        self::assertIsArray($get_body);
        self::assertCount(2, $get_body);

        // Cleanup (delete file)
        $delete_item_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', "docman_embedded_files/$item_id"),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
        );
        self::assertSame(200, $delete_item_response->getStatusCode());
    }

    private function assertGetDocumentItems(
        array $items,
        ?array $folder,
        ?array $empty,
        ?array $file,
        ?array $link,
        ?array $embedded,
        ?array $wiki,
    ): void {
        $this->assertGreaterThan(0, count($items));

        $this->assertEquals(' (docman_regular_user)', $items[0]['owner']['display_name']);

        $this->assertEquals($folder['user_can_write'], true);
        $this->assertEquals($empty['user_can_write'], true);
        $this->assertEquals($file['user_can_write'], true);
        $this->assertEquals($link['user_can_write'], true);
        $this->assertEquals($embedded['user_can_write'], true);
        $this->assertEquals($wiki['user_can_write'], true);

        $this->assertEquals($folder['is_expanded'], false);
        $this->assertEquals($empty['is_expanded'], false);
        $this->assertEquals($file['is_expanded'], false);
        $this->assertEquals($link['is_expanded'], false);
        $this->assertEquals($embedded['is_expanded'], false);
        $this->assertEquals($wiki['is_expanded'], false);

        $this->assertEquals($folder['file_properties'], null);
        $this->assertEquals($empty['file_properties'], null);
        $this->assertEquals($file['file_properties']['file_type'], 'application/pdf');
        $this->assertEquals(
            $file['file_properties']['download_href'],
            '/plugins/docman/download/' . urlencode((string) $file['id']) . '/1'
        );
        $this->assertEquals($file['file_properties']['file_size'], 3);
        $this->assertEquals($link['file_properties'], null);
        $this->assertEquals($embedded['file_properties'], null);
        $this->assertEquals($wiki['file_properties'], null);

        $this->assertEquals($folder['embedded_file_properties'], null);
        $this->assertEquals($empty['embedded_file_properties'], null);
        $this->assertEquals($file['embedded_file_properties'], null);
        $this->assertEquals($link['embedded_file_properties'], null);
        $this->assertEquals($embedded['embedded_file_properties']['file_type'], 'text/html');
        $this->assertArrayNotHasKey('content', $embedded['embedded_file_properties']);
        $this->assertEquals($wiki['embedded_file_properties'], null);

        $this->assertEquals($folder['link_properties'], null);
        $this->assertEquals($empty['link_properties'], null);
        $this->assertEquals($file['link_properties'], null);
        $this->assertEquals($link['link_properties'], null);
        $this->assertEquals($embedded['link_properties'], null);
        $this->assertEquals($wiki['link_properties'], null);

        $this->assertEquals($folder['wiki_properties'], null);
        $this->assertEquals($empty['wiki_properties'], null);
        $this->assertEquals($file['wiki_properties'], null);
        $this->assertEquals($link['wiki_properties'], null);
        $this->assertEquals($embedded['wiki_properties'], null);
        $this->assertEquals($wiki['wiki_properties']['page_name'], 'MyWikiPage');

        $this->assertNotNull($folder['permissions_for_groups']);
        $this->assertNotNull($empty['permissions_for_groups']);
        $this->assertNotNull($file['permissions_for_groups']);
        $this->assertNotNull($link['permissions_for_groups']);
        $this->assertNotNull($embedded['permissions_for_groups']);
        $this->assertNotNull($wiki['permissions_for_groups']);
    }
}
