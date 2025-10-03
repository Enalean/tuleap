<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use AgileDashboard_Milestone_Backlog_BacklogItemCollection;
use AgileDashboard_Milestone_Backlog_IBacklogItemCollection;
use Override;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * I build BacklogItem{,Collection}
 */
class BacklogItemBuilder implements IBuildBacklogItemAndBacklogItemCollection
{
    #[Override]
    public function getCollection(): AgileDashboard_Milestone_Backlog_IBacklogItemCollection
    {
        return new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
    }

    #[Override]
    public function getItem(Artifact $artifact, ?string $redirect_to_self, bool $is_inconsistent): BacklogItem
    {
        return new BacklogItem($artifact, $is_inconsistent);
    }
}
