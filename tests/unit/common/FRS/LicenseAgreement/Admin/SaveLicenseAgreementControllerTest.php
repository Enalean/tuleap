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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

final class SaveLicenseAgreementControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var SaveLicenseAgreementController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $project_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ServiceFile
     */
    private $service_file;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FRSPermissionManager
     */
    private $permissions_manager;
    /**
     * @var \HTTPRequest
     */
    private $request;
    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var \CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $csrf_token;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LicenseAgreementControllersHelper
     */
    private $helper;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BaseLayout
     */
    private $layout;

    protected function setUp(): void
    {
        $this->layout = Mockery::mock(BaseLayout::class);

        $this->current_user = new \PFUser(['language_id' => 'en_US']);

        $this->request = new \HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->service_file = Mockery::mock(\ServiceFile::class, ['displayFRSHeader' => 'foo']);
        $this->project      = Mockery::mock(\Project::class, ['getID' => '101']);
        $this->project->shouldReceive('getFileService')->andReturn($this->service_file)->byDefault();
        $this->project_retriever = Mockery::mock(ProjectRetriever::class)
            ->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($this->project)
            ->getMock();

        $this->permissions_manager = Mockery::mock(FRSPermissionManager::class);
        $this->permissions_manager->shouldReceive('isAdmin')->with($this->project, $this->current_user)->andReturnTrue(
        )->byDefault();

        $this->factory = Mockery::mock(LicenseAgreementFactory::class);

        $this->csrf_token = Mockery::mock(\CSRFSynchronizerToken::class);
        $this->csrf_token->shouldReceive('check');

        $this->helper = Mockery::mock(LicenseAgreementControllersHelper::class);
        $this->helper->shouldReceive('assertCanAccess')->with($this->project, $this->current_user);

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

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 1)->andReturn(new LicenseAgreement(1, 'some title', 'some content'));

        $this->factory->shouldReceive('save')->with($this->project, Mockery::on(function (LicenseAgreement $agreement) {
            return $agreement->getId() === 1 &&
                $agreement->getTitle() === 'updated title' &&
                $agreement->getContent() === 'updated content';
        }))->once();

        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItSavesNewLicenseAgreement(): void
    {
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->shouldReceive('save')->with($this->project, Mockery::on(function (LicenseAgreementInterface $agreement) {
            return $agreement->getTitle() === 'updated title' &&
                $agreement->getContent() === 'updated content';
        }))->once();

        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }


    public function testItAbortsWhenGivenLicenseIdIsNotValid(): void
    {
        $this->request->set('id', '1');
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 1)->andReturnNull();

        $this->factory->shouldNotReceive('save');

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, Mockery::mock(BaseLayout::class), ['project_id' => '101']);
    }

    public function testItAbortsWhenLicenseIsSiteDefault(): void
    {
        $this->request->set('id', '0');
        $this->request->set('title', 'updated title');
        $this->request->set('content', 'updated content');
        $this->request->set('save', '');

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 0)->andReturn(new DefaultLicenseAgreement());

        $this->factory->shouldNotReceive('save');

        $this->expectException(ForbiddenException::class);

        $this->controller->process($this->request, Mockery::mock(BaseLayout::class), ['project_id' => '101']);
    }

    public function testItDeletesAnUnusedCustomLicenseAgreement(): void
    {
        $this->request->set('id', '1');
        $this->request->set('delete', '');

        $license = new LicenseAgreement(1, 'title', 'content');
        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 1)->andReturn($license);
        $this->factory->shouldReceive('delete')->with($this->project, $license)->once();

        $this->layout->shouldReceive('redirect')->once();
        $this->layout->shouldReceive('addFeedback')->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }


    public function testItRaisesAnExceptionWhenTryingToDeleteUnknownCustomLicense(): void
    {
        $this->request->set('id', '1');
        $this->request->set('delete', '');

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 1)->andReturnNull();

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }


    public function testItRaisesAnExceptionWhenTryingToDeleteWithoutId(): void
    {
        $this->request->set('id', '');
        $this->request->set('delete', '');

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }
}
