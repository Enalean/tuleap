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

namespace Tuleap\FRS\LicenseAgreement;

use Codendi_HTMLPurifier;
use ForgeConfig;
use FRSPackage;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

class LicenseAgreementDisplayTest extends TestCase
{
    use ForgeConfigSandbox;

    private LicenseAgreementDisplay $display;
    /**
     * @var MockObject&LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var MockObject&TemplateRenderer
     */
    private $renderer;
    private Project $project;

    protected function setUp(): void
    {
        $this->project    = new Project(['group_id' => '101']);
        $this->factory    = $this->createMock(LicenseAgreementFactory::class);
        $this->renderer   = $this->createMock(TemplateRenderer::class);
        $renderer_factory = $this->createMock(TemplateRendererFactory::class);
        $renderer_factory->method('getRenderer')->willReturn($this->renderer);
        $this->display = new LicenseAgreementDisplay(
            Codendi_HTMLPurifier::instance(),
            $renderer_factory,
            $this->factory,
        );
    }

    public function testDoNotShowSelectWhenAgreementIsMandatoryAndNoCustomLicenseExistsInProject(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        $this->factory->expects(self::once())->method('getLicenseAgreementForPackage')->willReturn(new DefaultLicenseAgreement());
        $this->factory->expects(self::once())->method('getProjectLicenseAgreements')->with($this->project)->willReturn([]);

        $package = new FRSPackage(['package_id' => '470']);
        self::assertEquals('<input type="hidden" name="package[approve_license]" value="1">', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testDoNotShowNoLicenseWhenLicenseIsMandatoryAtPlatformLevelAndCustomLicenseExists(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        $this->renderer->expects(self::once())->method('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', true),
                new LicenseOptionPresenter(5, 'Some custom stuff', false),
            ]
        )->willReturn('foobar');

        $package = new FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(true);
        $this->factory->expects(self::once())->method('getLicenseAgreementForPackage')->willReturn(new DefaultLicenseAgreement());
        $this->factory->expects(self::once())->method('getProjectLicenseAgreements')->with($this->project)->willReturn([
            new LicenseAgreement(5, 'Some custom stuff', 'bla'),
        ]);
        self::assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItRendersWhenThereIsNoCustomLicenseAgreement(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);
        $this->renderer->expects(self::once())->method('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', true),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', false),
            ]
        )->willReturn('foobar');

        $package = new FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(false);
        $this->factory->method('getLicenseAgreementForPackage')->willReturn(new NoLicenseToApprove());
        $this->factory->method('getProjectLicenseAgreements')->with($this->project)->willReturn([]);
        self::assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItRendersWithCustomLicenseAgreementAndNoLicenseForPackage(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);
        $this->renderer->expects(self::once())->method('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', true),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', false),
                new LicenseOptionPresenter(5, 'Some custom stuff', false),
            ]
        )->willReturn('foobar');

        $package = new FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(true);
        $this->factory->expects(self::once())->method('getProjectLicenseAgreements')->with($this->project)->willReturn([
            new LicenseAgreement(5, 'Some custom stuff', 'bla'),
        ]);
        $this->factory->expects(self::once())->method('getLicenseAgreementForPackage')->willReturn(new NoLicenseToApprove());
        self::assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItRendersWithCustomLicenseAgreementAndDefaultLicenseForPackage(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);
        $this->renderer->expects(self::once())->method('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', false),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', true),
                new LicenseOptionPresenter(5, 'Some custom stuff', false),
            ]
        )->willReturn('foobar');

        $package = new FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(true);
        $this->factory->expects(self::once())->method('getProjectLicenseAgreements')->with($this->project)->willReturn([
            new LicenseAgreement(5, 'Some custom stuff', 'bla'),
        ]);
        $this->factory->expects(self::once())->method('getLicenseAgreementForPackage')->willReturn(new DefaultLicenseAgreement());
        self::assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItRendersWithCustomLicenseAgreementSelected(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);
        $this->renderer->expects(self::once())->method('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', false),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', false),
                new LicenseOptionPresenter(5, 'Some custom stuff', true),
            ]
        )->willReturn('foobar');

        $package = new FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(true);
        $custom_agreement = new LicenseAgreement(5, 'Some custom stuff', 'bla');
        $this->factory->expects(self::once())->method('getProjectLicenseAgreements')->with($this->project)->willReturn([
            $custom_agreement,
        ]);
        $this->factory->expects(self::once())->method('getLicenseAgreementForPackage')->willReturn($custom_agreement);
        self::assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItUsesTheSelectedDefaultAtPackageCreation(): void
    {
        $this->renderer->expects(self::once())->method('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', false),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', false),
                new LicenseOptionPresenter(5, 'Some custom stuff', true),
            ]
        )->willReturn('foobar');

        $package          = new FRSPackage();
        $custom_agreement = new LicenseAgreement(5, 'Some custom stuff', 'bla');
        $this->factory->expects(self::once())->method('getProjectLicenseAgreements')->with($this->project)->willReturn([
            $custom_agreement,
        ]);
        $this->factory->method('getDefaultLicenseAgreementForProject')->willReturn($custom_agreement);
        $this->assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }
}
