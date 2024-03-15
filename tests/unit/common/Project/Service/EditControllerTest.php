<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

final class EditControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ServicePOSTDataBuilder
     */
    private mixed $data_builder;
    /**
     * @var \CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->data_builder = $this->createMock(ServicePOSTDataBuilder::class);
        $this->csrf_token   = $this->createMock(\CSRFSynchronizerToken::class);
        $this->csrf_token->method('check');
    }

    public function testItRedirectsWhenProjectIsNotFound(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(120)->build();

        $current_user = UserTestBuilder::anActiveUser()->withId(101)->withAdministratorOf($project)->build();
        $request      = HTTPRequestBuilder::get()->withUser($current_user)->build();

        $inspector = new LayoutInspector();
        $response  = LayoutBuilder::buildWithInspector($inspector);

        $this->expectException(NotFoundException::class);

        $controller = new EditController(
            ProjectByIDFactoryStub::buildWith($project),
            new ProjectAdministratorChecker(),
            $this->createMock(ServiceUpdator::class),
            $this->data_builder,
            $this->createMock(\ServiceManager::class),
            $this->csrf_token,
            new \EventManager(),
        );
        $controller->process($request, $response, ['project_id' => '102']);
    }

    public function testItRedirectsWhenServiceDataIsInvalid(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $current_user = UserTestBuilder::anActiveUser()->withId(101)->withAdministratorOf($project)->build();
        $request      = HTTPRequestBuilder::get()->withUser($current_user)->build();

        $service_manager = $this->createMock(\ServiceManager::class);
        $service_manager->method('getListOfAllowedServicesForProject')->willReturn([]);

        $inspector = new LayoutInspector();
        $response  = LayoutBuilder::buildWithInspector($inspector);
        $this->data_builder->method('buildFromRequest')
            ->with($request, $project, null, $response)
            ->willThrowException(new InvalidServicePOSTDataException());

        $controller          = new EditController(
            ProjectByIDFactoryStub::buildWith($project),
            new ProjectAdministratorChecker(),
            $this->createMock(ServiceUpdator::class),
            $this->data_builder,
            $service_manager,
            $this->csrf_token,
            new \EventManager(),
        );
        $has_been_redirected = false;
        try {
            $controller->process($request, $response, ['project_id' => '102']);
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertEquals('error', $inspector->getFeedback()[0]['level']);
        self::assertTrue($has_been_redirected);
    }

    public function testItCreatesANewSystemService(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn('120');
        $project->method('getMinimalRank')->willReturn(1);
        $project->method('isError')->willReturn(false);
        $project->method('isDeleted')->willReturn(false);
        $project->method('getStatus')->willReturn(\Project::STATUS_ACTIVE);

        $current_user    = UserTestBuilder::anActiveUser()->withId(101)->withAdministratorOf($project)->build();
        $request_builder = HTTPRequestBuilder::get()->withUser($current_user);

        $inspector = new LayoutInspector();
        $response  = LayoutBuilder::buildWithInspector($inspector);

        $service_manager = $this->createMock(\ServiceManager::class);
        $service_manager->method('getListOfAllowedServicesForProject')->willReturn([]);

        $service_to_activate = new \Service(
            $project,
            [
                'short_name' => 'plugin_stuff',
            ]
        );

        $event_manager = new \EventManager();
        $event_manager->addClosureOnEvent(AddMissingService::NAME, fn (AddMissingService $event) => $event->addService($service_to_activate));

        $service_updator = $this->createMock(ServiceUpdator::class);
        $service_updator->expects($this->once())->method('addSystemService')->with($project, $service_to_activate);

        $controller = new EditController(
            ProjectByIDFactoryStub::buildWith($project),
            new ProjectAdministratorChecker(),
            $service_updator,
            new ServicePOSTDataBuilder(
                new ServiceLinkDataBuilder(),
            ),
            $service_manager,
            $this->csrf_token,
            $event_manager,
        );

        $request_builder->withParams([
            'service_id' => '-1',
            'short_name' => 'plugin_stuff',
            'is_used'  => '1',
            'is_active' => '1',
            'label' => 'Stuff',
            'rank' => 500,
        ]);

        $has_been_redirected = false;
        try {
            $controller->process(
                $request_builder->build(),
                $response,
                [
                    'project_id' => '120',
                ]
            );
        } catch (LayoutInspectorRedirection $exception) {
            $has_been_redirected = true;
        }

        self::assertEquals('info', $inspector->getFeedback()[0]['level']);
        self::assertTrue($has_been_redirected);
    }
}
