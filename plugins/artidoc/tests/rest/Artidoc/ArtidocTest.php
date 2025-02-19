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

require_once __DIR__ . '/../../../../docman/vendor/autoload.php';

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

final class ArtidocTest extends DocmanTestExecutionHelper
{
    private string $now                         = '';
    private string $registered_users_identifier = '2';
    private string $project_members_identifier;
    private string $project_admins_identifier;

    public function setUp(): void
    {
        parent::setUp();
        $this->now = (string) microtime();

        $this->project_members_identifier = $this->project_id . '_3';
        $this->project_admins_identifier  = $this->project_id . '_4';
    }

    /**
     * @depends testGetRootId
     */
    public function testArtidocCreation(int $root_id): int
    {
        $post_response_json = $this->createArtidoc($root_id, 'Artidoc F1 ' . $this->now);

        $item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $post_response_json['uri'])
        );
        self::assertSame(200, $item_response->getStatusCode());
        $item_response_json = json_decode($item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('artidoc', $item_response_json['type']);
        self::assertSame('Artidoc F1 ' . $this->now, $item_response_json['title']);

        return $post_response_json['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testArtidocMove(int $root_id): void
    {
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
                        json_encode(
                            [
                                'move' => [
                                    'destination_folder_id' => $folder_destination_id,
                                ],
                            ]
                        )
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
        $post_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $parent_id . '/others')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode(
                            [
                                'title' => $title,
                                'type'  => 'artidoc',
                                'permissions_for_groups' => [
                                    'can_read' => [
                                        [
                                            'id' => $this->registered_users_identifier,
                                        ],
                                    ],
                                    'can_write' => [
                                        [
                                            'id' => $this->project_members_identifier,
                                        ],
                                    ],
                                    'can_manage' => [
                                        [
                                            'id' => $this->project_members_identifier,
                                        ],
                                    ],
                                ],
                            ],
                        ),
                    ),
                ),
        );
        self::assertSame(201, $post_item_response->getStatusCode());

        $post_response_json = json_decode($post_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
                    $this->stream_factory->createStream(
                        json_encode(
                            [
                                'title' => $title,
                            ]
                        ),
                    )
                )
        );
        self::assertSame(201, $post_folder_response->getStatusCode());

        return json_decode($post_folder_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function getFolderContent(int $id): array
    {
        return json_decode(
            $this->getResponseByName(
                DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
                $this->request_factory->createRequest('GET', 'docman_items/' . $id . '/docman_items'),
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostOtherTypeDocumentDeniedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title' => 'Artidoc F2 ' . $this->now,
                'type'  => 'artidoc',
            ]
        );

        $response1 = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/others')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(403, $response1->getStatusCode());
    }

    /**
     * @depends testArtidocCreation
     */
    public function testOptionsDocument(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc/' . $id));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    /**
     * @depends testArtidocCreation
     */
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

    /**
     * @depends testGetRootId
     */
    public function testArtidocCopy(int $root_id): void
    {
        $artidoc_id   = $this->createArtidoc($root_id, 'Artidoc F6 ' . $this->now)['id'];
        $section_1_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');

        $this->importExistingArtifactInArtidoc($artidoc_id, $section_1_id, $section_2_id);

        $folder_id = $this->createFolder($root_id, 'Folder to copy item F6 into. ' . $this->now)['id'];
        self::assertCount(0, $this->getFolderContent($folder_id));

        $copy_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $folder_id . '/others')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode(
                            [
                                'copy' => [
                                    'item_id'  => $artidoc_id,
                                ],
                            ]
                        )
                    )
                )
        );
        self::assertSame(201, $copy_item_response->getStatusCode());

        $copy_response_json = json_decode($copy_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($copy_response_json['file_properties']);
        $new_artidoc_id = $copy_response_json['id'];

        self::assertNotSame($artidoc_id, $new_artidoc_id);

        $document_content = $this->getArtidocSections($new_artidoc_id);

        self::assertCount(2, $document_content);
        self::assertSame($section_1_id, $document_content[0]['artifact']['id']);
        self::assertSame($section_2_id, $document_content[1]['artifact']['id']);
    }

    private function importExistingArtifactInArtidoc(int $artidoc_id, int ...$artifact_ids): void
    {
        foreach ($artifact_ids as $artifact_id) {
            $post_response = $this->getResponse(
                $this->request_factory->createRequest('POST', 'artidoc_sections')->withBody(
                    $this->stream_factory->createStream(json_encode(
                        [
                            'artidoc_id' => $artidoc_id,
                            'section' => [
                                'import' => [
                                    'artifact' => ['id' => $artifact_id],
                                    'level' => 1,
                                ],
                                'position' => null,
                                'content' => null,
                            ],
                        ],
                        JSON_THROW_ON_ERROR
                    ))
                ),
                DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
            );
            self::assertSame(200, $post_response->getStatusCode());
        }
    }

    /**
     * @depends testGetRootId
     */
    public function testAddNewSectionToArtidoc(int $root_id): void
    {
        $artidoc_id       = $this->createArtidoc($root_id, 'Test Add New Section ' . $this->now)['id'];
        $section_1_art_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_art_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');

        $this->importExistingArtifactInArtidoc($artidoc_id, $section_1_art_id, $section_2_art_id);

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
                    json_encode(
                        [
                            'selected_tracker_ids' => [$this->requirements_tracker_id],
                        ],
                    ),
                )
            )
        );

        // at the end
        $section_3_post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_sections')->withBody(
                $this->stream_factory->createStream(json_encode([
                    'artidoc_id' => $artidoc_id,
                    'section' => [
                        'position' => null,
                        'content' => [
                            'title' => 'Section 3',
                            'description' => 'Content of section 3',
                            'type' => 'artifact',
                            'attachments' => [],
                            'level' => 1,
                        ],
                    ],
                ], JSON_THROW_ON_ERROR))
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

        $section_3_id = json_decode($section_3_post_response->getBody()->getContents(), null, 512, JSON_THROW_ON_ERROR)->id;

        // before another section
        $section_4_post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_sections')->withBody(
                $this->stream_factory->createStream(json_encode([
                    'artidoc_id' => $artidoc_id,
                    'section' => [
                        'position' => [
                            'before' => $section_3_id,
                        ],
                        'content' => [
                            'title' => 'Section 4',
                            'description' => 'Content of section 4',
                            'type' => 'artifact',
                            'attachments' => [],
                            'level' => 1,
                        ],
                    ],
                ], JSON_THROW_ON_ERROR))
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
                $this->stream_factory->createStream(json_encode([
                    'artidoc_id' => $artidoc_id,
                    'section' => [
                        'position' => [
                            'before' => $section_3_id,
                        ],
                        'content' => [
                            'title' => 'My freetext title',
                            'description' => 'My freetext description',
                            'type' => 'freetext',
                            'attachments' => [],
                            'level' => 1,
                        ],
                    ],
                ], JSON_THROW_ON_ERROR))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $post_response->getStatusCode());
        json_decode($post_response->getBody()->getContents(), null, 512, JSON_THROW_ON_ERROR)->id;

        $this->assertSectionsMatchContent(
            $artidoc_id,
            'Section 1',
            'Section 2',
            'Section 4',
            'My freetext title',
            'Section 3',
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testDeleteSection(int $root_id): void
    {
        $artidoc_id       = $this->createArtidoc($root_id, 'Test Add New Section ' . $this->now)['id'];
        $section_1_art_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_art_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');
        $section_3_art_id = $this->createRequirementArtifact('Section 3', 'Content of section 3');

        $this->importExistingArtifactInArtidoc($artidoc_id, $section_1_art_id, $section_2_art_id, $section_3_art_id);

        $this->assertSectionsMatchArtifactIdsForDocument(
            $artidoc_id,
            $section_1_art_id,
            $section_2_art_id,
            $section_3_art_id,
        );

        $uuid = $this->getSectionUuid($artidoc_id, $section_2_art_id);

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

    /**
     * @depends testGetRootId
     */
    public function testReorderSection(int $root_id): void
    {
        $artidoc_id       = $this->createArtidoc($root_id, 'Test reorder section ' . $this->now)['id'];
        $section_1_art_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_art_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');
        $section_3_art_id = $this->createRequirementArtifact('Section 3', 'Content of section 3');

        $this->importExistingArtifactInArtidoc($artidoc_id, $section_1_art_id, $section_2_art_id, $section_3_art_id);

        $this->assertSectionsMatchArtifactIdsForDocument(
            $artidoc_id,
            $section_1_art_id,
            $section_2_art_id,
            $section_3_art_id,
        );

        $uuid1 = $this->getSectionUuid($artidoc_id, $section_1_art_id);
        $uuid2 = $this->getSectionUuid($artidoc_id, $section_2_art_id);

        $order_response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'artidoc/' . $artidoc_id . '/sections')->withBody(
                $this->stream_factory->createStream(json_encode([
                    'order' => [
                        'ids'         => [$uuid1],
                        'direction'   => 'after',
                        'compared_to' => $uuid2,
                    ],
                ], JSON_THROW_ON_ERROR))
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

    private function getSectionUuid(int $artidoc_id, int $section_artifact_id): string
    {
        $document_content = $this->getArtidocSections($artidoc_id);
        foreach ($document_content as $section) {
            if ($section['artifact']['id'] === $section_artifact_id) {
                return $section['id'];
            }
        }

        throw new \Exception('Unable to find section for art #' . $section_artifact_id . ' in ' . $artidoc_id);
    }

    private function assertSectionsMatchArtifactIdsForDocument(int $artidoc_id, int ...$artifact_ids): void
    {
        $document_content = $this->getArtidocSections($artidoc_id);
        self::assertSame(count($artifact_ids), count($document_content));
        self::assertSame(
            $artifact_ids,
            array_map(
                static fn (array $section) => $section['artifact']['id'],
                $document_content,
            ),
        );
    }

    private function assertSectionsMatchContent(int $artidoc_id, string ...$titles): void
    {
        $document_content = $this->getArtidocSections($artidoc_id);
        self::assertSame(count($titles), count($document_content));
        self::assertSame(
            $titles,
            array_map(
                static fn (array $section) => $section['title'],
                $document_content,
            ),
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testDELETEArtidoc(int $root_id): void
    {
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
                $this->stream_factory->createStream(json_encode([
                    'tracker' => ['id' => $this->requirements_tracker_id],
                    'values_by_field' => [
                        'title' => ['value' => $title],
                        'description' => ['value' => $description],
                    ],
                ], JSON_THROW_ON_ERROR))
            ),
        );

        $response_content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $response_content['id'];
    }

    private function getArtidocSections(int $artidoc_id): array
    {
        return json_decode(
            $this->getResponse(
                $this->request_factory->createRequest('GET', 'artidoc/' . $artidoc_id . '/sections')
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPUTPermissions(int $root_id): void
    {
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc Permissions ' . $this->now)['id'];

        $get_by_regular_user_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $artidoc_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $get_by_regular_user_response->getStatusCode(), 'Regular user can read the document');

        $put_permissions_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory
                ->createRequest('PUT', 'docman_other_type_documents/' . $artidoc_id . '/permissions')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode(
                            [
                                'can_read' => [],
                                'can_write' => [],
                                'can_manage' => [
                                    [
                                        'id' => $this->project_admins_identifier,
                                    ],
                                ],
                            ],
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
            \TestDataBuilder::ADMIN_USER_NAME
        );
        self::assertSame(200, $get_by_admin_response->getStatusCode(), 'Admin can read the document');

        $permissions_for_groups_representation = json_decode($get_by_admin_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($this->project_admins_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);
    }

    /**
     * @depends testGetRootId
     */
    public function testGetOneSection(int $root_id): void
    {
        $artidoc_id   = $this->createArtidoc($root_id, 'Artidoc test one section ' . $this->now)['id'];
        $section_1_id = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $section_2_id = $this->createRequirementArtifact('Section 2', 'Content of section 2');

        $this->importExistingArtifactInArtidoc($artidoc_id, $section_1_id, $section_2_id);

        $document_content = $this->getArtidocSections($artidoc_id);

        self::assertCount(2, $document_content);

        $section_1_uuid       = $document_content[0]['id'];
        $get_section_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artidoc_sections/' . $section_1_uuid),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $get_section_response->getStatusCode());

        $section_representation = json_decode($get_section_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($document_content[0], $section_representation);
    }

    /**
     * @depends testArtidocCreation
     */
    public function testOptionsConfiguration(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc/' . $id . '/configuration'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
    }

    /**
     * @depends testArtidocCreation
     */
    public function testPutConfiguration(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'artidoc/' . $id . '/configuration'
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'selected_tracker_ids' => [$this->requirements_tracker_id],
                        ],
                    ),
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

    /**
     * @depends testGetRootId
     */
    public function testUpload(int $root_id): void
    {
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc upload attachment ' . $this->now)['id'];

        $payload = [
            'artidoc_id' => $artidoc_id,
            'name' => 'filename.png',
            'file_size' => 123,
            'file_type' => 'image/png',
        ];

        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_files')->withBody(
                $this->stream_factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR))
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        self::assertSame(403, $post_response->getStatusCode());

        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_files')->withBody(
                $this->stream_factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $post_response->getStatusCode());
        $upload_response_json = json_decode($post_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue(isset($upload_response_json['download_href']));
        self::assertTrue(isset($upload_response_json['upload_href']));
    }

    /**
     * @depends testGetRootId
     */
    public function testUpdateFreeTextSection(int $root_id): void
    {
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc freetext ' . $this->now)['id'];
        $section_id = $this->postFreeTextSection($artidoc_id);
        $response   = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'artidoc_sections/' . $section_id
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'title' => 'My updated title',
                            'description' => 'My updated description',
                            'attachments' => [],
                            'level' => 1,
                        ],
                    ),
                )
            )
        );
        self::assertSame(200, $response->getStatusCode());
        $document_content = $this->getArtidocSections($artidoc_id);
        self::assertContains('My updated title', array_map(
            static function (array $section) {
                return $section['title'] ?? null;
            },
            $document_content
        ));
    }

    /**
     * @depends testGetRootId
     */
    public function testUpdateArtifactSection(int $root_id): void
    {
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc update requirement ' . $this->now)['id'];
        $req_id     = $this->createRequirementArtifact('Section 1', 'Content of section 1');
        $this->importExistingArtifactInArtidoc($artidoc_id, $req_id);

        $document_content = $this->getArtidocSections($artidoc_id);

        self::assertCount(1, $document_content);

        $section_id = $document_content[0]['id'];

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'artidoc_sections/' . $section_id
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'title' => 'My updated title',
                            'description' => 'My updated description',
                            'attachments' => [],
                            'level' => 2,
                        ],
                    ),
                )
            )
        );
        self::assertSame(200, $response->getStatusCode());

        $document_content = $this->getArtidocSections($artidoc_id);

        self::assertCount(1, $document_content);
        self::assertSame('My updated title', $document_content[0]['title']);
        self::assertSame('My updated description', $document_content[0]['description']);
        self::assertSame(2, $document_content[0]['level']);
    }

    /**
     * @depends testGetRootId
     */
    public function testDeleteFreeTextSection(int $root_id): void
    {
        $artidoc_id = $this->createArtidoc($root_id, 'Artidoc delete freetext ' . $this->now)['id'];
        $section_id = $this->postFreeTextSection($artidoc_id);
        $response   = $this->getResponse(
            $this->request_factory->createRequest(
                'DELETE',
                'artidoc_sections/' . $section_id
            )
        );
        self::assertSame(204, $response->getStatusCode());

        $document_content = $this->getArtidocSections($artidoc_id);
        self::assertEmpty($document_content);
    }

    private function postFreeTextSection(int $id): string
    {
        $post_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artidoc_sections')->withBody(
                $this->stream_factory->createStream(json_encode([
                    'artidoc_id' => $id,
                    'section' => [
                        'position' => null,
                        'content' => [
                            'title' => 'My freetext title',
                            'description' => 'My freetext description',
                            'type' => 'freetext',
                            'attachments' => [],
                            'level' => 1,
                        ],
                    ],
                ], JSON_THROW_ON_ERROR))
            ),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        self::assertSame(200, $post_response->getStatusCode());
        json_decode($post_response->getBody()->getContents(), null, 512, JSON_THROW_ON_ERROR)->id;

        $document_content = $this->getArtidocSections($id);
        return $document_content[0]['id'];
    }
}
