<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets;

class HiddenFieldsetsRetriever
{
    /** @var HiddenFieldsetsDao */
    private $dao;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @psalm-var array<int, array<int, list<array{postaction_id: int, fieldset_id: int}>>>
     */
    private $cache = [];
    /**
     * @var self
     */
    private static $instance;

    public function __construct(HiddenFieldsetsDao $dao, \Tracker_FormElementFactory $form_element_factory)
    {
        $this->dao                  = $dao;
        $this->form_element_factory = $form_element_factory;
    }

    public static function instance(): self
    {
        if (! self::$instance) {
            self::$instance = new self(new HiddenFieldsetsDao(), \Tracker_FormElementFactory::instance());
        }
        return self::$instance;
    }

    /**
     * @throws NoHiddenFieldsetsPostActionException
     */
    public function getHiddenFieldsets(\Transition $transition): HiddenFieldsets
    {
        $rows = $this->getPostActionRows($transition);

        $fieldset_ids   = [];
        $post_action_id = null;
        foreach ($rows as $row) {
            $fieldset_ids[] = $row['fieldset_id'];
            // There is only one HiddenFieldsets post-action per transition, so we just choose the last row's id
            $post_action_id = $row['postaction_id'];
        }
        if ($post_action_id === null) {
            throw new NoHiddenFieldsetsPostActionException();
        }

        $fieldsets = [];
        foreach ($fieldset_ids as $fieldset_id) {
            $fieldset = $this->form_element_factory->getFieldsetById($fieldset_id);
            if ($fieldset) {
                $fieldsets[] = $fieldset;
            }
        }

        return new HiddenFieldsets($transition, $post_action_id, $fieldsets);
    }

    /**
     * @psalm-return list<array{postaction_id: int, fieldset_id: int}>
     * @throws \Tuleap\Tracker\Workflow\Transition\OrphanTransitionException
     */
    private function getPostActionRows(\Transition $transition): array
    {
        $this->warmUpCacheForWorkflow($transition->getWorkflow());

        $workflow_id = (int) $transition->getWorkflow()->getId();
        if (isset($this->cache[$workflow_id])) {
            $transition_id = (int) $transition->getId();
            return $this->cache[$workflow_id][$transition_id] ?? [];
        }

        return $this->dao->searchByTransitionId((int) $transition->getId());
    }

    public function warmUpCacheForWorkflow(\Workflow $workflow): void
    {
        $workflow_id = (int) $workflow->getId();
        if (isset($this->cache[$workflow_id])) {
            return;
        }
        $this->cache[$workflow_id] = [];
        foreach ($this->dao->searchByWorkflow($workflow) as $row) {
            $this->cache[$workflow_id][$row['transition_id']][] = [
                'postaction_id' => $row['postaction_id'],
                'fieldset_id'   => $row['fieldset_id'],
            ];
        }
    }
}
