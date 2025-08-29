<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Psl\Json;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Tests\ArtidocAPIHelper;
use Tuleap\Artidoc\Tests\DocumentPermissions;
use Tuleap\Artidoc\Tests\SiteAdminProjectApproval;
use Tuleap\Artidoc\Tests\Setup\ArtidocFieldsPreparator;
use Tuleap\Disposable\Dispose;
use Tuleap\Docman\Test\rest\Helper\DocmanAPIHelper;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RestBase;
use Tuleap\Tracker\REST\Tests\TrackerRESTHelper;
use Tuleap\Tracker\REST\Tests\TrackerRESTHelperFactory;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtidocFieldsTest extends RestBase
{
    private const string ALL_FIELDS_SHORT_NAME = 'all_fields';
    private ArtidocAPIHelper $artidoc_api;
    private DocmanAPIHelper $docman_api;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->artidoc_api = new ArtidocAPIHelper($this->rest_request, $this->request_factory, $this->stream_factory);
        $this->docman_api  = new DocmanAPIHelper($this->rest_request, $this->request_factory);
    }

    public function testReadonlyFields(): void
    {
        $template_project_id = $this->findTemplateProjectId();
        $real_project_id     = $this->createProject($template_project_id);
        $root_folder_id      = $this->docman_api->getRootFolderID($real_project_id);
        $artidoc_json        = $this->artidoc_api->createArtidoc(
            $root_folder_id,
            'Artidoc with fields',
            DocumentPermissions::buildProjectMembersCanManage($real_project_id)
        );
        $artidoc_id          = $artidoc_json['id'];

        $trackers           = new TrackerRESTHelperFactory(
            $this->rest_request,
            $this->request_factory,
            $this->stream_factory,
            $real_project_id,
            BaseTestDataBuilder::TEST_USER_1_NAME
        );
        $all_fields_tracker = $trackers->getTrackerRest(self::ALL_FIELDS_SHORT_NAME);

        $this->configureAllFieldTypes($all_fields_tracker, $artidoc_id);
    }

    private function findTemplateProjectId(): int
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                '/api/projects?query=' . urlencode(
                    Json\encode(['shortname' => ArtidocFieldsPreparator::FIELDS_TEMPLATE_SHORTNAME])
                )
            )
        );
        $projects = Json\decode($response->getBody()->getContents());
        foreach ($projects as $project) {
            if ($project['shortname'] === ArtidocFieldsPreparator::FIELDS_TEMPLATE_SHORTNAME) {
                return $project['id'];
            }
        }
        throw new \RuntimeException(
            sprintf('Could not find project with shortname "%s"', ArtidocFieldsPreparator::FIELDS_TEMPLATE_SHORTNAME)
        );
    }

    private function createProject(int $template_project_id): int
    {
        $now = new \DateTimeImmutable();
        return Dispose::using(
            new SiteAdminProjectApproval(),
            function (SiteAdminProjectApproval $project_approval) use ($template_project_id, $now): int {
                $project_approval->disableApprovalOfProjects();

                $response = $this->getResponse(
                    $this->request_factory->createRequest('POST', '/api/projects')
                        ->withBody(
                            $this->stream_factory->createStream(Json\encode([
                                'shortname'        => 'artidoc-fields-' . $now->getTimestamp(),
                                'description'      => 'yolo',
                                'label'            => 'Artidoc Fields',
                                'is_public'        => true,
                                'allow_restricted' => true,
                                'template_id'      => $template_project_id,
                            ]))
                        )
                );
                if ($response->getStatusCode() !== 201) {
                    throw new \RuntimeException('Could not create project from template.');
                }
                $created = Json\decode($response->getBody()->getContents());
                return $created['id'];
            }
        );
    }

    private function configureAllFieldTypes(
        TrackerRESTHelper $all_fields_tracker,
        int $artidoc_id,
    ): void {
        $string_field_id           = $all_fields_tracker->getFieldByShortName('string')['field_id'];
        $text_field_id             = $all_fields_tracker->getFieldByShortName('text')['field_id'];
        $int_field_id              = $all_fields_tracker->getFieldByShortName('integer')['field_id'];
        $float_field_id            = $all_fields_tracker->getFieldByShortName('float')['field_id'];
        $computed_field_id         = $all_fields_tracker->getFieldByShortName('computed')['field_id'];
        $date_field_id             = $all_fields_tracker->getFieldByShortName('date')['field_id'];
        $permissions_field_id      = $all_fields_tracker->getFieldByShortName('permissions')['field_id'];
        $selectbox_static_id       = $all_fields_tracker->getFieldByShortName('selectbox_static')['field_id'];
        $submitted_by_field_id     = $all_fields_tracker->getFieldByShortName('submitted_by')['field_id'];
        $last_update_by_field_id   = $all_fields_tracker->getFieldByShortName('last_update_by')['field_id'];
        $submitted_on_field_id     = $all_fields_tracker->getFieldByShortName('submitted_on')['field_id'];
        $last_update_date_field_id = $all_fields_tracker->getFieldByShortName('last_modified_on')['field_id'];
        $artifact_links_field_id   = $all_fields_tracker->getFieldByShortName('artifact_link')['field_id'];

        $put_configuration_response = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'artidoc/' . urlencode((string) $artidoc_id) . '/configuration'
            )->withBody(
                $this->stream_factory->createStream(
                    Json\encode([
                        'selected_tracker_ids' => [$all_fields_tracker->getTrackerID()],
                        'fields'               => [
                            ['field_id' => $text_field_id, 'display_type' => DisplayType::BLOCK->value],
                            ['field_id' => $string_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $int_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $float_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $computed_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $date_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $permissions_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $selectbox_static_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $submitted_by_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $last_update_by_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $submitted_on_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $last_update_date_field_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $artifact_links_field_id, 'display_type' => DisplayType::BLOCK->value],
                        ],
                    ])
                )
            )
        );
        self::assertSame(200, $put_configuration_response->getStatusCode());
    }
}
