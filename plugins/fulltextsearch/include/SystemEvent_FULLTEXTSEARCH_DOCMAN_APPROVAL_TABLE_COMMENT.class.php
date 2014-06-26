<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class SystemEvent_FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT extends SystemEvent {
    const NAME = 'FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT';

    /**
     * @var FullTextSearchDocmanActions
     */
    private $actions;

    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;


    public function injectDependencies(FullTextSearchDocmanActions $actions, Docman_ItemFactory $item_factory) {
        $this->actions      = $actions;
        $this->item_factory = $item_factory;
    }

    /**
     * Process the system event
     *
     * @return bool
     */
    public function process() {
        try {
            $group_id    = (int)$this->getRequiredParameter(0);
            $item_id     = (int)$this->getRequiredParameter(1);
            $version_nb  = (int)$this->getRequiredParameter(2);
            $table_id    = (int)$this->getRequiredParameter(3);
            $reviewer_id = (int)$this->getRequiredParameter(4);

            $item = $this->item_factory->getItemFromDb($item_id);
            if ($item) {
                $approval_table_factory = Docman_ApprovalTableFactory::getFromItem($item, $version_nb);
                $table = $approval_table_factory->getTable();
                if ($table) {
                    $reviewer_factory = new Docman_ApprovalTableReviewerFactory($table, $item);
                    $review = $reviewer_factory->getReviewer($reviewer_id);
                    if ($review) {
                        $this->done('Index '.$review->getComment());
                    } else {
                        $this->error('Review not found');
                    }
                } else {
                    $this->error('Table not found');
                }
            } else {
                $this->error('Item not found');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        return false;
    }

    /**
     * @return string a human readable representation of parameters
     */
    public function verbalizeParameters($with_link) {
        $txt = '';

        $group_id = (int)$this->getRequiredParameter(0);
        $item_id  = (int)$this->getRequiredParameter(1);
        $version  = (int)$this->getParameter(2);
        $txt .= 'project: '. $this->verbalizeProjectId($group_id, $with_link) .', item id: '. $this->verbalizeDocmanItemId($group_id, $item_id, $with_link);
        if ($version) {
            $txt .= ', version: '. $version;
        }
        return $txt;
    }

    private function verbalizeDocmanItemId($group_id, $item_id, $with_link) {
        $txt = '#'. $item_id;
        if ($with_link) {
            $txt = '<a href="/plugins/docman/?group_id='. $group_id .'&action=details&id='. $item_id .'&section=properties">'. $txt .'</a>';
        }
        return $txt;
    }
}
