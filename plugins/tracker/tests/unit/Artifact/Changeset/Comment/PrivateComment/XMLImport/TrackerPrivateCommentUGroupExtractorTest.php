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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SimpleXMLElement;
use Tracker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;

final class TrackerPrivateCommentUGroupExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerPrivateCommentUGroupEnabledDao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var TrackerPrivateCommentUGroupExtractor
     */
    private $extractor;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var \Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->dao            = \Mockery::mock(TrackerPrivateCommentUGroupEnabledDao::class);
        $this->ugroup_manager = \Mockery::mock(\UGroupManager::class);

        $this->project = ProjectTestBuilder::aProject()->build();

        $this->artifact = \Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getTrackerId')->andReturn(52);
        $this->artifact
            ->shouldReceive('getTracker')
            ->andReturn(\Mockery::mock(Tracker::class, ['getProject' => $this->project]));

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

        $this->dao->shouldReceive('isTrackerEnabledPrivateComment')->never();

        $this->assertCount(0, $this->extractor->extractUGroupsFromXML($this->artifact, $xml));
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

        $this->dao->shouldReceive('isTrackerEnabledPrivateComment')->with(52)->once()->andReturnFalse();

        $this->assertCount(0, $this->extractor->extractUGroupsFromXML($this->artifact, $xml));
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

        $this->dao->shouldReceive('isTrackerEnabledPrivateComment')->with(52)->once()->andReturnTrue();

        $this->assertCount(0, $this->extractor->extractUGroupsFromXML($this->artifact, $xml));
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

        $this->dao->shouldReceive('isTrackerEnabledPrivateComment')
            ->with(52)
            ->once()
            ->andReturnTrue();
        $this->ugroup_manager
            ->shouldReceive('getUGroupByName')
            ->with($this->project, 'my_group')
            ->once()
            ->andReturnNull();

        $this->assertCount(0, $this->extractor->extractUGroupsFromXML($this->artifact, $xml));
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

        $ugroup_expected = \Mockery::mock(\ProjectUGroup::class);

        $this->dao->shouldReceive('isTrackerEnabledPrivateComment')
            ->with(52)
            ->once()
            ->andReturnTrue();

        $this->ugroup_manager
            ->shouldReceive('getUGroupByName')
            ->with($this->project, 'my_group')
            ->once()
            ->andReturn($ugroup_expected);

        $ugroup = $this->extractor->extractUGroupsFromXML($this->artifact, $xml);

        $this->assertCount(1, $ugroup);
        $this->assertEquals($ugroup_expected, $ugroup[0]);
    }
}
