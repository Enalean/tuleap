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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\PromotedTrackerDao;

final class TrackerCreationSettingsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PromotedTrackerDao
     */
    private $in_new_dropdown_dao;
    /**
     * @var TrackerCreationSettingsBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerPrivateCommentUGroupEnabledDao
     */
    private $private_comment_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $this->in_new_dropdown_dao = \Mockery::mock(PromotedTrackerDao::class);
        $this->private_comment_dao = \Mockery::mock(TrackerPrivateCommentUGroupEnabledDao::class);
        $this->tracker             = \Mockery::mock(Tracker::class, ['getId' => 10]);

        $this->builder = new TrackerCreationSettingsBuilder($this->in_new_dropdown_dao, $this->private_comment_dao);
    }

    public function testItBuildTrackerCreationSettings(): void
    {
        $this->in_new_dropdown_dao->shouldReceive('isContaining')->with(10)->andReturnTrue();
        $this->private_comment_dao->shouldReceive('isTrackerEnabledPrivateComment')->with(10)->andReturnTrue();

        $expected = new TrackerCreationSettings(true, true);
        $result   = $this->builder->build($this->tracker);

        $this->assertEquals($expected->isDisplayedInNewDropdown(), $result->isDisplayedInNewDropdown());
        $this->assertEquals($expected->isPrivateCommentUsed(), $result->isPrivateCommentUsed());
    }
}
