<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\MediawikiStandalone\Permissions\ISearchByProjectStub;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissionsRetriever;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class AdminPermissionsControllerTest extends TestCase
{
    use \Tuleap\TemporaryTestDirectory;

    public function testExceptionWhenProjectIsNotAllowed(): void
    {
        $dao = ISearchByProjectStub::buildWithoutSpecificPermissions();

        $controller = new AdminPermissionsController(
            \Tuleap\Http\HTTPFactoryBuilder::responseFactory(),
            \Tuleap\Http\HTTPFactoryBuilder::streamFactory(),
            \Tuleap\Plugin\IsProjectAllowedToUsePluginStub::projectIsNotAllowed(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->createMock(CSRFSynchronizerTokenProvider::class),
            new AdminPermissionsPresenterBuilder(
                new ProjectPermissionsRetriever($dao),
                $this->createStub(\User_ForgeUserGroupFactory::class),
            ),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, ProjectTestBuilder::aProject()->build())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildWithDefaults());

        $this->expectException(\Tuleap\Request\ForbiddenException::class);
        $controller->handle($request);
    }

    public function testExceptionWhenServiceIsNotActivated(): void
    {
        $dao = ISearchByProjectStub::buildWithoutSpecificPermissions();

        $controller = new AdminPermissionsController(
            \Tuleap\Http\HTTPFactoryBuilder::responseFactory(),
            \Tuleap\Http\HTTPFactoryBuilder::streamFactory(),
            \Tuleap\Plugin\IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->createMock(CSRFSynchronizerTokenProvider::class),
            new AdminPermissionsPresenterBuilder(
                new ProjectPermissionsRetriever($dao),
                $this->createStub(\User_ForgeUserGroupFactory::class),
            ),
            new NoopSapiEmitter(),
        );

        $project = ProjectTestBuilder::aProject()
            ->withoutServices()
            ->build();

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, UserTestBuilder::buildWithDefaults());

        $this->expectException(\Tuleap\Request\ForbiddenException::class);
        $controller->handle($request);
    }

    public function testHappyPath(): void
    {
        $token = CSRFSynchronizerTokenStub::buildSelf();

        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')
            ->willReturn($token);

        $user_group_factory = $this->createStub(\User_ForgeUserGroupFactory::class);
        $ugroups            = [new \User_ForgeUGroup(104, 'Lorem ipsum', '')];
        $user_group_factory->method('getAllForProjectWithoutNobody')->willReturn($ugroups);
        $user_group_factory->method('getAllForProjectWithoutNobodyNorAnonymous')->willReturn($ugroups);
        $user_group_factory->method('getProjectUGroupsWithMembersWithoutNobody')->willReturn($ugroups);

        $dao = ISearchByProjectStub::buildWithoutSpecificPermissions();

        $controller = new AdminPermissionsController(
            \Tuleap\Http\HTTPFactoryBuilder::responseFactory(),
            \Tuleap\Http\HTTPFactoryBuilder::streamFactory(),
            \Tuleap\Plugin\IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $token_provider,
            new AdminPermissionsPresenterBuilder(
                new ProjectPermissionsRetriever($dao),
                $user_group_factory,
            ),
            new NoopSapiEmitter(),
        );

        $service = $this->createMock(MediawikiStandaloneService::class);
        $service->method('displayAdministrationHeader');
        $service->method('displayFooter');

        $project = ProjectTestBuilder::aProject()->build();
        $project->addUsedServices([MediawikiStandaloneService::SERVICE_SHORTNAME, $service]);

        $request = (new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, UserTestBuilder::buildWithDefaults())
            ->withAttribute(BaseLayout::class, LayoutBuilder::build());

        $response = $controller->handle($request);
        self::assertStringContainsString(
            'Lorem ipsum',
            $response->getBody()->getContents()
        );
    }
}
