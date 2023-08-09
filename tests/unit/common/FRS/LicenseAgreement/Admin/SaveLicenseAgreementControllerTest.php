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

use CSRFSynchronizerToken;
use HTTPRequest;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
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
use Tuleap\Test\PHPUnit\TestCase;

final class SaveLicenseAgreementControllerTest extends TestCase
{
    private SaveLicenseAgreementController $controller;
    /**
     * @var MockObject&ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var MockObject&Project
     */
    private $project;
    /**
     * @var MockObject&ServiceFile
     */
    private $service_file;
    /**
     * @var MockObject&FRSPermissionManager
     */
    private $permissions_manager;
    private HTTPRequest $request;
    private PFUser $current_user;
    /**
     * @var MockObject&LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var MockObject&CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var MockObject&LicenseAgreementControllersHelper
     */
    private $helper;
    /**
     * @var MockObject&BaseLayout
     */
    private $layout;

    protected function setUp(): void
    {
        $this->layout = $this->createMock(BaseLayout::class);

        $this->current_user = new PFUser(['language_id' => 'en_US']);

        $this->request = new HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->service_file = $this->createConfiguredMock(ServiceFile::class, ['displayFRSHeader' => 'foo']);
        $this->project      = $this->createConfiguredMock(Project::class, ['getID' => '101']);
        $this->project->method('getFileService')->willReturn($this->service_file);
        $this->project_retriever = $this->createMock(ProjectRetriever::class);
        $this->project_retriever->expects(self::once())->method('getProjectFromId')
            ->with('101')
            ->willReturn($this->project);

        $this->permissions_manager = $this->createMock(FRSPermissionManager::class);
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->current_user)->willReturn(true);

        $this->factory = $this->createMock(LicenseAgreementFactory::class);

        $this->csrf_token = $this->createMock(CSRFSynchronizerToken::class);
        $this->csrf_token->method('check');

        $this->helper = $this->createMock(LicenseAgreementControllersHelper::class);
        $this->helper->method('assertCanAccess')->with($this->project, $this->current_user);

        $this->controller = new SaveLicenseAgreementController(
            $this->project_retriever,
            $this->helper,
            $this->factory,
            $this->csrf_token,
        );
    }

    public function testItSavesExistingLicenseAgreement(): void
    {
        $this->request->set('id', '1');
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->method('getLicenseAgreementById')->with($this->project, 1)->willReturn(new LicenseAgreement(1, 'some title', 'some content'));

        $this->factory->expects(self::once())->method('save')->with($this->project, self::callback(function (LicenseAgreement $agreement) {
            return $agreement->getId() === 1 &&
                $agreement->getTitle() === 'updated title' &&
                $agreement->getContent() === 'updated content';
        }));

        $this->layout->expects(self::once())->method('redirect');

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItSavesNewLicenseAgreement(): void
    {
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->expects(self::once())->method('save')->with($this->project, self::callback(function (LicenseAgreementInterface $agreement) {
            return $agreement->getTitle() === 'updated title' &&
                $agreement->getContent() === 'updated content';
        }));

        $this->layout->expects(self::once())->method('redirect');

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItAbortsWhenGivenLicenseIdIsNotValid(): void
    {
        $this->request->set('id', '1');
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->method('getLicenseAgreementById')->with($this->project, 1)->willReturn(null);

        $this->factory->expects(self::never())->method('save');

        self::expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->createMock(BaseLayout::class), ['project_id' => '101']);
    }

    public function testItAbortsWhenLicenseIsSiteDefault(): void
    {
        $this->request->set('id', '0');
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->method('getLicenseAgreementById')->with($this->project, 0)->willReturn(new DefaultLicenseAgreement());

        $this->factory->expects(self::never())->method('save');

        self::expectException(ForbiddenException::class);

        $this->controller->process($this->request, $this->createMock(BaseLayout::class), ['project_id' => '101']);
    }

    public function testItDeletesAnUnusedCustomLicenseAgreement(): void
    {
        $this->request->set('id', '1');
        $this->request->set('delete', '');

        $license = new LicenseAgreement(1, 'title', 'content');
        $this->factory->method('getLicenseAgreementById')->with($this->project, 1)->willReturn($license);
        $this->factory->expects(self::once())->method('delete')->with($this->project, $license);

        $this->layout->expects(self::once())->method('redirect');
        $this->layout->expects(self::once())->method('addFeedback');

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItRaisesAnExceptionWhenTryingToDeleteUnknownCustomLicense(): void
    {
        $this->request->set('id', '1');
        $this->request->set('delete', '');

        $this->factory->method('getLicenseAgreementById')->with($this->project, 1)->willReturn(null);

        self::expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItRaisesAnExceptionWhenTryingToDeleteWithoutId(): void
    {
        $this->request->set('id', '');
        $this->request->set('delete', '');

        self::expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }
}
