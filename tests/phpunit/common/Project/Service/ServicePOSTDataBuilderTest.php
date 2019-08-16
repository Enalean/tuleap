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
use PHPUnit\Framework\TestCase;
use Project;
use Service;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\ServiceUrlCollector;

final class ServicePOSTDataBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock, ForgeConfigSandbox;

    /**
     * @var ServicePOSTDataBuilder
     */
    private $service_postdata_builder;
    /**
     * @var \EventManager|M\MockInterface
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->event_manager            = M::mock(\EventManager::class);
        $this->service_postdata_builder = new ServicePOSTDataBuilder(
            $this->event_manager
        );
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
                'is_active'     => true
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
                'is_active'     => true
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
                'is_active'     => true
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
                'is_active'     => true
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
                'is_active'     => true
            ]
        );
        $this->event_manager->shouldReceive('processEvent')
            ->with(
                M::on(
                    function (ServiceUrlCollector $event) {
                        $event->setUrl('https://example.com/custom');
                        return true;
                    }
                )
            )->once();

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
                'is_active'     => true
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
                'is_active'     => true
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
                'is_active'     => true
            ]
        );

        $this->event_manager->shouldReceive('processEvent')
            ->with(
                M::on(
                    function (ServiceUrlCollector $event) {
                        $event->setUrl('https://example.com/news');
                        return true;
                    }
                )
            )->once();

        $post_data = $this->service_postdata_builder->buildFromService($service, false);

        $this->assertSame($post_data->getIconName(), 'fa-rss');
    }

    public function testBuildFromRequestThrowsWhenIconIsInvalid(): void
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

        $this->service_postdata_builder->buildFromRequest($request, $project, $response);
    }
}
