<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;

class HeaderPresenterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;

    public function setUp(): void
    {
        parent::setUp();

        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(101);
        $this->tracker->shouldReceive('getName')->andReturn('Tracker 01');
    }

    public function testItSetsTheActiveTabBasedOnCurrentItem()
    {
        $presenter = new HeaderPresenter($this->tracker, 'editformElements', []);
        $this->assertTrue($presenter->is_fields_tab_active);

        $presenter = new HeaderPresenter($this->tracker, 'dependencies', []);
        $this->assertTrue($presenter->is_fields_tab_active);

        $presenter = new HeaderPresenter($this->tracker, 'editsemantic', []);
        $this->assertTrue($presenter->is_semantics_tab_active);

        $presenter = new HeaderPresenter($this->tracker, 'editperms', []);
        $this->assertTrue($presenter->is_permissions_tab_active);

        $presenter = new HeaderPresenter($this->tracker, 'editworkflow', []);
        $this->assertTrue($presenter->is_workflow_tab_active);

        $presenter = new HeaderPresenter($this->tracker, 'editnotifications', []);
        $this->assertTrue($presenter->is_notification_tab_active);

        $presenter = new HeaderPresenter($this->tracker, 'other', []);
        $this->assertTrue($presenter->is_other_tab_active);
    }
}
