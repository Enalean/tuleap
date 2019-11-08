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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

class LicenseAgreementFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LicenseAgreementDao
     */
    private $dao;
    /**
     * @var LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var \Project
     */
    private $project;
    private $package;

    protected function setUp(): void
    {
        $this->dao = \Mockery::mock(LicenseAgreementDao::class);
        $this->project = new \Project(['group_id' => '101']);
        $this->package = new \FRSPackage(['package_id' => '470']);
        $this->factory = new LicenseAgreementFactory($this->dao);
    }

    public function testGetLicenseAgreementOnNonExistingPackageShouldRaiseAnException()
    {
        $this->expectException(InvalidLicenseAgreementException::class);

        $this->factory->getLicenseAgreementForPackage(new \FRSPackage([]));
    }


    public function testItUpdatePackageWithNoLicenseAgreement(): void
    {
        $this->dao->shouldReceive('resetLicenseAgreementForPackage')->once()->with($this->package);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, -1);
        $this->assertFalse($this->package->getApproveLicense());
    }

    public function testItCannotDisableLicenseApprovalWhenPlatformMandatesOne(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        $this->expectException(InvalidLicenseAgreementException::class);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, -1);
    }

    public function testItUpdatesPackageWithACustomLicenseAgreement(): void
    {
        $this->dao->shouldReceive('isLicenseAgreementValidForProject')->once()->with($this->project, 5)->andReturnTrue();
        $this->dao->shouldReceive('saveLicenseAgreementForPackage')->once()->with($this->package, 5);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, 5);
        $this->assertTrue($this->package->getApproveLicense());
    }

    public function testItUpdatesPackageWithDefaultLicenseAgreement(): void
    {
        $this->dao->shouldReceive('resetLicenseAgreementForPackage')->once()->with($this->package);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, 0);
        $this->assertTrue($this->package->getApproveLicense());
    }

    public function testItRaisesAnExceptionIfSubmittedLicenseIdIsNotValidForProject()
    {
        $this->dao->shouldReceive('isLicenseAgreementValidForProject')->once()->with($this->project, 5)->andReturnFalse();

        $this->expectException(InvalidLicenseAgreementException::class);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, 5);
    }

    public function testItReturnsSiteDefaultAgreementWhenAgreementMandatoryAndNoDefaultSet()
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        $this->dao->shouldReceive('getDefaultLicenseIdForProject')->once()->with($this->project)->andReturnFalse();

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        $this->assertEquals(new DefaultLicenseAgreement(), $license);
    }

    public function testItReturnsNoLicenseAgreementWhenAgreementNotMandatoryAndNoDefaultSet()
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);

        $this->dao->shouldReceive('getDefaultLicenseIdForProject')->once()->with($this->project)->andReturnFalse();

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        $this->assertEquals(new NoLicenseToApprove(), $license);
    }

    public function testItReturnsCustomLicenseAsDefault()
    {
        $this->dao->shouldReceive('getById')->andReturn(['id' => 5, 'title' => 'foo', 'content' => 'bar']);
        $this->dao->shouldReceive('getDefaultLicenseIdForProject')->once()->with($this->project)->andReturns(5);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        $this->assertEquals(new LicenseAgreement(5, 'foo', 'bar'), $license);
    }

    public function testItReturnsDefaultLicenseAgreementIfALicenseWasSetButInvalid()
    {
        $this->dao->shouldReceive('getById')->andReturnFalse();
        $this->dao->shouldReceive('getDefaultLicenseIdForProject')->once()->with($this->project)->andReturns(5);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        $this->assertEquals(new DefaultLicenseAgreement(), $license);
    }

    public function testItReturnsNoLicenseToApproveWhenItsTheSelectedDefault()
    {
        $this->dao->shouldReceive('getDefaultLicenseIdForProject')->once()->with($this->project)->andReturns(NoLicenseToApprove::ID);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        $this->assertEquals(new NoLicenseToApprove(), $license);
    }

    public function testItReturnsDefaultLicenseWhenItsTheSelectedDefault()
    {
        $this->dao->shouldReceive('getDefaultLicenseIdForProject')->once()->with($this->project)->andReturns(DefaultLicenseAgreement::ID);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        $this->assertEquals(new DefaultLicenseAgreement(), $license);
    }

    public function testItReturnsDefaultLicenseWhenTheSelectedDefaultIsNoLicenseButLicenseMandatory()
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        $this->dao->shouldReceive('getDefaultLicenseIdForProject')->once()->with($this->project)->andReturns(NoLicenseToApprove::ID);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        $this->assertEquals(new DefaultLicenseAgreement(), $license);
    }
}
