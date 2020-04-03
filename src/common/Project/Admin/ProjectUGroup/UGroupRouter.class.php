<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

namespace Tuleap\Project\Admin\ProjectUGroup;

use Codendi_Request;
use CSRFSynchronizerToken;
use EventManager;
use Project;
use ProjectUGroup;
use UGroupManager;
use UserManager;

class UGroupRouter
{
    private const CSRF_TOKEN = 'user_group_modifications';

    /**
     * @var DelegationController
     */
    private $delegation_controller;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var Codendi_Request
     */
    private $request;
    /**
     * @var BindingController
     */
    private $binding_controller;
    /**
     * @var IndexController
     */
    private $index_controller;
    /**
     * @var DetailsController
     */
    private $details_controller;
    /**
     * @var EditBindingUGroupEventLauncher
     */
    private $edit_event_launcher;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        UGroupManager $ugroup_manager,
        Codendi_Request $request,
        EditBindingUGroupEventLauncher $edit_event_launcher,
        BindingController $binding_controller,
        DelegationController $delegation_controller,
        IndexController $index_controller,
        DetailsController $details_controller,
        UserManager $user_manager
    ) {
        $this->ugroup_manager        = $ugroup_manager;
        $this->request               = $request;
        $this->binding_controller    = $binding_controller;
        $this->delegation_controller = $delegation_controller;
        $this->index_controller      = $index_controller;
        $this->details_controller    = $details_controller;
        $this->edit_event_launcher   = $edit_event_launcher;
        $this->user_manager          = $user_manager;
    }

    public function process()
    {
        $project = $this->request->getProject();
        $ugroup  = $this->getUGroup($project);
        $csrf    = self::getCSRFTokenSynchronizer();
        switch ($this->request->get('action')) {
            case 'remove_binding':
                $csrf->check(self::getUGroupUrl($ugroup));
                $this->binding_controller->removeBinding($ugroup);
                $this->redirect($ugroup);
                break;
            case 'add_binding':
                $csrf->check(self::getUGroupUrl($ugroup));
                $this->binding_controller->addBinding($ugroup);
                $this->redirect($ugroup);
                break;
            case 'update_details':
                $csrf->check(self::getUGroupUrl($ugroup));
                try {
                    $this->details_controller->updateDetails($ugroup);
                } catch (CannotCreateUGroupException $ex) {
                    $GLOBALS['Response']->addFeedback(\Feedback::ERROR, $ex->getMessage());
                }
                $this->redirect($ugroup);
                break;
            case 'update_permssions_delegation':
                $csrf->check(self::getUGroupUrl($ugroup));
                $this->delegation_controller->updateDelegation($ugroup, $this->request->get('permissions-delegation'));
                $this->redirect($ugroup);
                break;
            default:
                $event = new UGroupEditProcessAction($this->request, $ugroup, $csrf, $this->edit_event_launcher);
                EventManager::instance()->processEvent($event);
                if ($event->hasBeenHandled()) {
                    $this->redirect($ugroup);
                } else {
                    $this->index_controller->display($ugroup, $csrf, $this->user_manager->getCurrentUser());
                }
        }
    }

    private function getUGroup(Project $project)
    {
        $ugroup_id = $this->request->getValidated('ugroup_id', 'uint', 0);
        if (! $ugroup_id) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), 'The ugroup ID is missing');
        }

        return $this->ugroup_manager->getUGroup($project, $ugroup_id);
    }

    protected function redirect(ProjectUGroup $ugroup)
    {
        $GLOBALS['Response']->redirect(self::getUGroupUrl($ugroup));
    }

    public static function getUGroupUrl(ProjectUGroup $ugroup)
    {
        return '/project/admin/editugroup.php?' . http_build_query(
            array(
                'group_id'  => $ugroup->getProjectId(),
                'ugroup_id' => $ugroup->getId()
            )
        );
    }

    public static function getCSRFTokenSynchronizer(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::CSRF_TOKEN);
    }
}
