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
use ArtifactGroupListDao;
use CodendiDataAccess;
use HTTPRequest;
use Reference;
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
        $request = HTTPRequest::instance();
        // Sanity check
        if (
            (! $request->get('group_id'))
            || (! $request->get('keyword'))
            || (! $request->get('link'))
            || ! $request->isPost()
        ) {
            exit_error(
                _('Error'),
                _('A parameter is missing, please press the "Back" button and complete the form')
            );
        }

        $this->checkCSRFToken($request->get('group_id'));

        $force = $request->get('force');
        if (! user_is_super_user()) {
            $force = false;
        }

        $reference_manager = ReferenceManager::instance();
        if ($request->get('service_short_name') == 100) { // none
            $service_short_name = "";
        } else {
            $service_short_name = $request->get('service_short_name');
        }
        $ref = new Reference(
            0,
            $request->get('keyword'),
            $request->get('description'),
            $request->get('link'),
            $request->get('scope'),
            $service_short_name,
            $request->get('nature'),
            $request->get('is_used'),
            $request->get('group_id')
        );
        if (($ref->getGroupId() == 100) && ($ref->isSystemReference())) {
            // Add reference to ALL active projects!
            $result = $reference_manager->createSystemReference($ref, $force);
            if (! $result) {
                exit_error(
                    _('Error'),
                    _('Reference pattern creation failed: the selected keyword is invalid (reserved, or already exists)')
                );
            } else {
                $GLOBALS['HTML']->addFeedback(
                    \Feedback::INFO,
                    _('Successfully created system reference pattern - reference pattern added to all projects')
                );
            }
        } else {
            $result = $reference_manager->createReference($ref, $force);
            if (! $result) {
                exit_error(
                    _('Error'),
                    _('Reference pattern creation failed: the selected keyword is invalid (reserved, or already exists)')
                );
            } else {
                $GLOBALS['HTML']->addFeedback(
                    \Feedback::INFO,
                    _('Successfully created reference pattern')
                );
            }
        }
    }

    // Edit an existing reference
    public function do_edit() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = HTTPRequest::instance();
        // Sanity check
        if (
            (! $request->get('group_id'))
            || (! $request->get('reference_id'))
            || ! $request->isPost()
        ) {
            exit_error(
                _('Error'),
                _('A parameter is missing, please press the "Back" button and complete the form')
            );
        }

        $this->checkCSRFToken($request->get('group_id'));

        $reference_manager = ReferenceManager::instance();

        $force = $request->get('force');
        $su    = false;
        if (user_is_super_user()) {
            $su = true;
        } else {
            $force = false;
        }

        // Load existing reference from DB
        $ref = $reference_manager->loadReference($request->get('reference_id'), $request->get('group_id'));

        if (! $ref) {
            echo '<p class="alert alert-error"> ' . _('This reference does not exist') . '</p>';

            return;
        }

        if (($ref->isSystemReference()) && ($ref->getGroupId() != 100)) {
            // Only update is_active field
            if ($ref->isActive() != $request->get('is_used')) {
                $reference_manager->updateIsActive($ref, $request->get('is_used'));
            }
        } else {
            if (! $su) {
                // Only a server admin may define a service_id
                $service_short_name = "";
            } else {
                if ($request->get('service_short_name') == 100) { // none
                    $service_short_name = "";
                } else {
                    $service_short_name = $request->get('service_short_name');
                }
            }

            $old_keyword = $ref->getKeyword();
            //Update table 'reference'
            $new_ref = new Reference(
                $request->get('reference_id'),
                $request->get('keyword'),
                $request->get('description'),
                $request->get('link'),
                $ref->getScope(), // Can't edit a ref scope
                $service_short_name,
                $request->get('nature'),
                $request->get('is_used'),
                $request->get('group_id')
            );
            $result  = $reference_manager->updateReference($new_ref, $force);

            if (! $result) {
                exit_error(
                    _('Error'),
                    _('Reference pattern edition failed: the selected keyword is invalid (reserved, or already exists)')
                );
            } else {
                if ($old_keyword != $request->get('keyword')) {
                    //Update table 'cross_reference'
                    $reference_dao = $this->getCrossReferenceDao();
                    $reference_dao->updateTargetKeyword(
                        $old_keyword,
                        $request->get('keyword'),
                        (int) $request->get('group_id')
                    );
                    $reference_dao->updateSourceKeyword(
                        $old_keyword,
                        $request->get('keyword'),
                        $request->get('group_id')
                    );

                    //Update table 'artifact_group_list'
                    $reference_dao = $this->getArtifactGroupListDao();
                    $result        = $reference_dao->updateItemName(
                        $request->get('group_id'),
                        $old_keyword,
                        $request->get('keyword')
                    );
                }
            }
        }
    }

    // Delete a reference.
    // If it is shared by several projects, only delete the reference_group entry.
    // WARNING: If it is a system reference, delete all occurences of the reference!
    public function do_delete() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = HTTPRequest::instance();
        // Sanity check
        if (
            (! $request->get('group_id'))
            || (! $request->get('reference_id'))
            || ! $request->isPost()
        ) {
            exit_error(
                _('Error'),
                _('A parameter is missing, please press the "Back" button and complete the form')
            );
        }

        $this->checkCSRFToken($request->get('group_id'));

        $reference_manager = ReferenceManager::instance();
        // Load existing reference from DB
        $ref = $reference_manager->loadReference($request->get('reference_id'), $request->get('group_id'));

        if (! $ref) {
            // Already deleted? User reloaded a page?
            return;
        }

        // WARNING: If it is a system reference, delete all occurences of the reference!
        if ($ref->isSystemReference()) {
            $result = $reference_manager->deleteSystemReference($ref);
            if ($result) {
                $GLOBALS['HTML']->addFeedback(
                    \Feedback::INFO,
                    _('System reference pattern deleted')
                );
            }
        } else {
            $result = $reference_manager->deleteReference($ref);
            if ($result) {
                $GLOBALS['HTML']->addFeedback(
                    \Feedback::INFO,
                    _('Reference pattern deleted')
                );
            }
        }
        if (! $result) {
            exit_error(
                _('Error'),
                _('DELETE FAILED!')
            );
        }
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

    private function getArtifactGroupListDao()
    {
        return new ArtifactGroupListDao(CodendiDataAccess::instance());
    }
}
