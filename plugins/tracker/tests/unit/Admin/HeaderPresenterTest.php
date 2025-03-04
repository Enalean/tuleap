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

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\WorkflowMenuPresenter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HeaderPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItSetsTheActiveTabBasedOnCurrentItem()
    {
        $tracker = TrackerTestBuilder::aTracker()->build();

        $presenter = new HeaderPresenter($tracker, 'editformElements', [], new WorkflowMenuPresenter([]));
        $this->assertTrue($presenter->is_fields_tab_active);

        $presenter = new HeaderPresenter($tracker, 'dependencies', [], new WorkflowMenuPresenter([]));
        $this->assertTrue($presenter->is_fields_tab_active);

        $presenter = new HeaderPresenter($tracker, 'editsemantic', [], new WorkflowMenuPresenter([]));
        $this->assertTrue($presenter->is_semantics_tab_active);

        $presenter = new HeaderPresenter($tracker, 'editperms', [], new WorkflowMenuPresenter([]));
        $this->assertTrue($presenter->is_permissions_tab_active);

        $presenter = new HeaderPresenter($tracker, 'editworkflow', [], new WorkflowMenuPresenter([]));
        $this->assertTrue($presenter->is_workflow_tab_active);

        $presenter = new HeaderPresenter($tracker, 'editnotifications', [], new WorkflowMenuPresenter([]));
        $this->assertTrue($presenter->is_notification_tab_active);

        $presenter = new HeaderPresenter($tracker, 'other', [], new WorkflowMenuPresenter([]));
        $this->assertTrue($presenter->is_other_tab_active);
    }
}
