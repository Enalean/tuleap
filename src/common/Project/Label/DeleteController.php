<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use ProjectHistoryDao;
use Tuleap\Label\CollectionOfLabelableDao;

class DeleteController
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
        EventManager $event_manager,
    ) {
        $this->url_builder    = $url_builder;
        $this->dao            = $dao;
        $this->history_dao    = $history_dao;
        $this->labelable_daos = $labelable_daos;
        $this->event_manager  = $event_manager;
    }

    public function delete(HTTPRequest $request)
    {
        $project = $request->getProject();
        $url     = $this->url_builder->getURL($project);
        $token   = new CSRFSynchronizerToken($url);
        $token->check();

        $label_to_delete_id = $request->get('id');

        $this->dao->startTransaction();
        try {
            $label = $this->dao->getLabelById($label_to_delete_id);
            foreach ($this->labelable_daos->getAll() as $dao) {
                $dao->deleteInTransaction($project->getID(), $label_to_delete_id);
            }
            $this->dao->deleteInTransaction($project->getID(), $label_to_delete_id);

            $event = new RemoveLabel($label_to_delete_id);
            $this->event_manager->processEvent($event);

            $this->dao->commit();

            $this->history_dao->groupAddHistory('label_deleted', $label['name'], $project->getID());
            $GLOBALS['HTML']->addFeedback(\Feedback::INFO, _('Label has been deleted from the project.'));
        } catch (Exception $exception) {
            $this->dao->rollBack();
            $GLOBALS['HTML']->addFeedback(\Feedback::ERROR, _('An error occurred while trying to remove the label.'));
        }
        $GLOBALS['HTML']->redirect($url);
    }
}
