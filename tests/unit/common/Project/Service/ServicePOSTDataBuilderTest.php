<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use Project;
use Service;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class ServicePOSTDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private ServicePOSTDataBuilder $service_postdata_builder;

    protected function setUp(): void
    {
        $link_data_builder              = new ServiceLinkDataBuilder();
        $this->service_postdata_builder = new ServicePOSTDataBuilder(
            $link_data_builder
        );

        \ForgeConfig::set('sys_default_domain', 'whatever');
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testBuildFromServiceThrowsWhenTemplateProjectAndNoShortname(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(100)->build();
        $service = new Service(
            $project,
            [
                'service_id'    => 12,
                'short_name'    => '',
                'label'         => 'My system service',
                'description'   => '',
                'rank'          => 123,
                'link'          => '',
                'is_in_iframe'  => false,
                'is_in_new_tab' => false,
                'scope'         => Service::SCOPE_SYSTEM,
                'is_active'     => true,
            ]
        );

        self::expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromREST($service, false);
    }

    public function testBuildFromServiceThrowsWhenNoLabel(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(105)->build();
        $service = new Service(
            $project,
            [
                'service_id'    => 12,
                'short_name'    => '',
                'label'         => '',
                'description'   => '',
                'rank'          => 123,
                'link'          => 'https://example.com/custom',
                'is_in_iframe'  => false,
                'is_in_new_tab' => false,
                'scope'         => Service::SCOPE_PROJECT,
                'is_active'     => true,
            ]
        );

        self::expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromREST($service, false);
    }

    public function testBuildFromServiceThrowsWhenNoRank(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(105);
        $project->method('getMinimalRank')->willReturn(10);
        $service = new Service(
            $project,
            [
                'service_id'    => 12,
                'short_name'    => '',
                'label'         => 'My custom service',
                'description'   => '',
                'rank'          => 0,
                'link'          => 'https://example.com/custom',
                'is_in_iframe'  => false,
                'is_in_new_tab' => false,
                'scope'         => Service::SCOPE_PROJECT,
                'is_active'     => true,
            ]
        );

        self::expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromREST($service, false);
    }

    public function testBuildFromServiceThrowsWhenRankBelowMinimalRank(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(105);
        $project->method('getMinimalRank')->willReturn(10);
        $service = new Service(
            $project,
            [
                'service_id'    => 12,
                'short_name'    => '',
                'label'         => 'My custom service',
                'description'   => '',
                'rank'          => 5,
                'link'          => 'https://example.com/custom',
                'is_in_iframe'  => false,
                'is_in_new_tab' => false,
                'scope'         => Service::SCOPE_PROJECT,
                'is_active'     => true,
            ]
        );

        self::expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromREST($service, false);
    }

    public function testBuildFromServiceDoesntCheckIconWhenScopeIsSystem(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(105);
        $project->method('getMinimalRank')->willReturn(10);
        $service = new Service(
            $project,
            [
                'service_id'    => 12,
                'short_name'    => Service::NEWS,
                'label'         => 'News',
                'description'   => '',
                'rank'          => 123,
                'link'          => '',
                'is_in_iframe'  => false,
                'is_in_new_tab' => false,
                'scope'         => Service::SCOPE_SYSTEM,
                'is_active'     => true,
            ]
        );

        $post_data = $this->service_postdata_builder->buildFromREST($service, false);

        self::assertSame($post_data->getId(), 12);
    }

    public function testBuildFromServiceThrowsWhenIconIsMissing(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(105);
        $project->method('getMinimalRank')->willReturn(10);
        $service = new Service(
            $project,
            [
                'service_id'    => 12,
                'short_name'    => '',
                'label'         => 'My custom service',
                'description'   => '',
                'rank'          => 123,
                'link'          => 'https://example.com/custom',
                'is_in_iframe'  => false,
                'is_in_new_tab' => false,
                'scope'         => Service::SCOPE_PROJECT,
                'is_active'     => true,
            ]
        );

        self::expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromREST($service, false);
    }

    public function testBuildFromServiceThrowsWhenBothOpenInIframeAndInNewTab(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(105);
        $project->method('getMinimalRank')->willReturn(10);
        $service = new Service(
            $project,
            [
                'service_id'    => 12,
                'short_name'    => '',
                'label'         => 'My custom service',
                'description'   => '',
                'rank'          => 123,
                'link'          => 'https://example.com/custom',
                'is_in_iframe'  => true,
                'is_in_new_tab' => true,
                'scope'         => Service::SCOPE_PROJECT,
                'is_active'     => true,
            ]
        );

        self::expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromREST($service, false);
    }

    public function testBuildFromServiceSucceeds(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(105);
        $project->method('getMinimalRank')->willReturn(10);
        $service = new Service(
            $project,
            [
                'service_id'    => 12,
                'short_name'    => Service::NEWS,
                'label'         => 'News',
                'description'   => '',
                'rank'          => 123,
                'link'          => '',
                'is_in_iframe'  => false,
                'is_in_new_tab' => false,
                'scope'         => Service::SCOPE_SYSTEM,
                'is_active'     => true,
            ]
        );

        $post_data = $this->service_postdata_builder->buildFromREST($service, false);

        self::assertSame('fas fa-rss', $post_data->getIconName());
    }

    public function testBuildFromRequestForceAdminServiceToBeUsed(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(105);
        $project->method('getMinimalRank')->willReturn(10);
        $response = $this->createMock(BaseLayout::class);
        $request  = $this->createMock(\HTTPRequest::class);
        $request
            ->expects(self::exactly(8))
            ->method('getValidated')
            ->withConsecutive(
                ['service_id', self::anything(), self::anything()],
                ['short_name', self::anything(), self::anything()],
                ['label', self::anything(), self::anything()],
                ['icon_name', self::anything(), self::anything()],
                ['description', self::anything(), self::anything()],
                ['rank', self::anything(), self::anything()],
                ['is_active', self::anything(), self::anything()],
                ['link', self::anything(), self::anything()],
            )
            ->willReturnOnConsecutiveCalls(
                12,
                'admin',
                'My custom service',
                'fa-invalid-icon-name',
                '',
                1230,
                1,
                'https://example.com/custom',
            );
        $request
            ->expects(self::once())
            ->method('exist')
            ->with('short_name')
            ->willReturn(true);
        $request
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['is_in_iframe'], ['is_in_new_tab'])
            ->willReturn(false);

        $current_admin_service = new Service($project, ['label' => 'Admin', 'short_name' => Service::ADMIN, 'description' => 'admin']);

        $admin_service = $this->service_postdata_builder->buildFromRequest($request, $project, $current_admin_service, $response);

        self::assertTrue($admin_service->isUsed());
    }

    public function testBuildFromRequestThrowsWhenIconIsInvalid(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(105);
        $project->method('getMinimalRank')->willReturn(10);
        $service  = new Service($project, ['label' => 'foo', 'short_name' => 'bar', 'description' => 'baz']);
        $response = $this->createMock(BaseLayout::class);
        $request  = $this->createMock(\HTTPRequest::class);
        $request
            ->expects(self::exactly(9))
            ->method('getValidated')
            ->withConsecutive(
                ['service_id', self::anything(), self::anything()],
                ['short_name', self::anything(), self::anything()],
                ['label', self::anything(), self::anything()],
                ['icon_name', self::anything(), self::anything()],
                ['description', self::anything(), self::anything()],
                ['rank', self::anything(), self::anything()],
                ['is_active', self::anything(), self::anything()],
                ['is_used', self::anything(), self::anything()],
                ['link', self::anything(), self::anything()],
            )
            ->willReturnOnConsecutiveCalls(
                12,
                '',
                'My custom service',
                'fa-invalid-icon-name',
                '',
                123,
                1,
                true,
                'https://example.com/custom',
            );
        $request
            ->expects(self::once())
            ->method('exist')
            ->with('short_name')
            ->willReturn(false);
        $request
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['is_in_iframe'], ['is_in_new_tab'])
            ->willReturn(false);

        self::expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromRequest($request, $project, $service, $response);
    }

    /**
     * @dataProvider provideLabelAndDescription
     */
    public function testBuildFromRequestAndUseInternalLabelAndDescriptionInsteadOfInternationalizedOne(
        string $submitted_label,
        string $submitted_description,
        string $expected_label,
        string $expected_description,
    ): void {
        $service = $this->createMock(Service::class);
        $service->method('getInternationalizedName')->willReturn('SVN');
        $service->method('getLabel')->willReturn('plugin_svn:service_lbl_key');
        $service->method('getInternationalizedDescription')->willReturn('SVN plugin to manage multiple SVN repositories');
        $service->method('getDescription')->willReturn('plugin_svn:service_lbl_description');
        $service->method('urlCanChange')->willReturn(true);
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(105);
        $project->method('getMinimalRank')->willReturn(10);

        $response = $this->createMock(BaseLayout::class);
        $request  = $this->createMock(\HTTPRequest::class);
        $request->method('getValidated')
            ->withConsecutive(
                ['service_id', self::anything(), self::anything()],
                ['short_name', self::anything(), self::anything()],
                ['label', self::anything(), self::anything()],
                ['icon_name', self::anything(), self::anything()],
                ['description', self::anything(), self::anything()],
                ['rank', self::anything(), self::anything()],
                ['is_active', self::anything(), self::anything()],
                ['is_used', self::anything(), self::anything()],
                ['link', self::anything(), self::anything()],
            )
            ->willReturnOnConsecutiveCalls(
                12,
                '',
                $submitted_label,
                'fa-bolt',
                $submitted_description,
                123,
                1,
                true,
                'https://example.com/custom',
            );
        $request->method('exist')
            ->with('short_name')
            ->willReturn(false);
        $request->method('get')
            ->withConsecutive(['is_in_iframe'], ['is_in_new_tab'])
            ->willReturn(false);

        $service = $this->service_postdata_builder->buildFromRequest($request, $project, $service, $response);

        self::assertEquals($expected_label, $service->getLabel());
        self::assertEquals($expected_description, $service->getDescription());
    }

    public static function provideLabelAndDescription(): array
    {
        return [
            'unmodified label and description' => [
                'SVN',
                'SVN plugin to manage multiple SVN repositories',
                'plugin_svn:service_lbl_key',
                'plugin_svn:service_lbl_description',
            ],
            'customised label'                 => [
                'My SVN',
                'SVN plugin to manage multiple SVN repositories',
                'My SVN',
                'plugin_svn:service_lbl_description',
            ],
            'customised label and description' => [
                'My SVN',
                'My SVN description',
                'My SVN',
                'My SVN description',
            ],
        ];
    }
}
