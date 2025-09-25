<?php
/**
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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

namespace Tuleap\FRS\Tests\REST\Packages;

use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\REST\RestBase;

/**
 * @group FRSTests
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PackagesTest extends RestBase
{
    public const string PROJECT_NAME = 'frs-test';

    private int $project_id;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->project_id = $this->getProjectId(self::PROJECT_NAME);
    }

    public function testPackagesIsInProjectResources(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', "projects/$this->project_id"));

        $this->assertPackageIsInProject($response);
    }

    public function testPackagesIsInProjectResourcesWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "projects/$this->project_id"),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertPackageIsInProject($response);
    }

    private function assertPackageIsInProject(\Psr\Http\Message\ResponseInterface $response): void
    {
        $project = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertContains(
            [
                'type' => 'frs_packages',
                'uri' => sprintf('projects/%d/frs_packages', $this->project_id),
            ],
            $project['resources']
        );
    }

    public function testOPTIONS(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'frs_packages'));
        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'frs_packages'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETPackage(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'frs_packages/1'));

        $this->assertGETPackage($response);
    }

    public function testGETPackageWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'frs_packages/1'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETPackage($response);
    }

    private function assertGETPackage(\Psr\Http\Message\ResponseInterface $response): void
    {
        $package = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(1, $package['id']);
        $this->assertEquals('package1', $package['label']);

        $this->assertCount(1, $package['permissions_for_groups']['can_read']);
        $this->assertEquals('project_members', $package['permissions_for_groups']['can_read'][0]['short_name']);
    }

    public function testGETReleasePackage(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'frs_packages/1/frs_release'));

        $this->assertGETReleasePackage($response);
    }

    public function testGETReleasePackageWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'frs_packages/1/frs_release'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETReleasePackage($response);
    }

    private function assertGETReleasePackage(\Psr\Http\Message\ResponseInterface $response): void
    {
        $package = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(1, $package['collection'][0]['id']);
        $this->assertEquals('release1', $package['collection'][0]['name']);
    }

    public function testGETPackageWithoutFRSAdminPermissions(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'frs_packages/1'), RESTTestDataBuilder::TEST_USER_5_NAME);
        $package  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($package['id'], 1);
        $this->assertNull($package['permissions_for_groups']);
    }

    public function testPOSTPackages(): void
    {
        $post_resource = json_encode([
            'project_id' => $this->project_id,
            'label' => 'New package',
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'frs_packages')->withBody($this->stream_factory->createStream($post_resource)));
        $package  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($package['id'], 2);
        $this->assertEquals($package['label'], 'New package');
    }

    public function testPOSTPackagesWithUserRESTReadOnlyAdmin(): void
    {
        $post_resource = json_encode([
            'project_id' => $this->project_id,
            'label' => 'New package',
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'frs_packages')->withBody($this->stream_factory->createStream($post_resource)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}
