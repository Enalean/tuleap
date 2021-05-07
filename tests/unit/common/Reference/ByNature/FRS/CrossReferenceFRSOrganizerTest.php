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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use PFUser;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class CrossReferenceFRSOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \FRSPackageFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $package_factory;
    /**
     * @var \FRSReleaseFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $release_factory;
    /**
     * @var CrossReferenceFRSOrganizer
     */
    private $organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var \FRSFileFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $file_factory;

    protected function setUp(): void
    {
        $this->package_factory = Mockery::mock(\FRSPackageFactory::class);
        $this->release_factory = Mockery::mock(\FRSReleaseFactory::class);
        $this->file_factory    = Mockery::mock(\FRSFileFactory::class);
        $this->user            = Mockery::mock(PFUser::class, ['getId' => 115]);

        $this->organizer = new CrossReferenceFRSOrganizer(
            $this->package_factory,
            $this->release_factory,
            $this->file_factory
        );
    }

    public function testRemoveFRSReleaseCrossReferenceIfReleaseDoesNotExist(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(['getCurrentUser' => $this->user])
            ->getMock();

        $this->release_factory->shouldReceive("getFRSReleaseFromDb")->with(1)->andReturnNull();
        $by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->once()->with($cross_reference);
        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveFRSReleaseCrossReferenceIfUserCanNotReadPackage(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(['getCurrentUser' => $this->user])
            ->getMock();

        $release = Mockery::mock(\FRSRelease::class)
            ->shouldReceive("getPackageID")
            ->andReturn(18)
            ->getMock();
        $release->shouldReceive("isHidden")->andReturn(false);
        $release->shouldReceive("getPackage")->andReturn(Mockery::mock(\FRSPackage::class, ["isHidden" => false]));

        $this->release_factory
            ->shouldReceive("getFRSReleaseFromDb")
            ->with(1)
            ->andReturn($release);
        $this->release_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 1, 115)
            ->andReturn(true);

        $this->package_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 115)
            ->andReturn(false);

        $by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->once()->with($cross_reference);
        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveFRSReleaseCrossReferenceIfUserCanNotReadRelease(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(['getCurrentUser' => $this->user])
            ->getMock();

        $release = Mockery::mock(\FRSRelease::class)
            ->shouldReceive("getPackageID")
            ->andReturn(18)
            ->getMock();
        $release->shouldReceive("isHidden")->andReturn(false);
        $release->shouldReceive("getPackage")->andReturn(Mockery::mock(\FRSPackage::class, ["isHidden" => false]));

        $this->release_factory
            ->shouldReceive("getFRSReleaseFromDb")
            ->with(1)
            ->andReturn($release);
        $this->release_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 1, 115)
            ->andReturn(false);

        $this->package_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 115)
            ->andReturn(true);

        $by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->once()->with($cross_reference);
        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveFRSReleaseCrossReferenceIfReleaseIsHidden(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(['getCurrentUser' => $this->user])
            ->getMock();

        $release = Mockery::mock(\FRSRelease::class)
            ->shouldReceive("getPackageID")
            ->andReturn(18)
            ->getMock();
        $release->shouldReceive("isHidden")->andReturn(true);
        $release->shouldReceive("getPackage")->andReturn(Mockery::mock(\FRSPackage::class, ["isHidden" => false]));

        $this->release_factory
            ->shouldReceive("getFRSReleaseFromDb")
            ->with(1)
            ->andReturn($release);
        $this->release_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 1, 115)
            ->andReturn(true);

        $this->package_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 115)
            ->andReturn(true);

        $by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->once()->with($cross_reference);
        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveFRSReleaseCrossReferenceIfPackageIsHidden(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(['getCurrentUser' => $this->user])
            ->getMock();

        $release = Mockery::mock(\FRSRelease::class)
            ->shouldReceive("getPackageID")
            ->andReturn(18)
            ->getMock();
        $release->shouldReceive("isHidden")->andReturn(false);
        $release->shouldReceive("getPackage")->andReturn(Mockery::mock(\FRSPackage::class, ["isHidden" => true]));

        $this->release_factory
            ->shouldReceive("getFRSReleaseFromDb")
            ->with(1)
            ->andReturn($release);
        $this->release_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 1, 115)
            ->andReturn(true);

        $this->package_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 115)
            ->andReturn(true);

        $by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->once()->with($cross_reference);
        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testMoveFRSReleaseCrossReferenceInUnlabelledSection(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(['getCurrentUser' => $this->user])
            ->getMock();

        $release = Mockery::mock(\FRSRelease::class)
            ->shouldReceive("getPackageID")
            ->andReturn(18)
            ->getMock();
        $release->shouldReceive("isHidden")->andReturn(false);
        $release->shouldReceive("getPackage")->andReturn(Mockery::mock(\FRSPackage::class, ["isHidden" => false]));

        $this->release_factory
            ->shouldReceive("getFRSReleaseFromDb")
            ->with(1)
            ->andReturn($release);
        $this->release_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 1, 115)
            ->andReturn(true);

        $this->package_factory
            ->shouldReceive("userCanRead")
            ->with(101, 18, 115)
            ->andReturn(true);

        $by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->once()
            ->with(
                $cross_reference,
                ""
            );

        $this->organizer->organizeFRSReleaseReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveCrossReferenceIfFileDoesNotExist(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(['getCurrentUser' => $this->user])
            ->getMock();

        $this->file_factory->shouldReceive("getFRSFileFromDb")->with(1)->andReturnNull();
        $by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->once()->with($cross_reference);
        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeFRSFileReference($cross_reference, $by_nature_organizer);
    }

    public function testRemoveCrossReferenceIfUserCanNotDownloadFile(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(['getCurrentUser' => $this->user])
            ->getMock();

        $file = Mockery::mock(\FRSFile::class)
            ->shouldReceive("userCanDownload")
            ->with($this->user)
            ->andReturn(false)
            ->getMock();

        $this->file_factory->shouldReceive("getFRSFileFromDb")->with(1)->andReturn($file);
        $by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->once()->with($cross_reference);
        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeFRSFileReference($cross_reference, $by_nature_organizer);
    }

    public function testMoveFRSFileCrossReferenceInUnlabelledSection(): void
    {
        $cross_reference = CrossReferencePresenterBuilder::get(1)->withType('release')->withValue("1")->withProjectId(101)->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(['getCurrentUser' => $this->user])
            ->getMock();

        $file = Mockery::mock(\FRSFile::class)
            ->shouldReceive("userCanDownload")
            ->with($this->user)
            ->andReturn(true)
            ->getMock();

        $this->file_factory->shouldReceive("getFRSFileFromDb")->with(1)->andReturn($file);

        $by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->once()
            ->with(
                $cross_reference,
                ""
            );

        $this->organizer->organizeFRSFileReference($cross_reference, $by_nature_organizer);
    }
}
