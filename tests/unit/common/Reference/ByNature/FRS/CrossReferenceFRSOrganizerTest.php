<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Reference\ByNature\FRS;

use FRSFile;
use FRSFileFactory;
use FRSPackage;
use FRSPackageFactory;
use FRSRelease;
use FRSReleaseFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferenceFRSOrganizerTest extends TestCase
{
    private FRSPackageFactory&MockObject $package_factory;
    private FRSReleaseFactory&MockObject $release_factory;
    private CrossReferenceFRSOrganizer $organizer;
    private PFUser $user;
    private FRSFileFactory&MockObject $file_factory;

    protected function setUp(): void
    {
        $this->package_factory = $this->createMock(FRSPackageFactory::class);
        $this->release_factory = $this->createMock(FRSReleaseFactory::class);
        $this->file_factory    = $this->createMock(FRSFileFactory::class);
        $this->user            = UserTestBuilder::buildWithId(115);

        $this->organizer = new CrossReferenceFRSOrganizer(
            $this->package_factory,
            $this->release_factory,
            $this->file_factory
        );
    }

    public function testRemoveFRSReleaseCrossReferenceIfReleaseDoesNotExist(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($this->user);

        $this->release_factory->method("getFRSReleaseFromDb")->with(1)->willReturn(null);
        $by_nature_organizer->expects(self::once())->method('removeUnreadableCrossReference')->with($cross_reference);
        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveFRSReleaseCrossReferenceIfUserCanNotReadPackage(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($this->user);

        $release = $this->createMock(FRSRelease::class);
        $release->method("getPackageID")->willReturn(18);
        $release->method("isHidden")->willReturn(false);
        $package = $this->createMock(FRSPackage::class);
        $package->method('isHidden')->willReturn(false);
        $release->method("getPackage")->willReturn($package);

        $this->release_factory
            ->method("getFRSReleaseFromDb")
            ->with(1)
            ->willReturn($release);
        $this->release_factory
            ->method("userCanRead")
            ->with(101, 18, 1, 115)
            ->willReturn(true);

        $this->package_factory
            ->method("userCanRead")
            ->with(101, 18, 115)
            ->willReturn(false);

        $by_nature_organizer->expects(self::once())->method('removeUnreadableCrossReference')->with($cross_reference);
        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveFRSReleaseCrossReferenceIfUserCanNotReadRelease(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($this->user);

        $release = $this->createMock(FRSRelease::class);
        $release->method("getPackageID")->willReturn(18);
        $release->method("isHidden")->willReturn(false);
        $package = $this->createMock(FRSPackage::class);
        $package->method('isHidden')->willReturn(false);
        $release->method("getPackage")->willReturn($package);

        $this->release_factory
            ->method("getFRSReleaseFromDb")
            ->with(1)
            ->willReturn($release);
        $this->release_factory
            ->method("userCanRead")
            ->with(101, 18, 1, 115)
            ->willReturn(false);

        $this->package_factory
            ->method("userCanRead")
            ->with(101, 18, 115)
            ->willReturn(true);

        $by_nature_organizer->expects(self::once())->method('removeUnreadableCrossReference')->with($cross_reference);
        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveFRSReleaseCrossReferenceIfReleaseIsHidden(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($this->user);

        $release = $this->createMock(FRSRelease::class);
        $release->method("getPackageID")->willReturn(18);
        $release->method("isHidden")->willReturn(true);
        $package = $this->createMock(FRSPackage::class);
        $package->method('isHidden')->willReturn(false);
        $release->method("getPackage")->willReturn($package);

        $this->release_factory
            ->method("getFRSReleaseFromDb")
            ->with(1)
            ->willReturn($release);
        $this->release_factory
            ->method("userCanRead")
            ->with(101, 18, 1, 115)
            ->willReturn(true);

        $this->package_factory
            ->method("userCanRead")
            ->with(101, 18, 115)
            ->willReturn(true);

        $by_nature_organizer->expects(self::once())->method('removeUnreadableCrossReference')->with($cross_reference);
        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveFRSReleaseCrossReferenceIfPackageIsHidden(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($this->user);

        $release = $this->createMock(FRSRelease::class);
        $release->method("getPackageID")->willReturn(18);
        $release->method("isHidden")->willReturn(false);
        $package = $this->createMock(FRSPackage::class);
        $package->method('isHidden')->willReturn(true);
        $release->method("getPackage")->willReturn($package);

        $this->release_factory
            ->method("getFRSReleaseFromDb")
            ->with(1)
            ->willReturn($release);
        $this->release_factory
            ->method("userCanRead")
            ->with(101, 18, 1, 115)
            ->willReturn(true);

        $this->package_factory
            ->method("userCanRead")
            ->with(101, 18, 115)
            ->willReturn(true);

        $by_nature_organizer->expects(self::once())->method('removeUnreadableCrossReference')->with($cross_reference);
        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testMoveFRSReleaseCrossReferenceInUnlabelledSection(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($this->user);

        $release = $this->createMock(FRSRelease::class);
        $release->method("getPackageID")->willReturn(18);
        $release->method("isHidden")->willReturn(false);
        $package = $this->createMock(FRSPackage::class);
        $package->method('isHidden')->willReturn(false);
        $release->method("getPackage")->willReturn($package);

        $this->release_factory
            ->method("getFRSReleaseFromDb")
            ->with(1)
            ->willReturn($release);
        $this->release_factory
            ->method("userCanRead")
            ->with(101, 18, 1, 115)
            ->willReturn(true);

        $this->package_factory
            ->method("userCanRead")
            ->with(101, 18, 115)
            ->willReturn(true);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer
            ->expects(self::once())
            ->method('moveCrossReferenceToSection')
            ->with($cross_reference, "");

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveCrossReferenceIfFileDoesNotExist(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($this->user);

        $this->file_factory->method("getFRSFileFromDb")->with(1)->willReturn(null);
        $by_nature_organizer->expects(self::once())->method('removeUnreadableCrossReference')->with($cross_reference);
        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeFRSFileReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveCrossReferenceIfUserCanNotDownloadFile(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($this->user);

        $file = $this->createMock(FRSFile::class);
        $file->method("userCanDownload")
            ->with($this->user)
            ->willReturn(false);

        $this->file_factory->method("getFRSFileFromDb")->with(1)->willReturn($file);
        $by_nature_organizer->expects(self::once())->method('removeUnreadableCrossReference')->with($cross_reference);
        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeFRSFileReference($cross_reference, $by_nature_organizer);
    }

    public function testMoveFRSFileCrossReferenceInUnlabelledSection(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($this->user);

        $file = $this->createMock(FRSFile::class);
        $file->method("userCanDownload")
            ->with($this->user)
            ->willReturn(true);

        $this->file_factory->method("getFRSFileFromDb")->with(1)->willReturn($file);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer
            ->expects(self::once())
            ->method('moveCrossReferenceToSection')
            ->with($cross_reference, "");

        $this->organizer->organizeFRSFileReference($cross_reference, $by_nature_organizer);
    }
}
