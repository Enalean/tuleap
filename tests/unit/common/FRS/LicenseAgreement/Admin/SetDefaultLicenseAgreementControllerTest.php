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
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\InvalidLicenseAgreementException;
use Tuleap\FRS\LicenseAgreement\LicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\NoLicenseToApprove;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\PHPUnit\TestCase;

final class SetDefaultLicenseAgreementControllerTest extends TestCase
{
    private PFUser $current_user;
    private HTTPRequest $request;
    /**
     * @var MockObject&LicenseAgreementControllersHelper
     */
    private $helper;
    /**
     * @var MockObject&ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var MockObject&Project
     */
    private $project;
    /**
     * @var MockObject&LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var MockObject&CSRFSynchronizerToken
     */
    private $csrf_token;
    private SetDefaultLicenseAgreementController $controller;
    /**
     * @var MockObject&BaseLayout
     */
    private $layout;

    protected function setUp(): void
    {
        $this->current_user = new PFUser(['language_id' => 'en_US']);

        $this->request = new HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->layout = $this->createMock(BaseLayout::class);

        $this->project           = $this->createConfiguredMock(Project::class, ['getID' => '101']);
        $this->project_retriever = $this->createMock(ProjectRetriever::class);
        $this->project_retriever->expects(self::once())->method('getProjectFromId')
            ->with('101')
            ->willReturn($this->project);

        $this->factory = $this->createMock(LicenseAgreementFactory::class);

        $this->csrf_token = $this->createMock(CSRFSynchronizerToken::class);

        $this->helper = $this->createMock(LicenseAgreementControllersHelper::class);

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

        $this->helper->expects(self::once())->method('assertCanAccess')->with($this->project, $this->current_user);

        $this->csrf_token->expects(self::once())->method('check');

        $custom_agreement = new LicenseAgreement(5, 'some title', 'some content');
        $this->factory->method('getLicenseAgreementById')->with($this->project, 5)->willReturn($custom_agreement);

        $this->factory->expects(self::once())->method('setProjectDefault')->with($this->project, $custom_agreement);

        $this->layout->expects(self::once())->method('redirect');

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItSavesTheNewDefaultTemplateAsNoLicense(): void
    {
        $this->request->set('default_agreement', (string) NoLicenseToApprove::ID);

        $this->helper->method('assertCanAccess');
        $this->csrf_token->method('check');

        $license = new NoLicenseToApprove();
        $this->factory->method('getLicenseAgreementById')->with($this->project, NoLicenseToApprove::ID)->willReturn($license);

        $this->factory->expects(self::once())->method('setProjectDefault')->with($this->project, $license);

        $this->layout->expects(self::once())->method('redirect');

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItSavesTheNewDefaultTemplateAsDefaultLicense(): void
    {
        $this->request->set('default_agreement', (string) DefaultLicenseAgreement::ID);

        $this->helper->method('assertCanAccess');
        $this->csrf_token->method('check');

        $license = new DefaultLicenseAgreement();
        $this->factory->method('getLicenseAgreementById')->with($this->project, DefaultLicenseAgreement::ID)->willReturn($license);

        $this->factory->expects(self::once())->method('setProjectDefault')->with($this->project, $license);

        $this->layout->expects(self::once())->method('redirect');

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItRaisesAnErrorIfNoDefaultAgreement(): void
    {
        $this->helper->method('assertCanAccess');
        $this->csrf_token->method('check');

        self::expectException(InvalidLicenseAgreementException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }

    public function testItRaisesAnErrorIfDefaultAgreementIsNotValidForProject(): void
    {
        $this->request->set('default_agreement', '6');

        $this->helper->method('assertCanAccess');
        $this->csrf_token->method('check');

        $this->factory->method('getLicenseAgreementById')->with($this->project, 6)->willReturn(null);

        self::expectException(InvalidLicenseAgreementException::class);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }
}
