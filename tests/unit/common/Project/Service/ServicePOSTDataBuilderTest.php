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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ServicePOSTDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private ServicePOSTDataBuilder $service_postdata_builder;

    #[\Override]
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

        $this->expectException(InvalidServicePOSTDataException::class);

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

        $this->expectException(InvalidServicePOSTDataException::class);

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

        $this->expectException(InvalidServicePOSTDataException::class);

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

        $this->expectException(InvalidServicePOSTDataException::class);

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

        $this->expectException(InvalidServicePOSTDataException::class);

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

        $this->expectException(InvalidServicePOSTDataException::class);

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
        $matcher  = self::exactly(8);
        $request
            ->expects($matcher)
            ->method('getValidated')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('service_id', $parameters[0]);
                    return 12;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('short_name', $parameters[0]);
                    return 'admin';
                }
                if ($matcher->numberOfInvocations() === 3) {
                    self::assertSame('label', $parameters[0]);
                    return 'My custom service';
                }
                if ($matcher->numberOfInvocations() === 4) {
                    self::assertSame('icon_name', $parameters[0]);
                    return 'fa-invalid-icon-name';
                }
                if ($matcher->numberOfInvocations() === 5) {
                    self::assertSame('description', $parameters[0]);
                    return '';
                }
                if ($matcher->numberOfInvocations() === 6) {
                    self::assertSame('rank', $parameters[0]);
                    return 1230;
                }
                if ($matcher->numberOfInvocations() === 7) {
                    self::assertSame('is_active', $parameters[0]);
                    return 1;
                }
                if ($matcher->numberOfInvocations() === 8) {
                    self::assertSame('link', $parameters[0]);
                    return 'https://example.com/custom';
                }
            });
        $request
            ->expects($this->once())
            ->method('exist')
            ->with('short_name')
            ->willReturn(true);
        $matcher = self::exactly(2);
        $request
            ->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('is_in_iframe', $parameters[0]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('is_in_new_tab', $parameters[0]);
                }
                return false;
            });

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
        $matcher  = self::exactly(9);
        $request
            ->expects($matcher)
            ->method('getValidated')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('service_id', $parameters[0]);
                    return 12;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('short_name', $parameters[0]);
                    return '';
                }
                if ($matcher->numberOfInvocations() === 3) {
                    self::assertSame('label', $parameters[0]);
                    return 'My custom service';
                }
                if ($matcher->numberOfInvocations() === 4) {
                    self::assertSame('icon_name', $parameters[0]);
                    return 'fa-invalid-icon-name';
                }
                if ($matcher->numberOfInvocations() === 5) {
                    self::assertSame('description', $parameters[0]);
                    return '';
                }
                if ($matcher->numberOfInvocations() === 6) {
                    self::assertSame('rank', $parameters[0]);
                    return 123;
                }
                if ($matcher->numberOfInvocations() === 7) {
                    self::assertSame('is_active', $parameters[0]);
                    return 1;
                }
                if ($matcher->numberOfInvocations() === 8) {
                    self::assertSame('is_used', $parameters[0]);
                    return true;
                }
                if ($matcher->numberOfInvocations() === 9) {
                    self::assertSame('link', $parameters[0]);
                    return 'https://example.com/custom';
                }
            });
        $request
            ->expects($this->once())
            ->method('exist')
            ->with('short_name')
            ->willReturn(false);
        $matcher = self::exactly(2);
        $request
            ->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('is_in_iframe', $parameters[0]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('is_in_new_tab', $parameters[0]);
                }
                return false;
            });

        $this->expectException(InvalidServicePOSTDataException::class);

        $this->service_postdata_builder->buildFromRequest($request, $project, $service, $response);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideLabelAndDescription')]
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
        $matcher  = $this->exactly(9);
        $request->expects($matcher)->method('getValidated')->willReturnCallback(function (...$parameters) use ($matcher, $submitted_label, $submitted_description) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('service_id', $parameters[0]);
                return 12;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('short_name', $parameters[0]);
                return '';
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame('label', $parameters[0]);
                return $submitted_label;
            }
            if ($matcher->numberOfInvocations() === 4) {
                self::assertSame('icon_name', $parameters[0]);
                return 'fa-bolt';
            }
            if ($matcher->numberOfInvocations() === 5) {
                self::assertSame('description', $parameters[0]);
                return $submitted_description;
            }
            if ($matcher->numberOfInvocations() === 6) {
                self::assertSame('rank', $parameters[0]);
                return 123;
            }
            if ($matcher->numberOfInvocations() === 7) {
                self::assertSame('is_active', $parameters[0]);
                return 1;
            }
            if ($matcher->numberOfInvocations() === 8) {
                self::assertSame('is_used', $parameters[0]);
                return true;
            }
            if ($matcher->numberOfInvocations() === 9) {
                self::assertSame('link', $parameters[0]);
                return 'https://example.com/custom';
            }
        });
        $request->method('exist')
            ->with('short_name')
            ->willReturn(false);
        $matcher = $this->exactly(2);
        $request->expects($matcher)->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('is_in_iframe', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('is_in_new_tab', $parameters[0]);
            }
            return false;
        });

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
