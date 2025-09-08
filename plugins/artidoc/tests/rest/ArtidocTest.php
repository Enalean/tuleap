<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc;

use ProjectUGroup;
use Psl\Json;
use Tuleap\Artidoc\Tests\ArtidocAPIHelper;
use Tuleap\Artidoc\Tests\DocumentPermissions;
use Tuleap\Docman\Test\rest\DocmanBase;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanAPIHelper;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RESTTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocTest extends DocmanBase
{
    private string $now = '';
    private ArtidocAPIHelper $artidoc_api;
    private DocmanAPIHelper $docman_api;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->now = microtime();

        $this->artidoc_api = new ArtidocAPIHelper($this->rest_request, $this->request_factory, $this->stream_factory);
        $this->docman_api  = new DocmanAPIHelper($this->rest_request, $this->request_factory);
    }

    public function testArtidocCreation(): int
    {
        $root_id            = $this->docman_api->getRootFolderID($this->project_id);
        $post_response_json = $this->createArtidoc($root_id, 'Artidoc F1 ' . $this->now);

        $item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $post_response_json['uri'])
        );
        self::assertSame(200, $item_response->getStatusCode());
        $item_response_json = Json\decode($item_response->getBody()->getContents());
        self::assertSame('artidoc', $item_response_json['type']);
        self::assertSame('Artidoc F1 ' . $this->now, $item_response_json['title']);

        return $post_response_json['id'];
    }

    public function testArtidocMove(): void
    {
        $root_id               = $this->docman_api->getRootFolderID($this->project_id);
        $folder_source_id      = $this->createFolder($root_id, 'Folder source to contain item F2 to move. ' . $this->now)['id'];
        $folder_destination_id = $this->createFolder($root_id, 'Folder target to move item F2 into. ' . $this->now)['id'];

        $item_id = $this->createArtidoc($folder_source_id, 'Artidoc F2 ' . $this->now)['id'];

        self::assertCount(1, $this->getFolderContent($folder_source_id));
        self::assertCount(0, $this->getFolderContent($folder_destination_id));

        $move_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'artidoc/' . $item_id)
                ->withBody(
                    $this->stream_factory->createStream(
                        Json\encode(['move' => ['destination_folder_id' => $folder_destination_id]])
                    )
                )
        );
        self::assertSame(200, $move_item_response->getStatusCode());

        self::assertCount(0, $this->getFolderContent($folder_source_id));
        $folder_destination_content = $this->getFolderContent($folder_destination_id);
        self::assertCount(1, $folder_destination_content);
        self::assertSame($item_id, $folder_destination_content[0]['id']);
    }

    private function createArtidoc(int $parent_id, string $title): array
    {
        $post_response_json = $this->artidoc_api->createArtidoc(
            $parent_id,
            $title,
            DocumentPermissions::buildProjectMembersCanManage($this->project_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertNull($post_response_json['file_properties']);

        return $post_response_json;
    }

    private function createFolder(int $parent_id, string $title): array
    {
        $post_folder_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory
                ->createRequest('POST', 'docman_folders/' . $parent_id . '/folders')
                ->withBody(
                    $this->stream_factory->createStream(Json\encode(['title' => $title]))
                )
        );
        self::assertSame(201, $post_folder_response->getStatusCode());

        return Json\decode($post_folder_response->getBody()->getContents());
    }

    private function getFolderContent(int $id): array
    {
        return Json\decode(
            $this->getResponseByName(
                DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
                $this->request_factory->createRequest('GET', 'docman_items/' . $id . '/docman_items'),
            )->getBody()->getContents()
        );
    }

    public function testPostOtherTypeDocumentDeniedForUserRESTReadOnlyAdminNotInvolvedInProject(): void
    {
        $root_id = $this->docman_api->getRootFolderID($this->project_id);

        $query = Json\encode([
            'title' => 'Artidoc F2 ' . $this->now,
            'type'  => 'artidoc',
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/others')->withBody($this->stream_factory->createStream($query)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testArtidocCreation')]
    public function testOptionsDocument(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc/' . $id));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[\PHPUnit\Framework\Attributes\Depends('testArtidocCreation')]
    public function testOptionsSections(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc/' . $id . '/sections'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'GET', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOptionsSectionsId(): void
    {
        $uuid     = 'dummy-uuid';
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc_sections/' . $uuid));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'GET', 'PUT', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testArtidocCopy(): void
    {
        $root_id      = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id   = $this->createArtidoc($root_id, 'Artidoc F6 ' . $this->now)['id'];
        $section_1_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');

        $this->artidoc_api->importExistingArtifactInArtidoc(
            $artidoc_id,
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $section_1_id,
            $section_2_id
        );

        $folder_id = $this->createFolder($root_id, 'Folder to copy item F6 into. ' . $this->now)['id'];
        self::assertCount(0, $this->getFolderContent($folder_id));

        $copy_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $folder_id . '/others')
                ->withBody(
                    $this->stream_factory->createStream(Json\encode([
                        'copy' => ['item_id' => $artidoc_id],
                    ]))
                )
        );
        self::assertSame(201, $copy_item_response->getStatusCode());

        $copy_response_json = Json\decode($copy_item_response->getBody()->getContents());
        self::assertNull($copy_response_json['file_properties']);
        $new_artidoc_id = $copy_response_json['id'];

        self::assertNotSame($artidoc_id, $new_artidoc_id);

        $document_content = $this->artidoc_api->getArtidocSections($new_artidoc_id);

        self::assertCount(2, $document_content);
        self::assertSame([$section_1_id, $section_2_id], $document_content->getArtifactSectionIds());
    }

    public function testAddNewSectionToArtidoc(): void
    {
        $root_id          = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id       = $this->createArtidoc($root_id, 'Test Add New Section ' . $this->now)['id'];
        $section_1_art_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_art_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');

        $this->artidoc_api->importExistingArtifactInArtidoc(
            $artidoc_id,
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $section_1_art_id,
            $section_2_art_id
        );

        $this->assertSectionsMatchContent(
            $artidoc_id,
            'Section 1',
            'Section 2',
        );

        $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'artidoc/' . $artidoc_id . '/configuration'
            )->withBody(
                $this->stream_factory->createStream(
                    Json\encode([
                        'selected_tracker_ids' => [$this->requirements_tracker_id],
                        'fields'               => [],
                    ]),
                )
            )
        );

        // at the end
        $section_3_post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_sections')->withBody(
                $this->stream_factory->createStream(Json\encode([
                    'artidoc_id' => $artidoc_id,
                    'section'    => [
                        'position' => null,
                        'content'  => [
                            'title'       => 'Section 3',
                            'description' => 'Content of section 3',
                            'type'        => 'artifact',
                            'attachments' => [],
                            'level'       => 1,
                        ],
                    ],
                ]))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $section_3_post_response->getStatusCode());

        $this->assertSectionsMatchContent(
            $artidoc_id,
            'Section 1',
            'Section 2',
            'Section 3',
        );

        $section_3_id = Json\decode($section_3_post_response->getBody()->getContents())['id'];

        // before another section
        $section_4_post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_sections')->withBody(
                $this->stream_factory->createStream(Json\encode([
                    'artidoc_id' => $artidoc_id,
                    'section'    => [
                        'position' => [
                            'before' => $section_3_id,
                        ],
                        'content'  => [
                            'title'       => 'Section 4',
                            'description' => 'Content of section 4',
                            'type'        => 'artifact',
                            'attachments' => [],
                            'level'       => 1,
                        ],
                    ],
                ]))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $section_4_post_response->getStatusCode());

        $this->assertSectionsMatchContent(
            $artidoc_id,
            'Section 1',
            'Section 2',
            'Section 4',
            'Section 3',
        );

        // with a free text
        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_sections')->withBody(
                $this->stream_factory->createStream(Json\encode([
                    'artidoc_id' => $artidoc_id,
                    'section'    => [
                        'position' => [
                            'before' => $section_3_id,
                        ],
                        'content'  => [
                            'title'       => 'My freetext title',
                            'description' => 'My freetext description',
                            'type'        => 'freetext',
                            'attachments' => [],
                            'level'       => 1,
                        ],
                    ],
                ]))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $post_response->getStatusCode());
        self::assertNotNull(Json\decode($post_response->getBody()->getContents())['id']);

        $this->assertSectionsMatchContent(
            $artidoc_id,
            'Section 1',
            'Section 2',
            'Section 4',
            'My freetext title',
            'Section 3',
        );
    }

    public function testDeleteSection(): void
    {
        $root_id          = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id       = $this->createArtidoc($root_id, 'Test Add New Section ' . $this->now)['id'];
        $section_1_art_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_art_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');
        $section_3_art_id = $this->createRequirementArtifact('Section 3', 'Content of section 3');

        $this->artidoc_api->importExistingArtifactInArtidoc(
            $artidoc_id,
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $section_1_art_id,
            $section_2_art_id,
            $section_3_art_id
        );

        $this->assertSectionsMatchArtifactIdsForDocument(
            $artidoc_id,
            $section_1_art_id,
            $section_2_art_id,
            $section_3_art_id,
        );

        $uuid = $this->artidoc_api->getArtidocSections($artidoc_id)->findArtifactSectionUUID($section_2_art_id);

        $delete_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'artidoc_sections/' . $uuid),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(204, $delete_response->getStatusCode());

        $this->assertSectionsMatchArtifactIdsForDocument(
            $artidoc_id,
            $section_1_art_id,
            $section_3_art_id,
        );
    }

    public function testReorderSection(): void
    {
        $root_id          = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id       = $this->createArtidoc($root_id, 'Test reorder section ' . $this->now)['id'];
        $section_1_art_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_art_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');
        $section_3_art_id = $this->createRequirementArtifact('Section 3', 'Content of section 3');

        $this->artidoc_api->importExistingArtifactInArtidoc(
            $artidoc_id,
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $section_1_art_id,
            $section_2_art_id,
            $section_3_art_id,
        );

        $this->assertSectionsMatchArtifactIdsForDocument(
            $artidoc_id,
            $section_1_art_id,
            $section_2_art_id,
            $section_3_art_id,
        );

        $sections = $this->artidoc_api->getArtidocSections($artidoc_id);
        $uuid1    = $sections->findArtifactSectionUUID($section_1_art_id);
        $uuid2    = $sections->findArtifactSectionUUID($section_2_art_id);

        $order_response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'artidoc/' . $artidoc_id . '/sections')->withBody(
                $this->stream_factory->createStream(Json\encode([
                    'order' => [
                        'ids'         => [$uuid1],
                        'direction'   => 'after',
                        'compared_to' => $uuid2,
                    ],
                ]))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $order_response->getStatusCode());

        $this->assertSectionsMatchArtifactIdsForDocument(
            $artidoc_id,
            $section_2_art_id,
            $section_1_art_id,
            $section_3_art_id,
        );
    }

    private function assertSectionsMatchArtifactIdsForDocument(int $artidoc_id, int ...$artifact_ids): void
    {
        $document_content = $this->artidoc_api->getArtidocSections($artidoc_id);
        self::assertCount(count($artifact_ids), $document_content);
        self::assertSame($artifact_ids, $document_content->getArtifactSectionIds());
    }

    private function assertSectionsMatchContent(int $artidoc_id, string ...$titles): void
    {
        $document_content = $this->artidoc_api->getArtidocSections($artidoc_id);
        self::assertCount(count($titles), $document_content);
        self::assertSame($titles, $document_content->getTitles());
    }

    public function testDELETEArtidoc(): void
    {
        $root_id    = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc F5 ' . $this->now)['id'];

        $delete_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_other_type_documents/' . $artidoc_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $delete_response->getStatusCode());

        $get_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $artidoc_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(404, $get_response->getStatusCode());
    }

    private function createRequirementArtifact(string $title, string $description): int
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'artifacts')->withBody(
                $this->stream_factory->createStream(Json\encode([
                    'tracker'         => ['id' => $this->requirements_tracker_id],
                    'values_by_field' => [
                        'title'       => ['value' => $title],
                        'description' => ['value' => $description],
                    ],
                ]))
            ),
        );

        $response_content = Json\decode($response->getBody()->getContents());

        return $response_content['id'];
    }

    public function testPUTPermissions(): void
    {
        $root_id    = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc Permissions ' . $this->now)['id'];

        $get_by_regular_user_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $artidoc_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $get_by_regular_user_response->getStatusCode(), 'Regular user can read the document');

        $put_permissions_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory
                ->createRequest('PUT', 'docman_other_type_documents/' . $artidoc_id . '/permissions')
                ->withBody(
                    $this->stream_factory->createStream(
                        Json\encode(
                            DocumentPermissions::buildProjectAdminsCanManageAndNobodyCanDoAnythingElse(
                                $this->project_id
                            )
                        ),
                    )
                )
        );
        self::assertSame(200, $put_permissions_response->getStatusCode());

        $get_by_regular_user_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $artidoc_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(403, $get_by_regular_user_response->getStatusCode(), 'Regular user has no longer permissions to read the document');

        $get_by_admin_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $artidoc_id),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );
        self::assertSame(200, $get_by_admin_response->getStatusCode(), 'Admin can read the document');

        $permissions_for_groups_representation = Json\decode($get_by_admin_response->getBody()->getContents())['permissions_for_groups'];
        self::assertEmpty($permissions_for_groups_representation['can_read']);
        self::assertEmpty($permissions_for_groups_representation['can_write']);
        self::assertCount(1, $permissions_for_groups_representation['can_manage']);
        $project_admins_user_group_id = $this->project_id . '_' . ProjectUGroup::PROJECT_ADMIN;
        self::assertSame($project_admins_user_group_id, $permissions_for_groups_representation['can_manage'][0]['id']);
    }

    public function testGetOneSection(): void
    {
        $root_id      = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id   = $this->createArtidoc($root_id, 'Artidoc test one section ' . $this->now)['id'];
        $section_1_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');

        $this->artidoc_api->importExistingArtifactInArtidoc(
            $artidoc_id,
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $section_1_id,
            $section_2_id
        );

        $document_content = $this->artidoc_api->getArtidocSections($artidoc_id);

        self::assertCount(2, $document_content);

        $section_1_uuid       = $document_content[0]['id'];
        $get_section_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artidoc_sections/' . $section_1_uuid),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $get_section_response->getStatusCode());

        $section_representation = Json\decode($get_section_response->getBody()->getContents());
        self::assertSame($document_content[0], $section_representation);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testArtidocCreation')]
    public function testOptionsConfiguration(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc/' . $id . '/configuration'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[\PHPUnit\Framework\Attributes\Depends('testArtidocCreation')]
    public function testPutConfiguration(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artidoc/' . $id . '/configuration')->withBody(
                $this->stream_factory->createStream(
                    Json\encode([
                        'selected_tracker_ids' => [$this->requirements_tracker_id],
                        'fields'               => [],
                    ]),
                )
            )
        );
        self::assertSame(200, $response->getStatusCode());
    }

    public function testOptionsUpload(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc_files'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testUpload(): void
    {
        $root_id    = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc upload attachment ' . $this->now)['id'];

        $payload = [
            'artidoc_id' => $artidoc_id,
            'name'       => 'filename.png',
            'file_size'  => 123,
            'file_type'  => 'image/png',
        ];

        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_files')->withBody(
                $this->stream_factory->createStream(Json\encode($payload))
            ),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        self::assertSame(403, $post_response->getStatusCode());

        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_files')->withBody(
                $this->stream_factory->createStream(Json\encode($payload))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $post_response->getStatusCode());
        $upload_response_json = Json\decode($post_response->getBody()->getContents());
        self::assertIsString($upload_response_json['download_href']);
        self::assertIsString($upload_response_json['upload_href']);
    }

    public function testUpdateFreeTextSection(): void
    {
        $root_id    = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc freetext ' . $this->now)['id'];
        $section_id = $this->postFreeTextSection($artidoc_id);
        $response   = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artidoc_sections/' . $section_id)->withBody(
                $this->stream_factory->createStream(
                    Json\encode([
                        'title'       => 'My updated title',
                        'description' => 'My updated description',
                        'attachments' => [],
                        'level'       => 1,
                    ]),
                )
            )
        );
        self::assertSame(200, $response->getStatusCode());
        $document_content = $this->artidoc_api->getArtidocSections($artidoc_id);
        self::assertContains('My updated title', $document_content->getTitles());
    }

    public function testUpdateArtifactSection(): void
    {
        $root_id    = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc update requirement ' . $this->now)['id'];
        $req_id     = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $this->artidoc_api->importExistingArtifactInArtidoc(
            $artidoc_id,
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $req_id
        );

        $document_content = $this->artidoc_api->getArtidocSections($artidoc_id);

        self::assertCount(1, $document_content);

        $section_id = $document_content[0]['id'];

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artidoc_sections/' . $section_id)->withBody(
                $this->stream_factory->createStream(
                    Json\encode([
                        'title'       => 'My updated title',
                        'description' => 'My updated description',
                        'attachments' => [],
                        'level'       => 2,
                    ]),
                )
            )
        );
        self::assertSame(200, $response->getStatusCode());

        $document_content = $this->artidoc_api->getArtidocSections($artidoc_id);

        self::assertCount(1, $document_content);
        self::assertSame('My updated title', $document_content[0]['title']);
        self::assertSame('My updated description', $document_content[0]['description']);
        self::assertSame(2, $document_content[0]['level']);
    }

    public function testDeleteFreeTextSection(): void
    {
        $root_id    = $this->docman_api->getRootFolderID($this->project_id);
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc delete freetext ' . $this->now)['id'];
        $section_id = $this->postFreeTextSection($artidoc_id);
        $response   = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'artidoc_sections/' . $section_id)
        );
        self::assertSame(204, $response->getStatusCode());

        $document_content = $this->artidoc_api->getArtidocSections($artidoc_id);
        self::assertEmpty($document_content);
    }

    private function postFreeTextSection(int $id): string
    {
        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_sections')->withBody(
                $this->stream_factory->createStream(Json\encode([
                    'artidoc_id' => $id,
                    'section'    => [
                        'position' => null,
                        'content'  => [
                            'title'       => 'My freetext title',
                            'description' => 'My freetext description',
                            'type'        => 'freetext',
                            'attachments' => [],
                            'level'       => 1,
                        ],
                    ],
                ]))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $post_response->getStatusCode());
        self::assertNotNull(Json\decode($post_response->getBody()->getContents())['id']);

        $document_content = $this->artidoc_api->getArtidocSections($id);
        return $document_content[0]['id'];
    }
}
