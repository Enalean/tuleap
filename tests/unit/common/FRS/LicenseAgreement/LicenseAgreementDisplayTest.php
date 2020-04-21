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

use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TemplateRendererFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Templating\Mustache\MustacheEngine;

class LicenseAgreementDisplayTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var LicenseAgreementDisplay
     */
    private $display;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MustacheEngine
     */
    private $renderer;
    /**
     * @var \Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->project    = new \Project(['group_id' => '101']);
        $this->factory    = Mockery::mock(LicenseAgreementFactory::class);
        $this->renderer   = Mockery::mock(MustacheEngine::class);
        $renderer_factory = Mockery::mock(TemplateRendererFactory::class);
        $renderer_factory->shouldReceive('getRenderer')->andReturn($this->renderer);
        $this->display = new LicenseAgreementDisplay(
            \Codendi_HTMLPurifier::instance(),
            $renderer_factory,
            $this->factory,
        );
    }

    public function testDoNotShowSelectWhenAgreementIsMandatoryAndNoCustomLicenseExistsInProject(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        $this->factory->shouldReceive('getLicenseAgreementForPackage')->once()->andReturns(new DefaultLicenseAgreement());
        $this->factory->shouldReceive('getProjectLicenseAgreements')->once()->with($this->project)->andReturns([]);

        $package = new \FRSPackage(['package_id' => '470']);
        $this->assertEquals('<input type="hidden" name="package[approve_license]" value="1">', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testDoNotShowNoLicenseWhenLicenseIsMandatoryAtPlatformLevelAndCustomLicenseExists(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        $this->renderer->shouldReceive('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', true),
                new LicenseOptionPresenter(5, 'Some custom stuff', false),
            ]
        )->once()->andReturn('foobar');

        $package = new \FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(true);
        $this->factory->shouldReceive('getLicenseAgreementForPackage')->once()->andReturns(new DefaultLicenseAgreement());
        $this->factory->shouldReceive('getProjectLicenseAgreements')->once()->with($this->project)->andReturns([
            new LicenseAgreement(5, 'Some custom stuff', 'bla')
        ]);
        $this->assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItRendersWhenThereIsNoCustomLicenseAgreement(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);
        $this->renderer->shouldReceive('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', true),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', false),
            ]
        )->once()->andReturn('foobar');

        $package = new \FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(false);
        $this->factory->shouldReceive('getLicenseAgreementForPackage')->once()->andReturns(new NoLicenseToApprove());
        $this->factory->shouldReceive('getProjectLicenseAgreements')->once()->with($this->project)->andReturns([]);
        $this->assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItRendersWithCustomLicenseAgreementAndNoLicenseForPackage(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);
        $this->renderer->shouldReceive('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', true),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', false),
                new LicenseOptionPresenter(5, 'Some custom stuff', false),
            ]
        )->once()->andReturn('foobar');

        $package = new \FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(true);
        $this->factory->shouldReceive('getProjectLicenseAgreements')->once()->with($this->project)->andReturns([
            new LicenseAgreement(5, 'Some custom stuff', 'bla')
        ]);
        $this->factory->shouldReceive('getLicenseAgreementForPackage')->once()->andReturns(new NoLicenseToApprove());
        $this->assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItRendersWithCustomLicenseAgreementAndDefaultLicenseForPackage(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);
        $this->renderer->shouldReceive('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', false),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', true),
                new LicenseOptionPresenter(5, 'Some custom stuff', false),
            ]
        )->once()->andReturn('foobar');

        $package = new \FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(true);
        $this->factory->shouldReceive('getProjectLicenseAgreements')->once()->with($this->project)->andReturns([
            new LicenseAgreement(5, 'Some custom stuff', 'bla')
        ]);
        $this->factory->shouldReceive('getLicenseAgreementForPackage')->once()->andReturns(new DefaultLicenseAgreement());
        $this->assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItRendersWithCustomLicenseAgreementSelected(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);
        $this->renderer->shouldReceive('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', false),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', false),
                new LicenseOptionPresenter(5, 'Some custom stuff', true),
            ]
        )->once()->andReturn('foobar');

        $package = new \FRSPackage(['package_id' => '470']);
        $package->setApproveLicense(true);
        $custom_agreement = new LicenseAgreement(5, 'Some custom stuff', 'bla');
        $this->factory->shouldReceive('getProjectLicenseAgreements')->once()->with($this->project)->andReturns([
            $custom_agreement,
        ]);
        $this->factory->shouldReceive('getLicenseAgreementForPackage')->once()->andReturns($custom_agreement);
        $this->assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }

    public function testItUsesTheSelectedDefaultAtPackageCreation(): void
    {
        $this->renderer->shouldReceive('renderToString')->with(
            'edit-package',
            [
                new LicenseOptionPresenter(-1, 'No', false),
                new LicenseOptionPresenter(0, 'Code eXchange Corporate Policy', false),
                new LicenseOptionPresenter(5, 'Some custom stuff', true),
            ]
        )->once()->andReturn('foobar');

        $package = new \FRSPackage();
        $custom_agreement = new LicenseAgreement(5, 'Some custom stuff', 'bla');
        $this->factory->shouldReceive('getProjectLicenseAgreements')->once()->with($this->project)->andReturns([
            $custom_agreement,
        ]);
        $this->factory->shouldReceive('getDefaultLicenseAgreementForProject')->andReturns($custom_agreement);
        $this->assertSame('foobar', $this->display->getPackageEditSelector($package, $this->project));
    }
}
