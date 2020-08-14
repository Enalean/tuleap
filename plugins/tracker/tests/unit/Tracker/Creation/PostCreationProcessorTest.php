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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\NewDropdown\TrackerInNewDropdownDao;

final class PostCreationProcessorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker
     */
    private $tracker;

    /**
     * @var PostCreationProcessor
     */
    private $processor;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerInNewDropdownDao
     */
    private $in_new_dropdown_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ReferenceManager
     */
    private $reference_manager;

    protected function setUp(): void
    {
        $this->reference_manager = Mockery::mock(\ReferenceManager::class);
        $this->in_new_dropdown_dao = Mockery::mock(TrackerInNewDropdownDao::class);
        $this->processor = new PostCreationProcessor($this->reference_manager, $this->in_new_dropdown_dao);

        $this->tracker = Mockery::mock(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(10);
        $this->tracker->shouldReceive('getItemName')->andReturn("bug")->once();
        $this->tracker->shouldReceive('getName')->andReturn("Bug")->once();
        $this->tracker->shouldReceive('getGroupId')->andReturn(101)->once();
    }

    public function testItAddsReferences(): void
    {
        $this->reference_manager->shouldReceive('createReference')->once();

        $settings = new TrackerCreationSettings(false);

        $this->processor->postCreationProcess($this->tracker, $settings);
    }

    public function testItAddsTrackerInDropDownIfSettingsSaidSo(): void
    {
        $this->reference_manager->shouldReceive('createReference')->once();
        $settings = new TrackerCreationSettings(true);
        $this->in_new_dropdown_dao->shouldReceive('insert')->with($this->tracker->getId())->once();

        $this->processor->postCreationProcess($this->tracker, $settings);
    }

    public function testItDoesNotAddTrackerInDropdown(): void
    {
        $this->reference_manager->shouldReceive('createReference')->once();
        $settings = new TrackerCreationSettings(false);
        $this->in_new_dropdown_dao->shouldReceive('insert')->with($this->tracker->getId())->never();

        $this->processor->postCreationProcess($this->tracker, $settings);
    }
}
