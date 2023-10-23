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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Service;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;

final class ServicePOSTDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
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
        $project = M::mock(Project::class, ['getID' => 100]);
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

        $this->expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromService($service, false);
    }

    public function testBuildFromServiceThrowsWhenNoLabel(): void
    {
        $project = M::mock(Project::class, ['getID' => 105]);
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

        $this->expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromService($service, false);
    }

    public function testBuildFromServiceThrowsWhenNoRank(): void
    {
        $project = M::mock(Project::class, ['getID' => 105, 'getMinimalRank' => 10]);
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

        $this->expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromService($service, false);
    }

    public function testBuildFromServiceThrowsWhenRankBelowMinimalRank(): void
    {
        $project = M::mock(Project::class, ['getID' => 105, 'getMinimalRank' => 10]);
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

        $this->expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromService($service, false);
    }

    public function testBuildFromServiceDoesntCheckIconWhenScopeIsSystem(): void
    {
        $project = M::mock(Project::class, ['getID' => 105, 'getMinimalRank' => 10]);
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

        $post_data = $this->service_postdata_builder->buildFromService($service, false);

        $this->assertSame($post_data->getId(), 12);
    }

    public function testBuildFromServiceThrowsWhenIconIsMissing(): void
    {
        $project = M::mock(Project::class, ['getID' => 105, 'getMinimalRank' => 10]);
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

        $this->expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromService($service, false);
    }

    public function testBuildFromServiceThrowsWhenBothOpenInIframeAndInNewTab(): void
    {
        $project = M::mock(Project::class, ['getID' => 105, 'getMinimalRank' => 10]);
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

        $this->expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromService($service, false);
    }

    public function testBuildFromServiceSucceeds(): void
    {
        $project = M::mock(Project::class, ['getID' => 105, 'getMinimalRank' => 10]);
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

        $post_data = $this->service_postdata_builder->buildFromService($service, false);

        $this->assertSame('fas fa-rss', $post_data->getIconName());
    }

    public function testBuildFromRequestForceAdminServiceToBeUsed(): void
    {
        $project  = M::mock(Project::class, ['getID' => 105, 'getMinimalRank' => 10]);
        $response = M::mock(BaseLayout::class);
        $request  = M::mock(\HTTPRequest::class);
        $request->shouldReceive('getValidated')
            ->with('service_id', M::any(), M::any())
            ->once()
            ->andReturn(12);
        $request->shouldReceive('getValidated')
            ->with('short_name', M::any(), M::any())
            ->once()
            ->andReturn('admin');
        $request->shouldReceive('exist')
            ->with('short_name')
            ->once()
            ->andReturnTrue();
        $request->shouldReceive('getValidated')
            ->with('label', M::any(), M::any())
            ->once()
            ->andReturn('My custom service');
        $request->shouldReceive('getValidated')
            ->with('icon_name', M::any(), M::any())
            ->once()
            ->andReturn('fa-invalid-icon-name');
        $request->shouldReceive('getValidated')
            ->with('description', M::any(), M::any())
            ->once()
            ->andReturn('');
        $request->shouldReceive('getValidated')
            ->with('rank', M::any(), M::any())
            ->once()
            ->andReturn(1230);
        $request->shouldReceive('getValidated')
            ->with('is_active', M::any(), M::any())
            ->once()
            ->andReturn(1);
        $request->shouldReceive('get')
            ->with('is_in_iframe')
            ->once()
            ->andReturnFalse();
        $request->shouldReceive('get')
            ->with('is_in_new_tab')
            ->once()
            ->andReturnFalse();
        $request->shouldReceive('getValidated')
            ->with('link', M::any(), M::any())
            ->andReturn('https://example.com/custom');

        $current_admin_service = new Service($project, ['label' => 'Admin', 'short_name' => Service::ADMIN, 'description' => 'admin']);

        $admin_service = $this->service_postdata_builder->buildFromRequest($request, $project, $current_admin_service, $response);

        $this->assertTrue($admin_service->isUsed());
    }

    public function testBuildFromRequestThrowsWhenIconIsInvalid(): void
    {
        $project  = M::mock(Project::class, ['getID' => 105, 'getMinimalRank' => 10]);
        $service  = new Service($project, ['label' => 'foo', 'short_name' => 'bar', 'description' => 'baz']);
        $response = M::mock(BaseLayout::class);
        $request  = M::mock(\HTTPRequest::class);
        $request->shouldReceive('getValidated')
            ->with('service_id', M::any(), M::any())
            ->once()
            ->andReturn(12);
        $request->shouldReceive('getValidated')
            ->with('short_name', M::any(), M::any())
            ->once()
            ->andReturn('');
        $request->shouldReceive('exist')
            ->with('short_name')
            ->once()
            ->andReturnFalse();
        $request->shouldReceive('getValidated')
            ->with('label', M::any(), M::any())
            ->once()
            ->andReturn('My custom service');
        $request->shouldReceive('getValidated')
            ->with('icon_name', M::any(), M::any())
            ->once()
            ->andReturn('fa-invalid-icon-name');
        $request->shouldReceive('getValidated')
            ->with('description', M::any(), M::any())
            ->once()
            ->andReturn('');
        $request->shouldReceive('getValidated')
            ->with('rank', M::any(), M::any())
            ->once()
            ->andReturn(123);
        $request->shouldReceive('getValidated')
            ->with('is_active', M::any(), M::any())
            ->once()
            ->andReturn(1);
        $request->shouldReceive('getValidated')
            ->with('is_used', M::any(), M::any())
            ->once()
            ->andReturn(true);
        $request->shouldReceive('get')
            ->with('is_in_iframe')
            ->once()
            ->andReturnFalse();
        $request->shouldReceive('get')
            ->with('is_in_new_tab')
            ->once()
            ->andReturnFalse();
        $request->shouldReceive('getValidated')
            ->with('link', M::any(), M::any())
            ->andReturn('https://example.com/custom');

        $this->expectException(InvalidServicePOSTDataException::class);

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
        $service = M::mock(Service::class);
        $service->shouldReceive(
            [
                'getInternationalizedName' => 'SVN',
                'getLabel' => 'plugin_svn:service_lbl_key',
                'getInternationalizedDescription' => 'SVN plugin to manage multiple SVN repositories',
                'getDescription' => 'plugin_svn:service_lbl_description',
                'urlCanChange' => true,
            ]
        );
        $project = M::mock(Project::class, ['getID' => 105, 'getMinimalRank' => 10]);

        $response = M::mock(BaseLayout::class);
        $request  = M::mock(\HTTPRequest::class);
        $request->shouldReceive('getValidated')
            ->with('service_id', M::any(), M::any())
            ->andReturn(12);
        $request->shouldReceive('getValidated')
            ->with('short_name', M::any(), M::any())
            ->andReturn('');
        $request->shouldReceive('exist')
            ->with('short_name')
            ->andReturnFalse();
        $request->shouldReceive('getValidated')
            ->with('label', M::any(), M::any())
            ->andReturn($submitted_label);
        $request->shouldReceive('getValidated')
            ->with('icon_name', M::any(), M::any())
            ->andReturn('fa-bolt');
        $request->shouldReceive('getValidated')
            ->with('description', M::any(), M::any())
            ->andReturn($submitted_description);
        $request->shouldReceive('getValidated')
            ->with('rank', M::any(), M::any())
            ->andReturn(123);
        $request->shouldReceive('getValidated')
            ->with('is_active', M::any(), M::any())
            ->andReturn(1);
        $request->shouldReceive('getValidated')
            ->with('is_used', M::any(), M::any())
            ->andReturn(true);
        $request->shouldReceive('get')
            ->with('is_in_iframe')
            ->andReturn(false);
        $request->shouldReceive('get')
            ->with('is_in_new_tab')
            ->andReturnFalse();
        $request->shouldReceive('getValidated')
            ->with('link', M::any(), M::any())
            ->andReturn('https://example.com/custom');

        $service = $this->service_postdata_builder->buildFromRequest($request, $project, $service, $response);

        $this->assertEquals($expected_label, $service->getLabel());
        $this->assertEquals($expected_description, $service->getDescription());
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
            'customised label' => [
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
