<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Label;

use CSRFSynchronizerToken;
use EventManager;
use Exception;
use HTTPRequest;

class EditController
{
    /**
     * @var LabelDao
     */
    private $dao;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var LabelsManagementURLBuilder
     */
    private $url_builder;

    public function __construct(LabelsManagementURLBuilder $url_builder, LabelDao $dao, EventManager $event_manager)
    {
        $this->url_builder   = $url_builder;
        $this->dao           = $dao;
        $this->event_manager = $event_manager;
    }

    public function edit(HTTPRequest $request)
    {
        $project = $request->getProject();
        $url     = $this->url_builder->getURL($project);
        $token   = new CSRFSynchronizerToken($url);
        $token->check();

        $label_to_edit_id = $request->get('id');
        $new_name         = $request->get('name');

        $this->dao->startTransaction();
        try {
            if ($this->dao->editInTransaction($project->getID(), $label_to_edit_id, $new_name)) {
                $this->mergeLabels($project, $label_to_edit_id);
                $this->dao->commit();
                $GLOBALS['HTML']->addFeedback(\Feedback::INFO, _('Label has been edited.'));
            } else {
                $GLOBALS['HTML']->addFeedback(\Feedback::INFO, _('Label has not been edited.'));
            }
        } catch (Exception $exception) {
            $this->dao->rollBack();
            $GLOBALS['HTML']->addFeedback(\Feedback::ERROR, _('An error occured while trying to edit the label.'). $exception->getMessage().db_error());
        }
        $GLOBALS['HTML']->redirect($url);
    }

    private function mergeLabels($project, $label_to_edit_id)
    {
        $other_labels = $this->dao->searchProjectLabelsThatHaveSameName($project->getID(), $label_to_edit_id);
        if (count($other_labels) === 0) {
            return;
        }

        $label_ids_to_merge = array();
        foreach ($other_labels as $row) {
            $label_ids_to_merge[] = $row['id'];
        }

        $this->event_manager->processEvent(
            new MergeProjectLabelInTransaction($project, $label_to_edit_id, $label_ids_to_merge)
        );

        $this->dao->deleteAllLabelsInTransaction($project->getID(), $label_ids_to_merge);
    }
}
