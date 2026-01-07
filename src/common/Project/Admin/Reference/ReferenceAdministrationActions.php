<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Reference;

use Actions;
use ReferenceManager;
use Tuleap\Reference\CrossReferencesDao;

class ReferenceAdministrationActions extends Actions
{
    public function __construct($controler)
    {
        parent::__construct($controler);
    }

    /** Actions **/

    // Create a new reference
    public function do_create() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request    = \Tuleap\HTTPRequest::instance();
        $project_id = (int) $request->get('group_id');

        if (
            (! $project_id)
            || (! $request->get('keyword'))
            || (! $request->get('link'))
            || ! $request->isPost()
        ) {
            $GLOBALS['HTML']->addFeedback(\Feedback::ERROR, _('A parameter is missing, please press the "Back" button and complete the form'));
            return;
        }

        $this->checkCSRFToken($project_id);

        $is_super_user = user_is_super_user();
        $force         = (bool) $request->get('force') && $is_super_user;

        $command = new ReferenceCreateCommand($this->getReferenceManager());
        $result  = $command->createReference($request, $is_super_user, $force);

        if (! $result) {
            $GLOBALS['HTML']->addFeedback(
                \Feedback::ERROR,
                _('Reference pattern creation failed: the selected keyword is invalid (reserved, or already exists)')
            );
        } else {
            $feedback_msg = ($project_id === \Project::DEFAULT_TEMPLATE_PROJECT_ID)
                ? _('Successfully created system reference pattern - reference pattern added to all projects')
                : _('Successfully created reference pattern');

            $GLOBALS['HTML']->addFeedback(\Feedback::INFO, $feedback_msg);
            $GLOBALS['Response']->redirect('/project/' . urlencode((string) $project_id) . '/admin/references');
        }
    }

    // Edit an existing reference
    public function do_edit() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request    = \Tuleap\HTTPRequest::instance();
        $project_id = (int) $request->get('group_id');
        $ref_id     = (int) $request->get('reference_id');

        if (! $project_id || ! $ref_id || ! $request->isPost()) {
            $GLOBALS['HTML']->addFeedback(\Feedback::ERROR, _('A parameter is missing, please press the "Back" button and complete the form'));
            return;
        }

        $this->checkCSRFToken($request->get('group_id'));

        $reference_manager = $this->getReferenceManager();
        $reference         = $reference_manager->loadReference($ref_id, $project_id);

        if (! $reference) {
            echo '<p class="alert alert-error"> ' . _('This reference does not exist') . '</p>';
            return;
        }

        $command = new ReferenceUpdateCommand($reference_manager, $this->getCrossReferenceDao());

        $success = $command->updateReference(
            $reference,
            $request,
            user_is_super_user(),
            (bool) $request->get('force') && user_is_super_user()
        );

        if (! $success) {
            $GLOBALS['HTML']->addFeedback(
                \Feedback::ERROR,
                _('Reference pattern edition failed: the selected keyword is invalid (reserved, or already exists)')
            );
        }
    }

    // Delete a reference.
    // If it is shared by several projects, only delete the reference_group entry.
    // WARNING: If it is a system reference, delete all occurences of the reference!
    public function do_delete() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request    = \Tuleap\HTTPRequest::instance();
        $project_id = (int) $request->get('group_id');
        $ref_id     = (int) $request->get('reference_id');

        if (! $project_id || ! $ref_id || ! $request->isPost()) {
            $GLOBALS['HTML']->addFeedback(\Feedback::ERROR, _('A parameter is missing, please press the "Back" button and complete the form'));
            return;
        }

        $this->checkCSRFToken($project_id);

        $reference_manager = $this->getReferenceManager();
        $reference         = $reference_manager->loadReference($ref_id, $project_id);
        if (! $reference) {
            return;
        }

        $command = new ReferenceDeleteCommand($reference_manager);
        $result  = $command->deleteReference($reference);

        if (! $result) {
            $GLOBALS['HTML']->addFeedback(\Feedback::ERROR, _('DELETE FAILED!'));
        }

        $is_system = $reference->isSystemReference();
        $feedback  = $is_system ? _('System reference pattern deleted') : _('Reference pattern deleted');
        $GLOBALS['HTML']->addFeedback(\Feedback::INFO, $feedback);
    }

    private function checkCSRFToken(int $project_id): void
    {
        $url        = '/project/admin/reference.php?group_id=' . $project_id;
        $csrf_token = new \CSRFSynchronizerToken($url);
        $csrf_token->check();
    }

    private function getCrossReferenceDao(): CrossReferencesDao
    {
        return new CrossReferencesDao();
    }

    private function getReferenceManager(): ReferenceManager
    {
        return ReferenceManager::instance();
    }
}
