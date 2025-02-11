<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\XMLImport;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use SimpleXMLElement;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UGroupManager;

final class TrackerPrivateCommentUGroupExtractorTest extends TestCase
{
    private TrackerPrivateCommentUGroupEnabledDao&MockObject $dao;
    private UGroupManager&MockObject $ugroup_manager;
    private TrackerPrivateCommentUGroupExtractor $extractor;
    private Artifact $artifact;
    private Project $project;

    protected function setUp(): void
    {
        $this->dao            = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $this->ugroup_manager = $this->createMock(UGroupManager::class);

        $this->project = ProjectTestBuilder::aProject()->build();

        $this->artifact = ArtifactTestBuilder::anArtifact(135)
            ->inTracker(TrackerTestBuilder::aTracker()->withProject($this->project)->withId(52)->build())
            ->build();

        $this->extractor = new TrackerPrivateCommentUGroupExtractor($this->dao, $this->ugroup_manager);
    }

    public function testGetEmptyArrayIfNoPrivateUGroupsKey(): void
    {
        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <comment></comment>
            EOS
        );

        $this->dao->expects(self::never())->method('isTrackerEnabledPrivateComment');

        self::assertCount(0, $this->extractor->extractUGroupsFromXML($this->artifact, $xml));
    }

    public function testGetEmptyArrayIfTrackerDontUsePrivateComment(): void
    {
        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <comment>
                <private_ugroups>
                </private_ugroups>
            </comment>
            EOS
        );

        $this->dao->expects(self::once())->method('isTrackerEnabledPrivateComment')->with(52)->willReturn(false);

        self::assertCount(0, $this->extractor->extractUGroupsFromXML($this->artifact, $xml));
    }

    public function testGetEmptyArrayIfNoUgroupKey(): void
    {
        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <comment>
                <private_ugroups>
                </private_ugroups>
            </comment>
            EOS
        );

        $this->dao->expects(self::once())->method('isTrackerEnabledPrivateComment')->with(52)->willReturn(true);

        self::assertCount(0, $this->extractor->extractUGroupsFromXML($this->artifact, $xml));
    }

    public function testGetEmptyArrayIfUgroupDoesNotExist(): void
    {
        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <comment>
                <private_ugroups>
                    <ugroup>my_group</ugroup>
                </private_ugroups>
            </comment>
            EOS
        );

        $this->dao->expects(self::once())->method('isTrackerEnabledPrivateComment')
            ->with(52)
            ->willReturn(true);
        $this->ugroup_manager->expects(self::once())
            ->method('getUGroupByName')
            ->with($this->project, 'my_group')
            ->willReturn(null);

        self::assertCount(0, $this->extractor->extractUGroupsFromXML($this->artifact, $xml));
    }

    public function testGetProjectUgroupArray(): void
    {
        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <comment>
                <private_ugroups>
                    <ugroup>my_group</ugroup>
                </private_ugroups>
            </comment>
            EOS
        );

        $ugroup_expected = ProjectUGroupTestBuilder::aCustomUserGroup(452)->withName('my_group')->build();

        $this->dao->expects(self::once())->method('isTrackerEnabledPrivateComment')
            ->with(52)
            ->willReturn(true);

        $this->ugroup_manager->expects(self::once())
            ->method('getUGroupByName')
            ->with($this->project, 'my_group')
            ->willReturn($ugroup_expected);

        $ugroup = $this->extractor->extractUGroupsFromXML($this->artifact, $xml);

        self::assertCount(1, $ugroup);
        self::assertSame($ugroup_expected, $ugroup[0]);
    }
}
