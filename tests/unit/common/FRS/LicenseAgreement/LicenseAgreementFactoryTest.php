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
use FRSPackage;
use FRSPackageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

class LicenseAgreementFactoryTest extends TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var MockObject&LicenseAgreementDao
     */
    private $dao;
    private LicenseAgreementFactory $factory;
    private Project $project;
    private FRSPackage $package;

    protected function setUp(): void
    {
        $this->dao     = $this->createMock(LicenseAgreementDao::class);
        $this->project = new Project(['group_id' => '101']);
        $this->package = new FRSPackage(['package_id' => '470']);
        $this->factory = new LicenseAgreementFactory($this->dao);
    }

    public function testGetLicenseAgreementOnNonExistingPackageShouldRaiseAnException(): void
    {
        self::expectException(InvalidLicenseAgreementException::class);

        $this->factory->getLicenseAgreementForPackage(new FRSPackage([]));
    }

    public function testItUpdatePackageWithNoLicenseAgreement(): void
    {
        $this->dao->expects(self::once())->method('resetLicenseAgreementForPackage')->with($this->package);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, -1);
        self::assertFalse($this->package->getApproveLicense());
    }

    public function testItCannotDisableLicenseApprovalWhenPlatformMandatesOne(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        self::expectException(InvalidLicenseAgreementException::class);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, -1);
    }

    public function testItUpdatesPackageWithACustomLicenseAgreement(): void
    {
        $this->dao->expects(self::once())->method('isLicenseAgreementValidForProject')->with($this->project, 5)->willReturn(true);
        $this->dao->expects(self::once())->method('saveLicenseAgreementForPackage')->with($this->package, 5);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, 5);
        self::assertTrue($this->package->getApproveLicense());
    }

    public function testItUpdatesPackageWithDefaultLicenseAgreement(): void
    {
        $this->dao->expects(self::once())->method('resetLicenseAgreementForPackage')->with($this->package);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, 0);
        self::assertTrue($this->package->getApproveLicense());
    }

    public function testItRaisesAnExceptionIfSubmittedLicenseIdIsNotValidForProject(): void
    {
        $this->dao->expects(self::once())->method('isLicenseAgreementValidForProject')->with($this->project, 5)->willReturn(false);

        self::expectException(InvalidLicenseAgreementException::class);

        $this->factory->updateLicenseAgreementForPackage($this->project, $this->package, 5);
    }

    public function testItReturnsSiteDefaultAgreementWhenAgreementMandatoryAndNoDefaultSet(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($this->project)->willReturn(false);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        self::assertEquals(new DefaultLicenseAgreement(), $license);
    }

    public function testItReturnsNoLicenseAgreementWhenAgreementNotMandatoryAndNoDefaultSet(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', false);

        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($this->project)->willReturn(false);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        self::assertEquals(new NoLicenseToApprove(), $license);
    }

    public function testItReturnsCustomLicenseAsDefault(): void
    {
        $this->dao->method('getById')->willReturn(['id' => 5, 'title' => 'foo', 'content' => 'bar']);
        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($this->project)->willReturn(5);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        self::assertEquals(new LicenseAgreement(5, 'foo', 'bar'), $license);
    }

    public function testItReturnsDefaultLicenseAgreementIfALicenseWasSetButInvalid(): void
    {
        $this->dao->method('getById')->willReturn(false);
        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($this->project)->willReturn(5);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        self::assertEquals(new DefaultLicenseAgreement(), $license);
    }

    public function testItReturnsNoLicenseToApproveWhenItsTheSelectedDefault(): void
    {
        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($this->project)->willReturn(NoLicenseToApprove::ID);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        self::assertEquals(new NoLicenseToApprove(), $license);
    }

    public function testItReturnsDefaultLicenseWhenItsTheSelectedDefault(): void
    {
        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($this->project)->willReturn(DefaultLicenseAgreement::ID);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        self::assertEquals(new DefaultLicenseAgreement(), $license);
    }

    public function testItReturnsDefaultLicenseWhenTheSelectedDefaultIsNoLicenseButLicenseMandatory(): void
    {
        ForgeConfig::set('sys_frs_license_mandatory', true);

        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($this->project)->willReturn(NoLicenseToApprove::ID);

        $license = $this->factory->getDefaultLicenseAgreementForProject($this->project);
        self::assertEquals(new DefaultLicenseAgreement(), $license);
    }

    public function testItDeletesACustomLicenseAgreement(): void
    {
        $license = new LicenseAgreement(5, 'title', 'content');

        $this->dao->expects(self::once())->method('delete')->with($license);
        $this->dao->method('canBeDeleted')->with($this->project, $license)->willReturn(true);

        $this->factory->delete($this->project, $license);
    }

    public function testItThrowsAnExceptionWhenTryingToDeleteCustomLicenseThatIsUsed(): void
    {
        $license = new LicenseAgreement(5, 'title', 'content');

        $this->dao->method('canBeDeleted')->with($this->project, $license)->willReturn(false);

        $this->dao->expects(self::never())->method('delete');

        self::expectException(InvalidLicenseAgreementException::class);

        $this->factory->delete($this->project, $license);
    }

    public function testItThrowsAnExceptionWhenTryingToDeleteNoLicenseApproval(): void
    {
        $license = new NoLicenseToApprove();

        $this->dao->expects(self::never())->method('delete');

        self::expectException(InvalidLicenseAgreementException::class);

        $this->factory->delete($this->project, $license);
    }

    public function testItThrowsAnExceptionWhenTryingToDeleteDefaultLicense(): void
    {
        $license = new DefaultLicenseAgreement();

        $this->dao->expects(self::never())->method('delete');

        self::expectException(InvalidLicenseAgreementException::class);

        $this->factory->delete($this->project, $license);
    }

    public function testItDuplicatesLicenseAgreementsFromTemplateWithoutAgreements(): void
    {
        $template_project = new Project(['group_id' => 150]);

        $this->dao->expects(self::once())->method('getProjectLicenseAgreements')->with($template_project)->willReturn([]);
        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($template_project)->willReturn(false);

        $this->dao->expects(self::never())->method('save');

        $this->factory->duplicate($this->createMock(FRSPackageFactory::class), $this->project, $template_project, []);
    }

    public function testItDuplicatesLicenseAgreementsFromTemplateWithAgreementsAndDefault(): void
    {
        $template_project = new Project(['group_id' => 150]);

        $this->dao->method('getProjectLicenseAgreements')->with($template_project)->willReturn(
            [
                ['id' => 5, 'title' => 'some title', 'content' => 'and content'],
            ]
        );
        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($template_project)->willReturn(5);

        $this->dao->expects(self::once())->method('create')
            ->with(
                $this->project,
                self::callback(
                    function (NewLicenseAgreement $agreement) {
                        return $agreement->getTitle() === 'some title' &&
                            $agreement->getContent() === 'and content';
                    }
                )
            )
            ->willReturn(12);

        $this->dao->expects(self::once())->method('setProjectDefault')
            ->with(
                $this->project,
                self::callback(
                    function (LicenseAgreement $agreement) {
                        return $agreement->getId() === 12;
                    }
                )
            );

        $this->factory->duplicate($this->createMock(FRSPackageFactory::class), $this->project, $template_project, []);
    }

    public function testItDuplicatesLicenseAgreementsFromTemplateWithAgreementsAndDefaultTemplateSiteLicenseAgreement(): void
    {
        $template_project = new Project(['group_id' => 150]);

        $this->dao->method('getProjectLicenseAgreements')->with($template_project)->willReturn(
            [
                ['id' => 5, 'title' => 'some title', 'content' => 'and content'],
            ]
        );
        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($template_project)->willReturn(NoLicenseToApprove::ID);

        $this->dao->expects(self::once())->method('create')
            ->with(
                $this->project,
                self::callback(
                    function (NewLicenseAgreement $agreement) {
                        return $agreement->getTitle() === 'some title' &&
                            $agreement->getContent() === 'and content';
                    }
                )
            )
            ->willReturn(12);

        $this->dao->expects(self::once())->method('setProjectDefault')
            ->with(
                $this->project,
                self::callback(
                    function (LicenseAgreementInterface $agreement) {
                        return $agreement instanceof NoLicenseToApprove;
                    }
                )
            );

        $this->factory->duplicate($this->createMock(FRSPackageFactory::class), $this->project, $template_project, []);
    }

    public function testItDuplicatesTheLicensesAssociatedToPackages(): void
    {
        $template_project = new Project(['group_id' => 150]);

        $this->dao->method('getProjectLicenseAgreements')->with($template_project)->willReturn(
            [
                ['id' => 5, 'title' => 'some title', 'content' => 'and content'],
            ]
        );
        $this->dao->expects(self::once())->method('getDefaultLicenseIdForProject')->with($template_project)->willReturn(5);
        $this->dao->method('create')->willReturn(12);
        $this->dao->method('setProjectDefault');

        $frs_package_factory = $this->createMock(FRSPackageFactory::class);
        $packages            = [];
        $package_ids         = [350, 1001, 470, 1002];
        foreach ($package_ids as $package_id) {
            $packages[$package_id] = new FRSPackage(['package_id' => (string) $package_id, 'approve_license' => '1']);
        }
        $frs_package_factory->method('getFRSPackageFromDb')->withConsecutive(...array_map(
            static fn(int $package_id) => [$package_id],
            $package_ids
        ))->willReturnOnConsecutiveCalls(...$packages);

        $this->dao->method('getLicenseAgreementForPackage')->withConsecutive(
            [$packages[350]],
            [$packages[470]]
        )->willReturnOnConsecutiveCalls(
            ['id' => 5, 'title' => 'some title', 'content' => 'and content'],
            null
        );

        $this->dao->method('isLicenseAgreementValidForProject')->with($this->project, 12)->willReturn(true);
        $this->dao->expects(self::once())->method('saveLicenseAgreementForPackage')->with($packages[1001], 12);
        $this->dao->expects(self::once())->method('resetLicenseAgreementForPackage')->with($packages[1002]);

        $this->factory->duplicate($frs_package_factory, $this->project, $template_project, [350 => 1001, 470 => 1002]);
    }
}
