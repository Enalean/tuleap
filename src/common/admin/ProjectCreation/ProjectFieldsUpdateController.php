<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\Admin\ProjectCreation;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ProjectFieldsUpdateController implements DispatchableWithRequest
{
    /**
     * @var ProjectsFieldDescriptionUpdater
     */
    private $description_updater;

    public function __construct(ProjectsFieldDescriptionUpdater $description_updater)
    {
        $this->description_updater = $description_updater;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $csrf_token = new CSRFSynchronizerToken('/admin/project-creation/fields');
        $csrf_token->check();

        $delete_desc_id = $request->get('delete_group_desc_id');
        if ($delete_desc_id) {
            $sql    = "DELETE FROM group_desc where group_desc_id='" . db_ei($delete_desc_id) . "'";
            $result = db_query($sql);

            if (!$result) {
                $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('admin_desc_fields', 'del_desc_field_fail'));
            }

            $sql    = "DELETE FROM group_desc_value where group_desc_id='" . db_ei($delete_desc_id) . "'";
            $result = db_query($sql);

            if (!$result) {
                $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('admin_desc_fields', 'del_desc_field_fail'));
            } else {
                $layout->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('admin_desc_fields', 'remove_success'));
            }

            $layout->redirect('/admin/project-creation/fields');
        }

        $make_required_desc_id   = $request->get('make_required_desc_id');
        $remove_required_desc_id = $request->get('remove_required_desc_id');
        $this->description_updater->updateDescription($make_required_desc_id, $remove_required_desc_id, $layout);

        $update           = $request->get('update_desc');
        $add_desc         = $request->get('add_desc');
        $desc_name        = $request->get('form_name');
        $desc_description = trim($request->get('form_desc'));
        $desc_type        = $request->get('form_type');
        $desc_rank        = $request->get('form_rank');
        $desc_required    = $request->get('form_required');

        if (($add_desc || $update)) {
            //data validation
            $valid_data = 1;
            if (!trim($desc_name)) {
                $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('admin_desc_fields', 'info_missed'));
                $valid_data = 0;
            }

            if (!is_numeric($desc_rank)) {
                $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('admin_desc_fields', 'info_rank_noint'));
                $valid_data = 0;
            }

            if ($valid_data == 1) {
                if ($add_desc) {
                    $sql = "INSERT INTO group_desc (desc_name, desc_description, desc_rank, desc_type, desc_required) ";
                    $sql .= "VALUES ('" . db_escape_string($desc_name) . "','" . db_escape_string($desc_description) . "','";
                    $sql .= db_escape_string($desc_rank) . "','" . db_es($desc_type) . "','" . db_ei($desc_required) . "')";
                    $result = db_query($sql);

                    if (!$result) {
                        list($host, $port) = explode(':', $GLOBALS['sys_default_domain']);
                        $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('admin_desc_fields', 'ins_desc_field_fail', array($host, db_error())));
                    } else {
                        $layout->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('admin_desc_fields', 'add_success'));
                    }
                } else {
                    $sql = "UPDATE group_desc SET ";
                    $sql .= "desc_name='" . db_escape_string($desc_name) . "',";
                    $sql .= "desc_description='" . db_escape_string($desc_description) . "',";
                    $sql .= "desc_rank='" . db_escape_string($desc_rank) . "',";
                    $sql .= "desc_type='" . db_escape_string($desc_type) . "',";
                    $sql .= "desc_required='" . db_escape_string($desc_required) . "'";
                    $sql .= " WHERE group_desc_id='" . db_ei($request->get('form_desc_id')) . "'";

                    $result = db_query($sql);

                    if (!$result || db_affected_rows($result) < 1) {
                        $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('admin_desc_fields', 'update_desc_field_fail', (db_error() ? db_error() : ' ')));
                    } else {
                        $layout->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('admin_desc_fields', 'update_success'));
                    }
                }
            }
        }
        $layout->redirect('/admin/project-creation/fields');
    }
}
