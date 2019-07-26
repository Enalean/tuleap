<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
use Project;
use ProjectHistoryDao;
use Tuleap\Color\AllowedColorsCollection;
use Tuleap\Label\CollectionOfLabelableDao;

class EditController
{
    /**
     * @var LabelDao
     */
    private $dao;
    /**
     * @var LabelsManagementURLBuilder
     */
    private $url_builder;
    /**
     * @var AllowedColorsCollection
     */
    private $allowed_colors;
    /**
     * @var CollectionOfLabelableDao
     */
    private $labelable_daos;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        LabelsManagementURLBuilder $url_builder,
        LabelDao $dao,
        ProjectHistoryDao $history_dao,
        CollectionOfLabelableDao $labelable_daos,
        AllowedColorsCollection $allowed_colors,
        EventManager $event_manager
    ) {
        $this->url_builder    = $url_builder;
        $this->dao            = $dao;
        $this->history_dao    = $history_dao;
        $this->allowed_colors = $allowed_colors;
        $this->labelable_daos = $labelable_daos;
        $this->event_manager  = $event_manager;
    }

    public function edit(HTTPRequest $request)
    {
        $project = $request->getProject();
        $url     = $this->url_builder->getURL($project);
        $token   = new CSRFSynchronizerToken($url);
        $token->check();

        $label_to_edit_id = $request->get('id');
        $new_name         = $request->get('name');
        $new_color        = $request->get('color');
        $new_is_outline   = $request->get('is_outline');

        $this->dao->startTransaction();
        try {
            $this->checkColor($new_color);
            $label = $this->dao->getLabelById($label_to_edit_id);
            $previous_name = $label['name'];

            if ($this->dao->editInTransaction($project->getID(), $label_to_edit_id, $new_name, $new_color, $new_is_outline)) {
                $this->mergeLabels($project, $label_to_edit_id);
                $this->dao->commit();
                if ($previous_name !== $new_name) {
                    $this->history_dao->groupAddHistory(
                        'label_renamed',
                        "$previous_name ⇒ $new_name",
                        $project->getID()
                    );
                }
                $GLOBALS['HTML']->addFeedback(\Feedback::INFO, _('Label has been edited.'));
            } else {
                $GLOBALS['HTML']->addFeedback(\Feedback::INFO, _('Label has not been edited.'));
            }
        } catch (Exception $exception) {
            $this->dao->rollBack();
            $GLOBALS['HTML']->addFeedback(\Feedback::ERROR, _('An error occurred while trying to edit the label.'));
        }
        $GLOBALS['HTML']->redirect($url);
    }

    private function mergeLabels(Project $project, $label_to_edit_id)
    {
        $other_labels = $this->dao->searchProjectLabelsThatHaveSameName($project->getID(), $label_to_edit_id);
        if (count($other_labels) === 0) {
            return;
        }

        $label_ids_to_merge = array();
        foreach ($other_labels as $row) {
            $label_ids_to_merge[] = $row['id'];
        }

        foreach ($this->labelable_daos->getAll() as $dao) {
            $dao->mergeLabelsInTransaction($project->getID(), $label_to_edit_id, $label_ids_to_merge);
        }

        $this->dao->deleteAllLabelsInTransaction($project->getID(), $label_ids_to_merge);

        $this->event_manager->processEvent(new MergeLabels($label_to_edit_id, $label_ids_to_merge));
    }

    private function checkColor($new_color)
    {
        if (! $this->allowed_colors->isColorAllowed($new_color)) {
            throw new InvalidColorException();
        }
    }
}
