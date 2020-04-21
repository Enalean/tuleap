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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\InvalidLicenseAgreementException;
use Tuleap\FRS\LicenseAgreement\LicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\NoLicenseToApprove;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ProjectRetriever;

final class SetDefaultLicenseAgreementControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var \HTTPRequest
     */
    private $request;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LicenseAgreementControllersHelper
     */
    private $helper;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var \CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $csrf_token;
    /**
     * @var SetDefaultLicenseAgreementController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BaseLayout
     */
    private $layout;

    protected function setUp(): void
    {
        $this->current_user = new \PFUser(['language_id' => 'en_US']);

        $this->request = new \HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->layout = Mockery::mock(BaseLayout::class);

        $this->project           = Mockery::mock(\Project::class, ['getID' => '101']);
        $this->project_retriever = Mockery::mock(ProjectRetriever::class);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($this->project);

        $this->factory = Mockery::mock(LicenseAgreementFactory::class);

        $this->csrf_token = Mockery::mock(\CSRFSynchronizerToken::class);

        $this->helper = \Mockery::mock(LicenseAgreementControllersHelper::class);

        $this->controller = new SetDefaultLicenseAgreementController(
            $this->project_retriever,
            $this->helper,
            $this->factory,
            $this->csrf_token,
        );
    }

    public function testItSavesTheNewDefaultTemplateAsCustom(): void
    {
        $this->request->set('default_agreement', '5');

        $this->helper->shouldReceive('assertCanAccess')->with($this->project, $this->current_user)->once();

        $this->csrf_token->shouldReceive('check')->once();

        $custom_agreement = new LicenseAgreement(5, 'some title', 'some content');
        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 5)->andReturn(
            $custom_agreement
        );

        $this->factory->shouldReceive('setProjectDefault')->with($this->project, $custom_agreement)->once();

        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItSavesTheNewDefaultTemplateAsNoLicense(): void
    {
        $this->request->set('default_agreement', (string) NoLicenseToApprove::ID);

        $this->helper->shouldReceive('assertCanAccess');
        $this->csrf_token->shouldReceive('check');

        $license = new NoLicenseToApprove();
        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, NoLicenseToApprove::ID)->andReturn($license);

        $this->factory->shouldReceive('setProjectDefault')->with($this->project, $license)->once();

        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItSavesTheNewDefaultTemplateAsDefaultLicense(): void
    {
        $this->request->set('default_agreement', (string) DefaultLicenseAgreement::ID);

        $this->helper->shouldReceive('assertCanAccess');
        $this->csrf_token->shouldReceive('check');

        $license = new DefaultLicenseAgreement();
        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, DefaultLicenseAgreement::ID)->andReturn($license);

        $this->factory->shouldReceive('setProjectDefault')->with($this->project, $license)->once();

        $this->layout->shouldReceive('redirect')->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItRaisesAnErrorIfNoDefaultAgreement(): void
    {
        $this->helper->shouldReceive('assertCanAccess');
        $this->csrf_token->shouldReceive('check');

        $this->expectException(InvalidLicenseAgreementException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItRaisesAnErrorIfDefaultAgreementIsNotValidForProject(): void
    {
        $this->request->set('default_agreement', '6');

        $this->helper->shouldReceive('assertCanAccess');
        $this->csrf_token->shouldReceive('check');

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 6)->andReturnNull();

        $this->expectException(InvalidLicenseAgreementException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }
}
