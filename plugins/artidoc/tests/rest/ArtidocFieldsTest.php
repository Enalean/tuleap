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
use ProjectUGroup;
use Psl\Json;
use Psr\Http\Message\ResponseInterface;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\REST\v1\ArtifactSection\Field\FieldType;
use Tuleap\Artidoc\Tests\ArtidocAPIHelper;
use Tuleap\Artidoc\Tests\DocumentPermissions;
use Tuleap\Artidoc\Tests\Setup\ArtidocFieldsPreparator;
use Tuleap\Artidoc\Tests\SiteAdminProjectApproval;
use Tuleap\Disposable\Dispose;
use Tuleap\Docman\Test\rest\Helper\DocmanAPIHelper;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RestBase;
use Tuleap\REST\Tests\API\ProjectsAPIHelper;
use Tuleap\TestManagement\REST\Tests\API\TestManagementAPIHelper;
use Tuleap\TestManagement\Type\TypeCoveredByPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\REST\Tests\TrackerRESTHelper;
use Tuleap\Tracker\REST\Tests\TrackerRESTHelperFactory;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtidocFieldsTest extends RestBase
{
    private const string PROJECT_LABEL                 = 'Artidoc Fields';
    private const string ALL_FIELDS_TRACKER_SHORT_NAME = 'all_fields';
    private const string TEST_CASE_TRACKER_SHORT_NAME  = 'test_case';
    private const string TEST_EXEC_TRACKER_SHORT_NAME  = 'test_exec';
    private const string TEXT_VALUE                    = 'hermoglyphic stepfatherhood';
    private const string STRING_VALUE                  = 'ketole missal';
    private const int    INT_VALUE                     = 223;
    private const float  FLOAT_VALUE                   = 306.21;
    private const int    COMPUTED_VALUE                = 456;
    private const string DATE_VALUE                    = '2029-03-14';
    private const string STATIC_LIST_VALUE             = 'Dos';
    private const string INTEGRATORS_UGROUP_NAME       = 'Integrators';
    private const string LINKED_ARTIFACT_TITLE         = 'Zannichellia vernant';
    private const string STEP_DEFINITION_DESCRIPTION   = 'rorulent triradiate';
    private const string STEP_DEFINITION_EXPECTATION   = 'generating rosebush';
    private const string STEP_EXECUTION_STATUS         = 'passed';
    private ProjectsAPIHelper $projects_api;
    private DocmanAPIHelper $docman_api;
    private TestManagementAPIHelper $test_management_api;
    private ArtidocAPIHelper $artidoc_api;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->projects_api        = new ProjectsAPIHelper($this->rest_request, $this->request_factory);
        $this->docman_api          = new DocmanAPIHelper($this->rest_request, $this->request_factory);
        $this->test_management_api = new TestManagementAPIHelper($this->rest_request, $this->request_factory);
        $this->artidoc_api         = new ArtidocAPIHelper(
            $this->rest_request,
            $this->request_factory,
            $this->stream_factory
        );
    }

    public function testReadonlyFields(): void
    {
        $template_project_id = $this->projects_api->findProjectId(
            ArtidocFieldsPreparator::FIELDS_TEMPLATE_SHORTNAME
        );
        $real_project_id     = $this->createProject($template_project_id);
        $root_folder_id      = $this->docman_api->getRootFolderID($real_project_id);

        $trackers           = new TrackerRESTHelperFactory(
            $this->rest_request,
            $this->request_factory,
            $this->stream_factory,
            $real_project_id,
            BaseTestDataBuilder::TEST_USER_1_NAME
        );
        $all_fields_tracker = $trackers->getTrackerRest(self::ALL_FIELDS_TRACKER_SHORT_NAME);
        $this->configureAllFieldTypes($all_fields_tracker, $root_folder_id, $real_project_id);

        $test_case_tracker     = $trackers->getTrackerRest(self::TEST_CASE_TRACKER_SHORT_NAME);
        $test_case_artifact_id = $this->configureStepsDefinition($test_case_tracker, $root_folder_id, $real_project_id);

        $test_exec_tracker = $trackers->getTrackerRest(self::TEST_EXEC_TRACKER_SHORT_NAME);
        $this->configureStepsExecution($test_exec_tracker, $root_folder_id, $real_project_id, $test_case_artifact_id);

        $this->testErrorsInConfiguration($all_fields_tracker, $root_folder_id, $real_project_id);
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
                                'label'            => self::PROJECT_LABEL,
                                'description'      => '',
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
        int $root_folder_id,
        int $project_id,
    ): void {
        $artidoc_id = $this->artidoc_api->createArtidoc(
            $root_folder_id,
            'Artidoc with fields',
            DocumentPermissions::buildProjectMembersCanManage($project_id)
        )['id'];

        $string_field_id           = $all_fields_tracker->getFieldByShortName('string')['field_id'];
        $text_field_id             = $all_fields_tracker->getFieldByShortName('text')['field_id'];
        $int_field_id              = $all_fields_tracker->getFieldByShortName('integer')['field_id'];
        $float_field_id            = $all_fields_tracker->getFieldByShortName('float')['field_id'];
        $computed_field_id         = $all_fields_tracker->getFieldByShortName('computed')['field_id'];
        $date_field_id             = $all_fields_tracker->getFieldByShortName('date')['field_id'];
        $permissions_field_id      = $all_fields_tracker->getFieldByShortName('permissions')['field_id'];
        $selectbox_static_id       = $all_fields_tracker->getFieldByShortName('selectbox_static')['field_id'];
        $radio_users_id            = $all_fields_tracker->getFieldByShortName('radio_users_registered')['field_id'];
        $multi_user_groups_id      = $all_fields_tracker->getFieldByShortName('msb_ugroups')['field_id'];
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
                            ['field_id' => $radio_users_id, 'display_type' => DisplayType::COLUMN->value],
                            ['field_id' => $multi_user_groups_id, 'display_type' => DisplayType::COLUMN->value],
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

        $user_groups               = $this->projects_api->getUserGroupsOfProject($project_id);
        $integrators_user_group_id = $user_groups->getUserGroupByShortName(self::INTEGRATORS_UGROUP_NAME)['id'];

        $artifact_to_link_id = $all_fields_tracker->createArtifact([
            $all_fields_tracker->getSubmitTextValue('title', self::LINKED_ARTIFACT_TITLE),
        ])['id'];

        $project_members_id = $project_id . '_' . ProjectUGroup::PROJECT_MEMBERS;

        $artifact = $all_fields_tracker->createArtifact(
            [
                $all_fields_tracker->getSubmitTextValue('title', 'All fields'),
                $all_fields_tracker->getSubmitTextValue('description', 'legpuller hexapod'),
                ['field_id' => $text_field_id, 'value' => self::TEXT_VALUE],
                ['field_id' => $string_field_id, 'value' => self::STRING_VALUE],
                ['field_id' => $int_field_id, 'value' => self::INT_VALUE],
                ['field_id' => $float_field_id, 'value' => self::FLOAT_VALUE],
                ['field_id' => $computed_field_id, 'manual_value' => self::COMPUTED_VALUE],
                ['field_id' => $date_field_id, 'value' => self::DATE_VALUE],
                $all_fields_tracker->getSubmitListValue('selectbox_static', self::STATIC_LIST_VALUE),
                [
                    'field_id'       => $radio_users_id,
                    'bind_value_ids' => [
                        $this->user_ids[BaseTestDataBuilder::TEST_USER_1_NAME],
                    ],
                ],
                [
                    'field_id'       => $multi_user_groups_id,
                    'bind_value_ids' => [$project_members_id, $integrators_user_group_id],
                ],
                [
                    'field_id' => $permissions_field_id,
                    'value'    => [
                        'granted_groups' => [$project_members_id],
                    ],
                ],
                [
                    'field_id'  => $artifact_links_field_id,
                    'all_links' => [
                        ['id' => $artifact_to_link_id, 'direction' => 'forward', 'type' => ArtifactLinkField::DEFAULT_LINK_TYPE],
                    ],
                ],
            ]
        );

        $this->artidoc_api->importExistingArtifactInArtidoc(
            $artidoc_id,
            BaseTestDataBuilder::TEST_USER_1_NAME,
            $artifact['id']
        );

        $sections         = $this->artidoc_api->getArtidocSections($artidoc_id);
        $artifact_section = $sections[0];
        if (! array_key_exists('fields', $artifact_section)) {
            throw new \RuntimeException('Expected a "fields" key in section representation');
        }
        self::assertCount(15, $artifact_section['fields']);

        $text_field = $artifact_section['fields'][0];
        self::assertSame(FieldType::TEXT->value, $text_field['type']);
        self::assertSame('Text', $text_field['label']);
        self::assertSame(DisplayType::BLOCK->value, $text_field['display_type']);
        self::assertStringContainsString(self::TEXT_VALUE, $text_field['value']);

        self::assertSame(
            [
                'type'         => FieldType::TEXT->value,
                'label'        => 'String',
                'display_type' => DisplayType::COLUMN->value,
                'value'        => self::STRING_VALUE,
            ],
            $artifact_section['fields'][1]
        );

        self::assertSame(
            [
                'type'         => FieldType::NUMERIC->value,
                'label'        => 'Integer',
                'display_type' => DisplayType::COLUMN->value,
                'value'        => self::INT_VALUE,
            ],
            $artifact_section['fields'][2]
        );

        self::assertSame(
            [
                'type'         => FieldType::NUMERIC->value,
                'label'        => 'Float',
                'display_type' => DisplayType::COLUMN->value,
                'value'        => self::FLOAT_VALUE,
            ],
            $artifact_section['fields'][3]
        );

        self::assertSame(
            [
                'type'         => FieldType::NUMERIC->value,
                'label'        => 'Computed',
                'display_type' => DisplayType::COLUMN->value,
                'value'        => self::COMPUTED_VALUE,
            ],
            $artifact_section['fields'][4]
        );

        $date_field = $artifact_section['fields'][5];
        self::assertSame(FieldType::DATE->value, $date_field['type']);
        self::assertSame('Date', $date_field['label']);
        self::assertSame(DisplayType::COLUMN->value, $date_field['display_type']);
        self::assertIsString($date_field['value']);
        self::assertFalse($date_field['with_time']);

        self::assertSame(
            [
                'type'         => FieldType::PERMISSIONS->value,
                'label'        => 'Permissions',
                'display_type' => DisplayType::COLUMN->value,
                'value'        => [
                    ['label' => 'Project members'],
                ],
            ],
            $artifact_section['fields'][6]
        );

        self::assertSame(
            [
                'type'         => FieldType::STATIC_LIST->value,
                'label'        => 'Selectbox static',
                'display_type' => DisplayType::COLUMN->value,
                'value'        => [
                    ['label' => self::STATIC_LIST_VALUE, 'tlp_color' => ''],
                ],
            ],
            $artifact_section['fields'][7]
        );

        $user_list_field = $artifact_section['fields'][8];
        self::assertSame(FieldType::USER_LIST->value, $user_list_field['type']);
        self::assertSame('Radio users (members)', $user_list_field['label']);
        self::assertSame(DisplayType::COLUMN->value, $user_list_field['display_type']);
        self::assertCount(1, $user_list_field['value']);
        self::assertSame(BaseTestDataBuilder::TEST_USER_1_DISPLAYNAME, $user_list_field['value'][0]['display_name']);
        self::assertIsString($user_list_field['value'][0]['avatar_url']);

        self::assertSame(
            [
                'type'         => FieldType::USER_GROUPS_LIST->value,
                'label'        => 'MSB ugroups',
                'display_type' => DisplayType::COLUMN->value,
                'value'        => [
                    ['label' => 'Project members'],
                    ['label' => self::INTEGRATORS_UGROUP_NAME],
                ],
            ],
            $artifact_section['fields'][9]
        );

        $submitted_by_field = $artifact_section['fields'][10];
        self::assertSame(FieldType::USER->value, $submitted_by_field['type']);
        self::assertSame('Submitted By', $submitted_by_field['label']);
        self::assertSame(DisplayType::COLUMN->value, $submitted_by_field['display_type']);
        self::assertSame(BaseTestDataBuilder::TEST_USER_1_DISPLAYNAME, $submitted_by_field['value']['display_name']);
        self::assertIsString($submitted_by_field['value']['avatar_url']);

        $last_update_by_field = $artifact_section['fields'][11];
        self::assertSame(FieldType::USER->value, $last_update_by_field['type']);
        self::assertSame('Last Update By', $last_update_by_field['label']);
        self::assertSame(DisplayType::COLUMN->value, $last_update_by_field['display_type']);
        self::assertSame(BaseTestDataBuilder::TEST_USER_1_DISPLAYNAME, $last_update_by_field['value']['display_name']);
        self::assertIsString($last_update_by_field['value']['avatar_url']);

        $submitted_on_field = $artifact_section['fields'][12];
        self::assertSame(FieldType::DATE->value, $submitted_on_field['type']);
        self::assertSame('Submitted On', $submitted_on_field['label']);
        self::assertSame(DisplayType::COLUMN->value, $submitted_on_field['display_type']);
        self::assertIsString($submitted_on_field['value']);
        self::assertTrue($submitted_on_field['with_time']);

        $last_update_date_field = $artifact_section['fields'][13];
        self::assertSame(FieldType::DATE->value, $last_update_date_field['type']);
        self::assertSame('Last Modified On', $last_update_date_field['label']);
        self::assertSame(DisplayType::COLUMN->value, $last_update_date_field['display_type']);
        self::assertIsString($last_update_date_field['value']);
        self::assertTrue($last_update_date_field['with_time']);

        self::assertSame(
            [
                'type'         => FieldType::ARTIFACT_LINK->value,
                'label'        => 'Artifact link',
                'display_type' => DisplayType::BLOCK->value,
                'value'        => [
                    [
                        'link_label'        => 'is Linked to',
                        'tracker_shortname' => self::ALL_FIELDS_TRACKER_SHORT_NAME,
                        'tracker_color'     => 'sherwood-green',
                        'project'           => [
                            'id'    => $project_id,
                            'label' => self::PROJECT_LABEL,
                            'icon'  => '',
                        ],
                        'artifact_id'       => $artifact_to_link_id,
                        'title'             => self::LINKED_ARTIFACT_TITLE,
                        'html_uri'          => '/plugins/tracker/?aid=' . $artifact_to_link_id,
                        'status'            => null,
                        'link_type' => [
                            'shortname' => ArtifactLinkField::DEFAULT_LINK_TYPE,
                            'direction' => LinkDirection::FORWARD->value,
                        ],
                    ],
                ],
            ],
            $artifact_section['fields'][14]
        );
    }

    private function configureStepsDefinition(
        TrackerRESTHelper $test_case_tracker,
        int $root_folder_id,
        int $project_id,
    ): int {
        $artidoc_id = $this->artidoc_api->createArtidoc(
            $root_folder_id,
            'Artidoc with Steps definition',
            DocumentPermissions::buildProjectMembersCanManage($project_id)
        )['id'];

        $summary_field_id          = $test_case_tracker->getFieldByShortName('summary')['field_id'];
        $steps_definition_field_id = $test_case_tracker->getFieldByShortName('steps')['field_id'];

        $put_configuration_response = $this->putConfigurationForField(
            $artidoc_id,
            $test_case_tracker->getTrackerID(),
            $steps_definition_field_id,
            DisplayType::BLOCK
        );
        self::assertSame(200, $put_configuration_response->getStatusCode());

        $artifact = $test_case_tracker->createArtifact(
            [
                ['field_id' => $summary_field_id, 'value' => 'Test Case'],
                [
                    'field_id' => $steps_definition_field_id,
                    'value'    => [
                        [
                            'description'             => self::STEP_DEFINITION_DESCRIPTION,
                            'description_format'      => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                            'expected_results'        => self::STEP_DEFINITION_EXPECTATION,
                            'expected_results_format' => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                        ],
                    ],
                ],
            ]
        );

        $artifact_id = $artifact['id'];
        $this->artidoc_api->importExistingArtifactInArtidoc(
            $artidoc_id,
            BaseTestDataBuilder::TEST_USER_1_NAME,
            $artifact_id
        );

        $sections         = $this->artidoc_api->getArtidocSections($artidoc_id);
        $artifact_section = $sections[0];
        if (! array_key_exists('fields', $artifact_section)) {
            throw new \RuntimeException('Expected a "fields" key in section representation');
        }
        self::assertCount(1, $artifact_section['fields']);

        $steps_definition = $artifact_section['fields'][0];
        self::assertSame(FieldType::STEPS_DEFINITION->value, $steps_definition['type']);
        self::assertSame('Steps definition', $steps_definition['label']);
        self::assertSame(DisplayType::BLOCK->value, $steps_definition['display_type']);
        self::assertStringContainsString(
            self::STEP_DEFINITION_DESCRIPTION,
            $steps_definition['value'][0]['description']
        );
        self::assertStringContainsString(
            self::STEP_DEFINITION_EXPECTATION,
            $steps_definition['value'][0]['expected_results']
        );

        $steps_in_column_response = $this->putConfigurationForField(
            $artidoc_id,
            $test_case_tracker->getTrackerID(),
            $steps_definition_field_id,
            DisplayType::COLUMN
        );
        // Steps definition field is not allowed to have column display type
        self::assertSame(400, $steps_in_column_response->getStatusCode());

        return $artifact_id;
    }

    private function configureStepsExecution(
        TrackerRESTHelper $test_exec_tracker,
        int $root_folder_id,
        int $project_id,
        int $test_case_artifact_id,
    ): void {
        $artidoc_id = $this->artidoc_api->createArtidoc(
            $root_folder_id,
            'Artidoc with Steps execution',
            DocumentPermissions::buildProjectMembersCanManage($project_id)
        )['id'];

        $summary_field_id         = $test_exec_tracker->getFieldByShortName('summary')['field_id'];
        $steps_execution_field_id = $test_exec_tracker->getFieldByShortName('steps_results')['field_id'];
        $artifact_links_field_id  = $test_exec_tracker->getFieldByShortName('artifact_links')['field_id'];

        $put_configuration_response = $this->putConfigurationForField(
            $artidoc_id,
            $test_exec_tracker->getTrackerID(),
            $steps_execution_field_id,
            DisplayType::BLOCK
        );
        self::assertSame(200, $put_configuration_response->getStatusCode());

        $step_ids = $this->test_management_api->getTestDefinition($test_case_artifact_id)->getStepIds();

        $artifact = $test_exec_tracker->createArtifact(
            [
                ['field_id' => $summary_field_id, 'value' => 'Test Exec'],
                [
                    'field_id' => $steps_execution_field_id,
                    'value'    => [
                        'steps_results' => [
                            $step_ids[0] => self::STEP_EXECUTION_STATUS,
                        ],
                    ],
                ],
                [
                    'field_id'  => $artifact_links_field_id,
                    'all_links' => [
                        ['id' => $test_case_artifact_id, 'direction' => 'forward', 'type' => TypeCoveredByPresenter::TYPE_COVERED_BY],
                    ],
                ],
            ]
        );

        $this->artidoc_api->importExistingArtifactInArtidoc(
            $artidoc_id,
            BaseTestDataBuilder::TEST_USER_1_NAME,
            $artifact['id']
        );

        $sections         = $this->artidoc_api->getArtidocSections($artidoc_id);
        $artifact_section = $sections[0];
        if (! array_key_exists('fields', $artifact_section)) {
            throw new \RuntimeException('Expected a "fields" key in section representation');
        }
        self::assertCount(1, $artifact_section['fields']);

        $steps_execution = $artifact_section['fields'][0];
        self::assertSame(FieldType::STEPS_EXECUTION->value, $steps_execution['type']);
        self::assertSame('Steps results', $steps_execution['label']);
        self::assertSame(DisplayType::BLOCK->value, $steps_execution['display_type']);
        self::assertStringContainsString(
            self::STEP_DEFINITION_DESCRIPTION,
            $steps_execution['value'][0]['description']
        );
        self::assertStringContainsString(
            self::STEP_DEFINITION_EXPECTATION,
            $steps_execution['value'][0]['expected_results']
        );
        self::assertSame(self::STEP_EXECUTION_STATUS, $steps_execution['value'][0]['status']);

        $steps_in_column_response = $this->putConfigurationForField(
            $artidoc_id,
            $test_exec_tracker->getTrackerID(),
            $steps_execution_field_id,
            DisplayType::COLUMN
        );
        // Steps execution field is not allowed to have column display type
        self::assertSame(400, $steps_in_column_response->getStatusCode());
    }

    private function testErrorsInConfiguration(
        TrackerRESTHelper $all_fields_tracker,
        int $root_folder_id,
        int $project_id,
    ): void {
        $artidoc_id = $this->artidoc_api->createArtidoc(
            $root_folder_id,
            'Artidoc with failing configuration',
            DocumentPermissions::buildProjectMembersCanManage($project_id)
        )['id'];

        $tracker_id              = $all_fields_tracker->getTrackerID();
        $title_field_id          = $all_fields_tracker->getFieldByShortName('title')['field_id'];
        $description_field_id    = $all_fields_tracker->getFieldByShortName('description')['field_id'];
        $text_field_id           = $all_fields_tracker->getFieldByShortName('text')['field_id'];
        $artifact_links_field_id = $all_fields_tracker->getFieldByShortName('artifact_link')['field_id'];

        $title_response = $this->putConfigurationForField(
            $artidoc_id,
            $tracker_id,
            $title_field_id,
            DisplayType::BLOCK,
        );
        // Title field cannot be used
        self::assertSame(400, $title_response->getStatusCode());

        $description_response = $this->putConfigurationForField(
            $artidoc_id,
            $tracker_id,
            $description_field_id,
            DisplayType::BLOCK
        );
        // Description field cannot be used
        self::assertSame(400, $description_response->getStatusCode());

        $text_field_in_column_response = $this->putConfigurationForField(
            $artidoc_id,
            $tracker_id,
            $text_field_id,
            DisplayType::COLUMN,
        );
        // Text field is not allowed to have column display type
        self::assertSame(400, $text_field_in_column_response->getStatusCode());

        $artifact_links_in_column_response = $this->putConfigurationForField(
            $artidoc_id,
            $tracker_id,
            $artifact_links_field_id,
            DisplayType::COLUMN,
        );
        // Artifact links field is not allowed to have column display type
        self::assertSame(400, $artifact_links_in_column_response->getStatusCode());
    }

    private function putConfigurationForField(
        int $artidoc_id,
        int $tracker_id,
        int $field_id,
        DisplayType $display_type,
    ): ResponseInterface {
        return $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'artidoc/' . urlencode((string) $artidoc_id) . '/configuration'
            )->withBody(
                $this->stream_factory->createStream(
                    Json\encode(
                        [
                            'selected_tracker_ids' => [$tracker_id],
                            'fields'               => [['field_id' => $field_id, 'display_type' => $display_type->value]],
                        ]
                    )
                )
            )
        );
    }
}
