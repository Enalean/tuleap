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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS\LicenseAgreement\Admin;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Project;
use ServiceFile;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SaveLicenseAgreementControllerTest extends TestCase
{
    private SaveLicenseAgreementController $controller;
    private ProjectRetriever&MockObject $project_retriever;
    private Project&Stub $project;
    private ServiceFile&Stub $service_file;
    private FRSPermissionManager&Stub $permissions_manager;
    private \Tuleap\HTTPRequest $request;
    private PFUser $current_user;
    private LicenseAgreementFactory&MockObject $factory;
    private LicenseAgreementControllersHelper&Stub $helper;
    private TestLayout $layout;

    #[\Override]
    protected function setUp(): void
    {
        $this->layout = new TestLayout(new LayoutInspector());

        $this->current_user = new PFUser(['language_id' => 'en_US']);

        $this->request = new \Tuleap\HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->service_file = $this->createStub(\ServiceFile::class);
        $this->project      = $this->createConfiguredStub(Project::class, ['getID' => '101']);
        $this->project->method('getService')->willReturnMap([[\Service::FILE, $this->service_file]]);
        $this->project_retriever = $this->createMock(ProjectRetriever::class);
        $this->project_retriever->expects($this->once())->method('getProjectFromId')
            ->with('101')
            ->willReturn($this->project);

        $this->permissions_manager = $this->createStub(FRSPermissionManager::class);
        $this->permissions_manager->method('isAdmin')->willReturn(true);

        $this->factory = $this->createMock(LicenseAgreementFactory::class);

        $this->helper = $this->createMock(LicenseAgreementControllersHelper::class);
        $this->helper->expects($this->once())->method('assertCanAccess')->with($this->project, $this->current_user);

        $this->controller = new SaveLicenseAgreementController(
            $this->project_retriever,
            $this->helper,
            $this->factory,
            CSRFSynchronizerTokenStub::buildSelf(),
        );
    }

    public function testItSavesExistingLicenseAgreement(): void
    {
        $this->request->set('id', '1');
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->method('getLicenseAgreementById')->with($this->project, 1)->willReturn(new LicenseAgreement(1, 'some title', 'some content'));

        $this->factory->expects($this->once())->method('save')->with($this->project, self::callback(function (LicenseAgreement $agreement) {
            return $agreement->getId() === 1 &&
                $agreement->getTitle() === 'updated title' &&
                $agreement->getContent() === 'updated content';
        }));

        $this->expectException(LayoutInspectorRedirection::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItSavesNewLicenseAgreement(): void
    {
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->expects($this->once())->method('save')->with($this->project, self::callback(function (LicenseAgreementInterface $agreement) {
            return $agreement->getTitle() === 'updated title' &&
                $agreement->getContent() === 'updated content';
        }));

        $this->expectException(LayoutInspectorRedirection::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItAbortsWhenGivenLicenseIdIsNotValid(): void
    {
        $this->request->set('id', '1');
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->method('getLicenseAgreementById')->with($this->project, 1)->willReturn(null);

        $this->factory->expects($this->never())->method('save');

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->createStub(BaseLayout::class), ['project_id' => '101']);
    }

    public function testItAbortsWhenLicenseIsSiteDefault(): void
    {
        $this->request->set('id', '0');
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->method('getLicenseAgreementById')->with($this->project, 0)->willReturn(new DefaultLicenseAgreement());

        $this->factory->expects($this->never())->method('save');

        $this->expectException(ForbiddenException::class);

        $this->controller->process($this->request, $this->createStub(BaseLayout::class), ['project_id' => '101']);
    }

    public function testItDeletesAnUnusedCustomLicenseAgreement(): void
    {
        $this->request->set('id', '1');
        $this->request->set('delete', '');

        $license = new LicenseAgreement(1, 'title', 'content');
        $this->factory->method('getLicenseAgreementById')->with($this->project, 1)->willReturn($license);
        $this->factory->expects($this->once())->method('delete')->with($this->project, $license);

        $this->expectException(LayoutInspectorRedirection::class);

        try {
            $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
        } finally {
            self::assertCount(1, $this->layout->inspector->getFeedback());
        }
    }

    public function testItRaisesAnExceptionWhenTryingToDeleteUnknownCustomLicense(): void
    {
        $this->request->set('id', '1');
        $this->request->set('delete', '');

        $this->factory->expects($this->once())->method('getLicenseAgreementById')->with($this->project, 1)->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItRaisesAnExceptionWhenTryingToDeleteWithoutId(): void
    {
        $this->request->set('id', '');
        $this->request->set('delete', '');

        $this->factory->expects($this->never())->method('delete');

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }
}
