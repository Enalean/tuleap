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

namespace Tuleap\REST;

use REST_TestDataBuilder;
use Test\Rest\TuleapConfig;
use TestDataBuilder;

/**
 * @group ProjectTests
 */
class ProjectTest extends ProjectBase
{
    use ForgeAccessSandbox;

    /**
     * @after
     */
    protected function resetProjectCreationConfiguration(): void
    {
        $tuleap_config = TuleapConfig::instance();
        $tuleap_config->enableProjectCreation();
    }

    private function getBasicAuthResponse($request)
    {
        return $this->getResponseByBasicAuth(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            REST_TestDataBuilder::TEST_USER_1_PASS,
            $request
        );
    }

    public function testPOSTDryRunForRegularUserDisabledProjectCreation(): void
    {
        $tuleap_config = TuleapConfig::instance();
        $tuleap_config->disableProjectCreation();

        $post_resource = json_encode([
            'label' => 'Test Request 9747 regular user',
            'shortname'  => 'test9747-regular-user',
            'description' => 'Test of Request 9747 for REST API Project Creation',
            'is_public' => true,
            'template_id' => $this->project_public_template_id,
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_5_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects?dry_run=true'
            )->withBody(
                $this->stream_factory->createStream($post_resource)
            )
        );

        self::assertEquals(400, $response->getStatusCode());

        $errors_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $errors_response);
        self::assertArrayHasKey('i18n_error_messages', $errors_response['error']);
        self::assertCount(1, $errors_response['error']['i18n_error_messages']);
        self::assertSame("Only site administrators can create projects.", $errors_response['error']['i18n_error_messages'][0]);
    }

    public function testPOSTDryRunForRegularUserWithErrors(): void
    {
        $post_resource = json_encode([
            'label' => 'Looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong name',
            'shortname'  => 'short_name',
            'description' => '',
            'is_public' => true,
            'template_id' => $this->project_public_template_id,
            'categories' => [
                [
                    'category_id' => 15,
                    'value_id' => 21,
                ],
            ],
            'fields' => [
                [
                    'field_id' => 100002,
                    'value'    => "field value",
                ],
            ],
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_5_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects?dry_run=true'
            )->withBody(
                $this->stream_factory->createStream($post_resource)
            )
        );

        self::assertEquals(400, $response->getStatusCode());

        $errors_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $errors_response);
        self::assertArrayHasKey('i18n_error_messages', $errors_response['error']);
        self::assertCount(4, $errors_response['error']['i18n_error_messages']);
    }

    public function testPOSTDryRunForRegularUserWithInvalidTemplateID(): void
    {
        $post_resource = json_encode(
            [
                'label' => 'Label',
                'shortname' => 'short_name',
                'description' => '',
                'is_public' => true,
                'template_id' => 1,
            ],
            JSON_THROW_ON_ERROR
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects?dry_run=true'
            )->withBody(
                $this->stream_factory->createStream($post_resource)
            )
        );

        self::assertEquals(400, $response->getStatusCode());

        $errors_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $errors_response);
        self::assertArrayHasKey('i18n_error_messages', $errors_response['error']);
        self::assertCount(1, $errors_response['error']['i18n_error_messages']);
    }

    public function testPOSTDryRunForRegularUserWithInvalidShotname(): void
    {
        $post_resource = json_encode([
            'label' => 'Test Request 9747 regular user',
            'shortname'  => '_test9747-regular-user',
            'description' => 'Test of Request 9747 for REST API Project Creation',
            'is_public' => true,
            'template_id' => $this->project_public_template_id,
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects?dry_run=true'
            )->withBody(
                $this->stream_factory->createStream($post_resource)
            )
        );

        self::assertEquals(400, $response->getStatusCode());

        $errors_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $errors_response);
        self::assertArrayHasKey('i18n_error_messages', $errors_response['error']);
        self::assertCount(1, $errors_response['error']['i18n_error_messages']);
        self::assertSame(
            "Project shortname is invalid. The reason is: Short name must start with an alphanumeric character.",
            $errors_response['error']['i18n_error_messages'][0],
        );
    }

    public function testPOSTDryRunForRegularUser(): void
    {
        $post_resource = json_encode([
            'label' => 'Test Request 9747 regular user',
            'shortname'  => 'test9747-regular-user',
            'description' => 'Test of Request 9747 for REST API Project Creation',
            'is_public' => true,
            'template_id' => $this->project_public_template_id,
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_5_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects?dry_run=true'
            )->withBody(
                $this->stream_factory->createStream($post_resource)
            )
        );

        self::assertEquals(204, $response->getStatusCode());
    }

    public function testPOSTForRegularUser(): void
    {
        $post_resource = json_encode([
            'label' => 'Test Request 9747 regular user',
            'shortname'  => 'test9747-regular-user',
            'description' => 'Test of Request 9747 for REST API Project Creation',
            'is_public' => true,
            'template_id' => $this->project_public_template_id,
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_5_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects'
            )->withBody(
                $this->stream_factory->createStream($post_resource)
            )
        );

        self::assertEquals(201, $response->getStatusCode());

        $create_project_id = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $this->removeAdminFromProjectMembers(
            $create_project_id,
            REST_TestDataBuilder::TEST_USER_5_NAME,
        );
    }

    public function testPOSTForRegularUserWithTemplateProjectUserCantAccess(): void
    {
        $post_resource = json_encode([
            'label'       => 'Test from template without access',
            'shortname'   => 'template-no-access-user',
            'description' => 'Test project template for REST API Project Creation',
            'is_public'   => true,
            'template_id' => $this->project_private_template_id,
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects'
            )->withBody(
                $this->stream_factory->createStream($post_resource)
            )
        );

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testPOSTForRegularUserWithPrivateTemplateProjectUserCanAccess(): void
    {
        $post_resource = json_encode([
            'label'       => 'Test from private template with access',
            'shortname'   => 'template-private-user-access',
            'description' => 'Test project template for REST API Project Creation',
            'is_public'   => true,
            'template_id' => $this->project_private_template_id,
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_5_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects'
            )->withBody(
                $this->stream_factory->createStream($post_resource)
            )
        );

        self::assertEquals(201, $response->getStatusCode());
    }

    public function testPOSTForRegularUserWithArchive(): void
    {
        $file_creation_body_content = json_encode([
            'label' => 'Test create from archive file',
            'shortname' => 'from-archive',
            'description' => '',
            'is_public' => true,
            'from_archive' => [
                'file_name' => 'my file',
                'file_size' => 1234,
            ],
        ]);

        $request  = $this->request_factory->createRequest('POST', 'projects')->withBody(
            $this->stream_factory->createStream($file_creation_body_content)
        );
        $response = $this->getResponse($request);

        $this->assertEquals(201, $response->getStatusCode());

        $file_representation = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals("/uploads/project/file/1", $file_representation['upload_href']);
    }

    /**
     * Transfer project ownership and administration to a catch all user
     *
     * Historically projects where created but not approved. Tests were written with this assumption so, for instance
     * membership verification didn't take into account project created in the tests. Now that the projects are auto
     * approved by default, we need to remove the project creator from the project they just created to avoid fixing
     * all downstream tests.
     *
     * Thanks to project_ownership crap, in order to remove user from admin, we have to:
     * * Add the new admin
     * * As site admin change project owner
     * * Remove the old admin
     * * Remove from project members
     */
    private function removeAdminFromProjectMembers(int $project_id, string $original_project_admin): void
    {
        $response = $this->getResponseByName(
            $original_project_admin,
            $this->request_factory->createRequest(
                'PUT',
                sprintf('user_groups/%d_4/users', $project_id)
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'user_references' => [
                                ['username' => \TestDataBuilder::TEST_USER_CATCH_ALL_PROJECT_ADMIN],
                                ['username' => $original_project_admin],
                            ],
                        ],
                        JSON_THROW_ON_ERROR
                    )
                )
            )
        );

        self::assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'PUT',
                sprintf('project_ownership/%d', $project_id)
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'project_owner' => [
                                'username' => \TestDataBuilder::TEST_USER_CATCH_ALL_PROJECT_ADMIN,
                            ],
                        ],
                        JSON_THROW_ON_ERROR
                    )
                )
            )
        );

        self::assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            $original_project_admin,
            $this->request_factory->createRequest(
                'PUT',
                sprintf('user_groups/%d_4/users', $project_id)
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'user_references' => [
                                [
                                    'username' => \TestDataBuilder::TEST_USER_CATCH_ALL_PROJECT_ADMIN,
                                ],
                            ],
                        ],
                        JSON_THROW_ON_ERROR
                    )
                )
            )
        );

        self::assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            \TestDataBuilder::TEST_USER_CATCH_ALL_PROJECT_ADMIN,
            $this->request_factory->createRequest(
                'PUT',
                sprintf('user_groups/%d_3/users', $project_id)
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'user_references' => [
                                [
                                    'username' => \TestDataBuilder::TEST_USER_CATCH_ALL_PROJECT_ADMIN,
                                ],
                            ],
                        ],
                        JSON_THROW_ON_ERROR
                    )
                )
            )
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPOSTForAdmin()
    {
        $post_resource = json_encode([
            'label' => 'Test Request 9747',
            'shortname'  => 'test9747',
            'description' => 'Test of Request 9747 for REST API Project Creation',
            'is_public' => true,
            'template_id' => 100,
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects'
            )->withBody(
                $this->stream_factory->createStream(
                    $post_resource
                )
            )
        );

        $project = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(201, $response->getStatusCode());
        self::assertArrayHasKey("id", $project);

        $this->removeAdminFromProjectMembers(
            $project['id'],
            REST_TestDataBuilder::ADMIN_USER_NAME,
        );
    }

    public function testPOSTForRestProjectManager()
    {
        $post_resource = json_encode([
            'label' => 'Test Request 9748',
            'shortname'  => 'test9748',
            'description' => 'Test of Request 9748 for REST API Project Creation',
            'is_public' => true,
            'template_id' => $this->project_public_template_id,
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects'
            )->withBody(
                $this->stream_factory->createStream(
                    $post_resource
                )
            )
        );

        $project = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(201, $response->getStatusCode());
        self::assertArrayHasKey("id", $project);
    }

    public function testProjectCreationWithAnIncorrectProjectIDFails(): void
    {
        $post_resource = json_encode([
            'label'       => 'Invalid project creation template ID',
            'shortname'   => 'invalidtemplateid',
            'description' => 'Invalid project creation template ID',
            'is_public'   => true,
            'template_id' => 0,
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects'
            )->withBody(
                $this->stream_factory->createStream(
                    $post_resource
                )
            )
        );
        self::assertEquals(400, $response->getStatusCode());
    }

    public function testProjectCreationWithXMLTemplate(): void
    {
        $post_resource = json_encode([
            'label'       => 'Created from scrum XML template',
            'shortname'   => 'from-scrum-template',
            'description' => 'I create projects',
            'is_public'   => false,
            'xml_template_name' => 'scrum',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_5_NAME,
            $this->request_factory->createRequest(
                'POST',
                'projects'
            )->withBody(
                $this->stream_factory->createStream(
                    $post_resource
                )
            )
        );

        self::assertEquals(201, $response->getStatusCode());
        $project = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals('Created from scrum XML template', $project['label']);
    }

    public function testGET()
    {
        $response      = $this->getResponse($this->request_factory->createRequest('GET', 'projects'));
        $json_projects = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue(
            $this->valuesArePresent(
                [
                    $this->project_private_member_id,
                    $this->project_public_id,
                    $this->project_public_member_id,
                    $this->project_pbi_id,
                ],
                $this->getIds($json_projects)
            )
        );

        self::assertArrayHasKey('resources', $json_projects[0]);
        self::assertContains(
            [
                'type' => 'trackers',
                'uri' => 'projects/' . $this->project_private_member_id . '/trackers',
            ],
            $json_projects[0]['resources']
        );

        self::assertContains(
            [
                'type' => 'backlog',
                'uri' => 'projects/' . $this->project_private_member_id . '/backlog',
            ],
            $json_projects[0]['resources']
        );

        self::assertContains(
            [
                'type' => 'milestones',
                'uri' => 'projects/' . $this->project_private_member_id . '/milestones',
            ],
            $json_projects[0]['resources']
        );

        self::assertContains(
            [
                'type' => 'plannings',
                'uri' => 'projects/' . $this->project_private_member_id . '/plannings',
            ],
            $json_projects[0]['resources']
        );

        self::assertContains(
            [
                'type' => 'user_groups',
                'uri' => 'projects/' . $this->project_private_member_id . '/user_groups',
            ],
            $json_projects[0]['resources']
        );

        self::assertContains(
            [
                'type' => 'labels',
                'uri' => 'projects/' . $this->project_private_member_id . '/labels',
            ],
            $json_projects[0]['resources']
        );

        self::assertContains(
            [
                'type' => 'project_services',
                'uri' => 'projects/' . $this->project_private_member_id . '/project_services',
            ],
            $json_projects[0]['resources']
        );

        self::assertContains(
            [
                'type' => 'docman_service',
                'uri'  => 'projects/' . $this->project_private_member_id . '/docman_service',
            ],
            $json_projects[0]['resources']
        );
        self::assertContains(
            [
                'type' => 'docman_metadata',
                'uri'  => 'projects/' . $this->project_private_member_id . '/docman_metadata',
            ],
            $json_projects[0]['resources']
        );

        self::assertArrayHasKey('id', $json_projects[0]);
        self::assertEquals($this->project_private_member_id, $json_projects[0]['id']);

        self::assertArrayHasKey('uri', $json_projects[0]);
        self::assertEquals('projects/' . $this->project_private_member_id, $json_projects[0]['uri']);

        self::assertArrayHasKey('label', $json_projects[0]);
        self::assertEquals('Private member', $json_projects[0]['label']);

        self::assertArrayHasKey('access', $json_projects[0]);
        self::assertEquals('private', $json_projects[0]['access']);

        self::assertArrayHasKey('is_member_of', $json_projects[0]);
        self::assertTrue($json_projects[0]['is_member_of']);

        self::assertArrayHasKey('additional_informations', $json_projects[0]);
        self::assertEquals(
            $this->releases_tracker_id,
            $json_projects[0]['additional_informations']['agiledashboard']['root_planning']['milestone_tracker']['id']
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGETByShortname()
    {
        $response      = $this->getResponse($this->request_factory->createRequest('GET', 'projects?query=' . urlencode('{"shortname":"pbi-6348"}')));
        $json_projects = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('id', $json_projects[0]);
        self::assertEquals($this->project_pbi_id, $json_projects[0]['id']);
        self::assertCount(1, $json_projects);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGETByMembership()
    {
        $response      = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('GET', 'projects?query=' . urlencode('{"is_member_of":true}'))
        );
        $json_projects = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $json_projects);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGETByAdministratorship(): void
    {
        $response      = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'projects?query=' . urlencode('{"is_admin_of":true}'))
        );
        $json_projects = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertGreaterThan(5, count($json_projects));

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGETByNonMembershipShouldFail()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects?query=' . urlencode('{"is_member_of":false}')));

        self::assertEquals(400, $response->getStatusCode());
    }

    private function valuesArePresent(array $values, array $array)
    {
        foreach ($values as $value) {
            if (! in_array($value, $array)) {
                return false;
            }
        }

        return true;
    }

    private function getIds(array $json_with_id)
    {
        $ids = [];
        foreach ($json_with_id as $json) {
            $ids[] = $json['id'];
        }
        return $ids;
    }

    public function testProjectRepresentationContainsShortname()
    {
        $response     = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', "projects/$this->project_pbi_id"));
        $json_project = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('shortname', $json_project);

        self::assertEquals($json_project['shortname'], REST_TestDataBuilder::PROJECT_PBI_SHORTNAME);
    }

    public function testThatAdminGetEvenPrivateProjectThatSheIsNotMemberOf()
    {
        $response       = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('GET', 'projects/'));
        $admin_projects = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($admin_projects as $project) {
            if ($project['id'] !== $this->project_private_id) {
                continue;
            }

            self::assertFalse($project['is_member_of']);

            $project_members_uri = "user_groups/$this->project_private_id" . "_3/users";
            $project_members     = json_decode(
                $this->getResponseByName(
                    REST_TestDataBuilder::ADMIN_USER_NAME,
                    $this->request_factory->createRequest('GET', $project_members_uri)
                )->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            foreach ($project_members as $member) {
                self::assertNotEquals('admin', $member['username']);
            }
            return;
        }

        $this->fail('Admin should be able to get private projects even if she is not member of');
    }

    public function testGETbyIdForAdmin()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id));

        $json_project = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('resources', $json_project);
        self::assertContains(
            [
                'type' => 'trackers',
                'uri' => 'projects/' . $this->project_private_member_id . '/trackers',
            ],
            $json_project['resources']
        );

        self::assertContains(
            [
                'type' => 'backlog',
                'uri' => 'projects/' . $this->project_private_member_id . '/backlog',
            ],
            $json_project['resources']
        );

        self::assertContains(
            [
                'type' => 'milestones',
                'uri' => 'projects/' . $this->project_private_member_id . '/milestones',
            ],
            $json_project['resources']
        );

        self::assertContains(
            [
                'type' => 'plannings',
                'uri' => 'projects/' . $this->project_private_member_id . '/plannings',
            ],
            $json_project['resources']
        );

        self::assertContains(
            [
                'type' => 'user_groups',
                'uri' => 'projects/' . $this->project_private_member_id . '/user_groups',
            ],
            $json_project['resources']
        );

        self::assertContains(
            [
                'type' => 'labels',
                'uri' => 'projects/' . $this->project_private_member_id . '/labels',
            ],
            $json_project['resources']
        );

        self::assertArrayHasKey('id', $json_project);
        self::assertEquals($this->project_private_member_id, $json_project['id']);

        self::assertArrayHasKey('uri', $json_project);
        self::assertEquals('projects/' . $this->project_private_member_id, $json_project['uri']);

        self::assertArrayHasKey('label', $json_project);
        self::assertEquals('Private member', $json_project['label']);

        self::assertArrayHasKey('description', $json_project);
        self::assertEquals("For test", $json_project['description']);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGETbyIdForAdminProjectReturnAdditionalField()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('GET', 'projects/' . REST_TestDataBuilder::DEFAULT_TEMPLATE_PROJECT_ID));

        $json_project = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('additional_fields', $json_project);
        self::assertEquals([["name" => "Test Rest", "value" => "Admin test"]], $json_project['additional_fields']);
    }

    public function testGETbyIdForDelegatedRestProjectManager()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME, $this->request_factory->createRequest('GET', 'projects/' . $this->project_deleted_id));

        $json_project = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($json_project['status'], 'deleted');
    }

    public function testOPTIONSprojects()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('OPTIONS', 'projects'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSbyIdForAdmin()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id));

        self::assertEquals(['OPTIONS', 'GET', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testOPTIONSbyIdForDelegatedRestProjectManager()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME, $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_deleted_id));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testOPTIONSbyIdForProjectMember()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGETbyIdForForbiddenUser(): void
    {
        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', 'projects/' . $this->project_deleted_id));
        self::assertEquals(403, $response->getStatusCode());
    }

    public function testGETbyIdForSystemProject(): void
    {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('GET', 'projects/' . REST_TestDataBuilder::DEFAULT_TEMPLATE_PROJECT_ID));
        self::assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', 'projects/' . REST_TestDataBuilder::DEFAULT_TEMPLATE_PROJECT_ID));
        self::assertEquals(404, $response->getStatusCode());
    }

    public function testGETBadRequest()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('GET', 'projects/abc'));
        self::assertEquals(400, $response->getStatusCode());
    }

    public function testGETUnknownProject()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('GET', 'projects/1234567890'));
        self::assertEquals(404, $response->getStatusCode());
    }

    public function testGETmilestones()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/milestones')
        );

        $milestones = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $milestones);

        $release_milestone = $milestones[0];
        self::assertArrayHasKey('id', $release_milestone);
        self::assertEquals($release_milestone['label'], "Release 1.0");
        self::assertEquals($release_milestone['project'], [
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member',
            'icon' => '',
        ]);
        self::assertArrayHasKey('id', $release_milestone['artifact']);
        self::assertArrayHasKey('uri', $release_milestone['artifact']);
        self::assertMatchesRegularExpression('%^artifacts/[0-9]+$%', $release_milestone['artifact']['uri']);

        self::assertArrayHasKey('open', $release_milestone['status_count']);
        self::assertArrayHasKey('closed', $release_milestone['status_count']);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGETmilestonesDoesNotContainStatusCountInSlimRepresentation()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/milestones?fields=slim')
        );

        $milestones = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $milestones);

        $release_milestone = $milestones[0];
        self::assertEquals($release_milestone['label'], "Release 1.0");
        self::assertEquals($release_milestone['status_count'], null);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testOPTIONSmilestones()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/milestones'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONStrackers()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/trackers'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGETtrackers()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/trackers'));

        $trackers = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(6, $trackers);

        $epics_tracker = $trackers[0];
        self::assertArrayHasKey('id', $epics_tracker);
        self::assertEquals($epics_tracker['label'], "Epics");
        self::assertEquals($epics_tracker['project'], [
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member',
            'icon' => '',
        ]);

        $kanban_tracker = $trackers[1];
        self::assertArrayHasKey('id', $kanban_tracker);
        self::assertEquals($kanban_tracker['label'], "Kanban Tasks");
        self::assertEquals($kanban_tracker['project'], [
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member',
            'icon' => '',
        ]);

        $releases_tracker = $trackers[2];
        self::assertArrayHasKey('id', $releases_tracker);
        self::assertEquals($releases_tracker['label'], "Releases");
        self::assertEquals($releases_tracker['project'], [
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member',
            'icon' => '',
        ]);

        $sprints_tracker = $trackers[3];
        self::assertArrayHasKey('id', $sprints_tracker);
        self::assertEquals($sprints_tracker['label'], "Sprints");
        self::assertEquals($sprints_tracker['project'], [
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member',
            'icon' => '',
        ]);

        $tasks_tracker = $trackers[4];
        self::assertArrayHasKey('id', $tasks_tracker);
        self::assertEquals($tasks_tracker['label'], "Tasks");
        self::assertEquals($tasks_tracker['project'], [
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member',
            'icon' => '',
        ]);

        $userstories_tracker = $trackers[5];
        self::assertArrayHasKey('id', $userstories_tracker);
        self::assertEquals($userstories_tracker['label'], "User Stories");
        self::assertEquals($userstories_tracker['project'], [
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member',
            'icon' => '',
        ]);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testOPTIONSbacklog()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/backlog'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETbacklog()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/backlog')
        );

        $backlog_items = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        self::assertArrayHasKey('id', $first_backlog_item);
        self::assertEquals($first_backlog_item['label'], "Epic pic");
        self::assertEquals($first_backlog_item['status'], "Open");
        self::assertEquals($first_backlog_item['artifact']['id'], $this->epic_artifact_ids[5]);
        self::assertEquals($first_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[5]);
        self::assertEquals($first_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $second_backlog_item = $backlog_items[1];
        self::assertArrayHasKey('id', $second_backlog_item);
        self::assertEquals($second_backlog_item['label'], "Epic c'est tout");
        self::assertEquals($second_backlog_item['status'], "Open");
        self::assertEquals($second_backlog_item['artifact']['id'], $this->epic_artifact_ids[6]);
        self::assertEquals($second_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[6]);
        self::assertEquals($second_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $third_backlog_item = $backlog_items[2];
        self::assertArrayHasKey('id', $third_backlog_item);
        self::assertEquals($third_backlog_item['label'], "Epic epoc");
        self::assertEquals($third_backlog_item['status'], "Open");
        self::assertEquals($third_backlog_item['artifact']['id'], $this->epic_artifact_ids[7]);
        self::assertEquals($third_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[7]);
        self::assertEquals($third_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);
    }

    public function testPUTbacklogWithoutPermission()
    {
        $response_put = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest(
                'PUT',
                'projects/' . $this->project_private_member_id . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[7] . ',' . $this->epic_artifact_ids[5] . ',' . $this->epic_artifact_ids[6] . ']'
                )
            )
        );

        self::assertEquals(403, $response_put->getStatusCode());
    }

    public function testPUTbacklog()
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'projects/' . $this->project_private_member_id . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[7] . ',' . $this->epic_artifact_ids[5] . ',' . $this->epic_artifact_ids[6] . ']'
                )
            )
        );

        self::assertEquals(200, $response_put->getStatusCode());

        $response_get  = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/backlog')
        );
        $backlog_items = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        self::assertArrayHasKey('id', $first_backlog_item);
        self::assertEquals($first_backlog_item['label'], "Epic epoc");
        self::assertEquals($first_backlog_item['status'], "Open");
        self::assertEquals($first_backlog_item['artifact']['id'], $this->epic_artifact_ids[7]);
        self::assertEquals($first_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[7]);
        self::assertEquals($first_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $second_backlog_item = $backlog_items[1];
        self::assertArrayHasKey('id', $second_backlog_item);
        self::assertEquals($second_backlog_item['label'], "Epic pic");
        self::assertEquals($second_backlog_item['status'], "Open");
        self::assertEquals($second_backlog_item['artifact']['id'], $this->epic_artifact_ids[5]);
        self::assertEquals($second_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[5]);
        self::assertEquals($second_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $third_backlog_item = $backlog_items[2];
        self::assertArrayHasKey('id', $third_backlog_item);
        self::assertEquals($third_backlog_item['label'], "Epic c'est tout");
        self::assertEquals($third_backlog_item['status'], "Open");
        self::assertEquals($third_backlog_item['artifact']['id'], $this->epic_artifact_ids[6]);
        self::assertEquals($third_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[6]);
        self::assertEquals($third_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);
    }

    public function testPUTbacklogOnlyTwoItems()
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'projects/' . $this->project_private_member_id . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[6] . ',' . $this->epic_artifact_ids[7] . ']'
                )
            )
        );

        self::assertEquals(200, $response_put->getStatusCode());

        $response_get  = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/backlog')
        );
        $backlog_items = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        self::assertArrayHasKey('id', $first_backlog_item);
        self::assertEquals($first_backlog_item['label'], "Epic pic");
        self::assertEquals($first_backlog_item['status'], "Open");
        self::assertEquals($first_backlog_item['artifact']['id'], $this->epic_artifact_ids[5]);
        self::assertEquals($first_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[5]);
        self::assertEquals($first_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $second_backlog_item = $backlog_items[1];
        self::assertArrayHasKey('id', $second_backlog_item);
        self::assertEquals($second_backlog_item['label'], "Epic c'est tout");
        self::assertEquals($second_backlog_item['status'], "Open");
        self::assertEquals($second_backlog_item['artifact']['id'], $this->epic_artifact_ids[6]);
        self::assertEquals($second_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[6]);
        self::assertEquals($second_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $third_backlog_item = $backlog_items[2];
        self::assertArrayHasKey('id', $third_backlog_item);
        self::assertEquals($third_backlog_item['label'], "Epic epoc");
        self::assertEquals($third_backlog_item['status'], "Open");
        self::assertEquals($third_backlog_item['artifact']['id'], $this->epic_artifact_ids[7]);
        self::assertEquals($third_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[7]);
        self::assertEquals($third_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);
    }

    public function testOPTIONSLabels()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/labels'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETLabels()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/labels'));

        self::assertEquals(['labels' => []], json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testOPTIONSUserGroups()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/user_groups'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETUserGroupsContainingStaticUGroups()
    {
        $response        = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/user_groups'));
        $expected_result = [

            [
                'id' => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID,
                'uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID,
                'label' => 'Project members',
                'users_uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID . '/users',
                'key' => REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_KEY,
                'short_name' => 'project_members',
                'additional_information' => ['ldap' => null],
            ],
            [
                'id' => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID,
                'uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID,
                'label' => 'Project administrators',
                'users_uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID . '/users',
                'key' => 'ugroup_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_LABEL . '_name_key',
                'short_name' => 'project_admins',
                'additional_information' => [],
            ],
            [
                'id'         => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'uri'        => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'label'      => REST_TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_LABEL,
                'users_uri'  => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID . '/users',
                'key'        => 'ugroup_file_manager_admin_name_key',
                'short_name' => 'file_manager_admins',
                'additional_information' => [],
            ],
            [
                'id' => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'label' => 'Wiki administrators',
                'users_uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID . '/users',
                'key' => 'ugroup_wiki_admin_name_key',
                'short_name' => 'wiki_admins',
                'additional_information' => [],
            ],
            [
                'id' => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FORUM_ADMIN_ID,
                'uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FORUM_ADMIN_ID,
                'label' => 'Forum moderators',
                'users_uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FORUM_ADMIN_ID . '/users',
                'key' => 'ugroup_forum_admin_name_key',
                'short_name' => 'forum_admins',
                'additional_information' => [],
            ],
            [
                'id'         => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_ADMIN_ID,
                'uri'        => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_ADMIN_ID,
                'label'      => 'News administrators',
                'users_uri'  => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_ADMIN_ID . '/users',
                'key'        => 'ugroup_news_admin_name_key',
                'short_name' => 'news_admins',
                'additional_information' => [],

            ],
            [
                'id'         => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_WRITER_ID,
                'uri'        => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_WRITER_ID,
                'label'      => 'News writers',
                'users_uri'  => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_WRITER_ID . '/users',
                'key'        => 'ugroup_news_writer_name_key',
                'short_name' => 'news_editors',
                'additional_information' => [],
            ],
            [
                'id' => (string) REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'label' => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'users_uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_1_ID . '/users',
                'key' => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'short_name' => 'static_ugroup_1',
                'additional_information' => ['ldap' => null],
            ],
            [
                'id' => (string) REST_TestDataBuilder::STATIC_UGROUP_2_ID,
                'uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_2_ID,
                'label' => REST_TestDataBuilder::STATIC_UGROUP_2_LABEL,
                'users_uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users',
                'key' => REST_TestDataBuilder::STATIC_UGROUP_2_LABEL,
                'short_name' => 'static_ugroup_2',
                'additional_information' => ['ldap' => null],
            ],
            [
                'id' => (string) REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
                'uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
                'label' => REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL,
                'users_uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID . '/users',
                'key' => REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL,
                'short_name' => REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL,
                'additional_information' => ['ldap' => null],
            ],
        ];
        self::assertEquals($expected_result, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGETUserGroupsWithSystemUserGroupsReturnsAnonymousAndRegisteredWhenAnonymousUsersCanAccessThePlatform()
    {
        $this->setForgeToAnonymous();

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_public_member_id . '/user_groups?query=' . urlencode('{"with_system_user_groups":true}'))
        );

        $json_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $user_group_ids = [];
        foreach ($json_response as $user_group) {
            $user_group_ids[] = $user_group['id'];
        }
        self::assertContains('1', $user_group_ids); // ProjectUgroup::ANONYMOUS
        self::assertContains('2', $user_group_ids); // ProjectUgroup::REGISTERED
    }

    public function testPATCHbacklogWithoutPermission()
    {
        $response_patch = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest(
                'PUT',
                'projects/' . $this->project_private_member_id . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[7] . ',' . $this->epic_artifact_ids[5] . ',' . $this->epic_artifact_ids[6] . ']'
                )
            )
        );

        self::assertEquals(403, $response_patch->getStatusCode());
    }

    public function testPATCHbacklog()
    {
        $uri           = 'projects/' . $this->project_private_member_id . '/backlog';
        $backlog_items = json_decode(
            $this->getResponse($this->request_factory->createRequest('GET', $uri))->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $first_item  = $backlog_items[0];
        $second_item = $backlog_items[1];
        $third_item  = $backlog_items[2];
        self::assertEquals($first_item['label'], "Epic pic");
        self::assertEquals($second_item['label'], "Epic c'est tout");
        self::assertEquals($third_item['label'], "Epic epoc");

        $request_body = json_encode([
            'order' => [
                'ids'         => [$second_item['id']],
                'direction'   => 'before',
                'compared_to' => $first_item['id'],
            ],
        ]);

        $response_patch_with_rest_read_only = $this->getResponse(
            $this->request_factory->createRequest(
                'PATCH',
                'projects/' . $this->project_private_member_id . '/backlog'
            )->withBody($this->stream_factory->createStream($request_body)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertEquals(403, $response_patch_with_rest_read_only->getStatusCode());

        $response_patch = $this->getResponse(
            $this->request_factory->createRequest(
                'PATCH',
                'projects/' . $this->project_private_member_id . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    $request_body
                )
            )
        );

        self::assertEquals(200, $response_patch->getStatusCode());

        // assert that the two items are in a different order
        $modified_backlog_items = json_decode(
            $this->getResponse($this->request_factory->createRequest('GET', $uri))->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $first_modified  = $modified_backlog_items[0];
        $second_modified = $modified_backlog_items[1];
        $third_modified  = $modified_backlog_items[2];
        self::assertEquals($first_modified['label'], "Epic c'est tout");
        self::assertEquals($second_modified['label'], "Epic pic");
        self::assertEquals($third_modified['label'], "Epic epoc");

        // re-invert order of the two tasks
        $reinvert_patch = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $uri)->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$first_modified['id']],
                                'direction' => 'after',
                                'compared_to' => $second_modified['id'],
                            ],
                        ]
                    )
                )
            )
        );
        self::assertEquals(200, $reinvert_patch->getStatusCode());

        // assert that the two tasks are in the order
        $reverted_backlog_items = json_decode(
            $this->getResponse($this->request_factory->createRequest('GET', $uri))->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $first_item  = $reverted_backlog_items[0];
        $second_item = $reverted_backlog_items[1];
        $third_item  = $backlog_items[2];
        self::assertEquals($first_item['label'], "Epic pic");
        self::assertEquals($second_item['label'], "Epic c'est tout");
        self::assertEquals($third_item['label'], "Epic epoc");
    }

    public function testPATCHbacklogMoveBackAndForthInTopBacklog()
    {
        $uri           = 'projects/' . $this->project_private_member_id . '/backlog';
        $backlog_items = json_decode(
            $this->getResponse($this->request_factory->createRequest('GET', $uri))->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $first_item  = $backlog_items[0];
        $second_item = $backlog_items[1];
        $third_item  = $backlog_items[2];

        $releases      = json_decode(
            $this->getResponse(
                $this->request_factory->createRequest(
                    'GET',
                    'projects/' . $this->project_private_member_id . '/milestones'
                )
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $first_release = $releases[0];

        $release_content = json_decode(
            $this->getResponse(
                $this->request_factory->createRequest('GET', 'milestones/' . $first_release['id'] . '/content')
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $first_epic      = $release_content[0];
        $second_epic     = $release_content[1];

        // remove from release, back to top backlog
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PATCH',
                'projects/' . $this->project_private_member_id . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$first_epic['id']],
                                'direction' => 'after',
                                'compared_to' => $first_item['id'],
                            ],
                            'add' => [
                                [
                                    'id' => $first_epic['id'],
                                    'remove_from' => $first_release['id'],
                                ],
                            ],
                        ]
                    )
                )
            )
        );
        self::assertEquals(200, $response->getStatusCode());

        self::assertEquals(
            [
                $first_item['id'],
                $first_epic['id'],
                $second_item['id'],
                $third_item['id'],
            ],
            $this->getIdsOrderedByPriority($uri)
        );

        // Move back to release
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'milestones/' . $first_release['id'] . '/content')->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'order' => [
                                'ids' => [$first_epic['id']],
                                'direction' => 'before',
                                'compared_to' => $second_epic['id'],
                            ],
                            'add' => [
                                [
                                    'id' => $first_epic['id'],
                                ],
                            ],
                        ]
                    )
                )
            )
        );
        self::assertEquals(200, $response->getStatusCode());

        // Assert Everything is equal to the beginning of the test
        self::assertEquals(
            [
                $first_item['id'],
                $second_item['id'],
                $third_item['id'],
            ],
            $this->getIdsOrderedByPriority($uri)
        );

        self::assertEquals(
            $this->getIds($release_content),
            $this->getIdsOrderedByPriority('milestones/' . $first_release['id'] . '/content')
        );
    }

    private function getIdsOrderedByPriority($uri)
    {
        return $this->getIds(
            json_decode(
                $this->getResponse($this->request_factory->createRequest('GET', $uri))->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            )
        );
    }

    public function testOPTIONSWiki()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/phpwiki'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETWiki()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/phpwiki'));

        $expected_result = [
            'pages' => [
                0 => [
                    'id'  => REST_TestDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki/6097',
                    'name' => 'WithContent',
                ],
                1 => [
                    'id'  => REST_TestDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki/6100',
                    'name' => 'With Space',
                ],
            ],
        ];

        self::assertEquals($expected_result, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGETWikiWithGoodPagename()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/phpwiki?pagename=WithContent'));

        $expected_result = [
            'pages' => [
                0 => [
                    'id'  => REST_TestDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki/6097',
                    'name' => 'WithContent',
                ],
            ],
        ];

        self::assertEquals($expected_result, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGETWikiWithGoodPagenameAndASpace()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/phpwiki?pagename=With+Space'));

        $expected_result = [
            'pages' => [
                0 => [
                    'id'  => REST_TestDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki/6100',
                    'name' => 'With Space',
                ],
            ],
        ];

        self::assertEquals($expected_result, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGETWikiWithGoodRelativePagename()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/phpwiki?pagename=With'));

        $expected_result = [
            'pages' => [
                0 => [
                    'id'  => REST_TestDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki/6097',
                    'name' => 'WithContent',
                ],
                1 => [
                    'id'  => REST_TestDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki/6100',
                    'name' => 'With Space',
                ],
            ],
        ];

        self::assertEqualsCanonicalizing($expected_result, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGETWikiWithNotExistingPagename()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/phpwiki?pagename="no"'));

        $expected_result = [
            'pages' => [],
        ];

        self::assertEquals($expected_result, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGETWithBasicAuth()
    {
        $response      = $this->getBasicAuthResponse($this->request_factory->createRequest('GET', 'projects'));
        $json_projects = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue(
            $this->valuesArePresent(
                [
                    $this->project_private_member_id,
                    $this->project_public_id,
                    $this->project_public_member_id,
                    $this->project_pbi_id,
                ],
                $this->getIds($json_projects)
            )
        );
    }

    public function testPATCHWithRegularUser()
    {
        $patch_resource = json_encode([
            'status' => 'suspended',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('PATCH', 'projects/' . $this->project_pbi_id)->withBody(
                $this->stream_factory->createStream($patch_resource)
            )
        );
        self::assertEquals(403, $response->getStatusCode());
    }

    public function testPATCHWithAdmin()
    {
        $patch_resource = json_encode([
            'status' => 'suspended',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'projects/' . $this->project_pbi_id)->withBody(
                $this->stream_factory->createStream($patch_resource)
            )
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPATCHWithRestProjectManager()
    {
        $patch_resource = json_encode([
            'status' => 'active',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME,
            $this->request_factory->createRequest('PATCH', 'projects/' . $this->project_pbi_id)->withBody(
                $this->stream_factory->createStream($patch_resource)
            )
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPATCHWithAdminWhenProjectIsDeleted()
    {
        $patch_resource = json_encode([
            'status' => 'active',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'projects/' . $this->project_deleted_id)->withBody(
                $this->stream_factory->createStream($patch_resource)
            )
        );

        self::assertEquals(403, $response->getStatusCode());
    }

    public function testPATCHWithAdminToSwitchBackProjectToPending(): void
    {
        $patch_resource = json_encode([
            'status' => 'pending',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'projects/' . $this->project_deleted_id)->withBody(
                $this->stream_factory->createStream($patch_resource)
            )
        );

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testPATCHWithAdminToSDeleteProjectIsNotAllowed(): void
    {
        $patch_resource = json_encode([
            'status' => 'deleted',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'projects/' . $this->project_deleted_id)->withBody(
                $this->stream_factory->createStream($patch_resource)
            )
        );

        self::assertEquals(400, $response->getStatusCode());
    }

    public function getSuspendedProjectTrackersWithRegularUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_suspended_id . '/trackers')
        );
        self::assertEquals(403, $response->getStatusCode());
        self::assertStringContainsString('This project is suspended', $response->getBody()->getContents());
    }

    public function getSuspendedProjectTrackersWithSiteAdmin()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_suspended_id . '/trackers')
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPUTBanner(): void
    {
        $payload = json_encode([
            'message' => 'a banner message',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'PUT',
                'projects/' . $this->project_public_member_id . '/banner'
            )->withBody(
                $this->stream_factory->createStream(
                    $payload
                )
            )
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPUTEmptyMessageBannerShouldReturn400(): void
    {
        $payload = json_encode([
            'message' => '',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'PUT',
                'projects/' . $this->project_public_member_id . '/banner'
            )->withBody(
                $this->stream_factory->createStream(
                    $payload
                )
            )
        );

        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGETBanner
     */
    public function testDELETEBanner(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'DELETE',
                'projects/' . $this->project_public_member_id . '/banner'
            )
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTBanner
     */
    public function testGETBanner(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'projects/' . $this->project_public_member_id . '/banner'
            )
        );

        self::assertEquals(200, $response->getStatusCode());

        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals('a banner message', $response_json['message']);
    }

    public function testPUTHeaderBackground(): void
    {
        $payload = json_encode(['identifier' => 'beach-daytime'], JSON_THROW_ON_ERROR);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'PUT',
                'projects/' . $this->project_public_member_id . '/header_background'
            )->withBody(
                $this->stream_factory->createStream($payload)
            )
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testDELETEHeaderBackground(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'DELETE',
                'projects/' . $this->project_public_member_id . '/header_background'
            )
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGETThirdPartiesIntegrationData(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest(
                'GET',
                'projects/' . $this->project_public_member_id . '/3rd_party_integration_data'
            )
        );

        self::assertEquals(200, $response->getStatusCode());

        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($response_json['styles']['content']);
        self::assertJson($response_json['project_sidebar']['config']);
    }
}
