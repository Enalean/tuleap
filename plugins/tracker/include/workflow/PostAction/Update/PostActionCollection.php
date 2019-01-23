<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update;

use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuild;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionIdCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionsDiff;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionVisitor;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetFloatValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException;
use Tuleap\Tracker\Workflow\Update\PostAction;

/**
 * Consistent set of actions
 */
class PostActionCollection implements PostActionVisitor
{
    /**
     * @var CIBuild[]
     */
    private $ci_build_actions = [];

    /**
     * @var SetDateValue[]
     */
    private $set_date_value_actions = [];

    /**
     * @var SetIntValue[]
     */
    private $set_int_value_actions = [];

    /**
     * @var SetFloatValue[]
     */
    private $set_float_value_actions = [];

    public function __construct(PostAction... $actions)
    {
        foreach ($actions as $action) {
            $action->accept($this);
        }
    }

    public function visitCIBuild(CIBuild $ci_build_action)
    {
        $this->ci_build_actions[] = $ci_build_action;
    }

    public function visitSetDateValue(SetDateValue $set_date_value_action)
    {
        $this->set_date_value_actions[] = $set_date_value_action;
    }

    public function visitSetIntValue(SetIntValue $set_int_value_action)
    {
        $this->set_int_value_actions[] = $set_int_value_action;
    }

    public function visitSetFloatValue(SetFloatValue $set_float_value_action)
    {
        $this->set_float_value_actions[] = $set_float_value_action;
    }

    /**
     * @return CIBuild[]
     */
    public function getCIBuildActions(): array
    {
        return $this->ci_build_actions;
    }

    /**
     * Compare only CIBuild actions against a list of action ids:
     * - Actions without id are marked as added
     * - Actions whose id is in given list are marked as updated
     * @throws UnknownPostActionIdsException
     */
    public function compareCIBuildActionsTo(PostActionIdCollection $our_ids): PostActionsDiff
    {
        return $this->compare($our_ids, $this->ci_build_actions);
    }

    /**
     * Compare only Set Date Value actions against a list of action ids:
     * - Actions without id are marked as added
     * - Actions whose id is in given list are marked as updated
     */
    public function compareSetDateValueActionsTo(PostActionIdCollection $our_ids): PostActionsDiff
    {
        return $this->compare($our_ids, $this->set_date_value_actions);
    }

    /**
     * @param PostActionIdCollection $our_ids ids of actions taken as reference
     * @param PostAction[]           $theirs  actions to compare
     *
     * @return PostActionsDiff Comparison result
     * @throws UnknownPostActionIdsException
     */
    private function compare(PostActionIdCollection $our_ids, array $theirs): PostActionsDiff
    {
        $added = [];
        $updated = [];
        $unknown_ids = [];

        foreach ($theirs as $post_action) {
            if ($post_action->getId() === null) {
                $added[] = $post_action;
            } elseif ($our_ids->contains($post_action->getId())) {
                $updated[] = $post_action;
            } else {
                $unknown_ids[] = $post_action->getId();
            }
        }

        if (count($unknown_ids) > 0) {
            throw new UnknownPostActionIdsException($unknown_ids);
        }

        return new PostActionsDiff(
            array_values($added),
            array_values($updated)
        );
    }
}
