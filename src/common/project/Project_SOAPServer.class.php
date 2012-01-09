<?php

/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'ProjectManager.class.php';
require_once 'ProjectCreator.class.php';
require_once 'www/include/account.php';

class Project_SOAPServer {

    /**
     * @var ProjectManager
     */
    private $projectManager;

    /**
     * @var ProjectCreator
     */
    private $projectCreator;

    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(ProjectManager $projectManager, ProjectCreator $projectCreator, UserManager $userManager) {
        $this->projectManager = $projectManager;
        $this->projectCreator = $projectCreator;
        $this->userManager = $userManager;
    }

    /**
     * Create a new project
     *
     * This method throw an exception if there is a conflict on names or if there is an error during the creation process.
     * TODO: list error fault code
     * 
     * You can select:
     * * The privacy of the project 'private' or 'public'
     * * The projectId of the template (100 means default template aka default new project).
     * 
     * It assumes a couple of things:
     * * The project type is "Project" (Not modifiable)
     * * There is no "Project description" nor any "Project description fields" (long desc, patents, IP, other software)
     * * The project services are inherited from the template
     * * There is no trove cat selected
     * * The default Software Policy is "Site exchange policy".
     *
     * Projects are automatically accepted
     *
     * @param string  $sessionKey     The session hash associated with the session opened by the person who calls the service
     * @param String  $adminSessionKey Login of the user on behalf of who you create the project
     * @param String  $shortName      Unix name of the project
     * @param String  $publicName       Full name of the project
     * @param String  $privacy        Either 'public' or 'private'
     * @param Integer $templateId     Id of template project
     *
     * @return Integer The ID of newly created project
     */
    public function addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId) {
        $admin = $this->userManager->getCurrentUser($adminSessionKey);
        if ($admin && $admin->isLoggedIn() && $admin->isSuperUser()) {
            if ($this->continueSession($sessionKey)) {
                $template = $this->projectManager->getProject($templateId);
                if ($template && !$template->isError()) {
                    try {
                        return $this->formatDataAndCreateProject($shortName, $publicName, $privacy, $template);
                    } catch (Exception $e) {
                        throw new SoapFault('3100', $e->getMessage());
                    }
                } else {
                    throw new SoapFault('3100', 'Invalid template id ' . $templateId);
                }
            } else {
                throw new SoapFault('3001', 'Invalid session');
            }
        } else {
            throw new SoapFault('3200', 'Only site admin is allowed to create project on behalf of users');
        }
    }

    private function formatDataAndCreateProject($shortName, $publicName, $privacy, Project $template) {
        $data = array(
            'project' => array(
                'form_license'           => 'xrx',
                'form_license_other'     => '',
                'form_short_description' => '',
                'is_test'                => false,
                'is_public'              => false,
                'services'               => array(),
                'built_from_template'    => $template->getID(),
            )
        );

        if ($privacy === 'public') {
            $data['project']['is_public'] = true;
        }

        foreach ($template->services as $key => $service) {
            $is_used = $service->isActive() && $service->isUsed();
            $data['project']['services'][$service->getId()]['is_used'] = $is_used;
        }

        $project = $this->projectCreator->create($shortName, $publicName, $data);
        return $this->projectManager->activate($project);
    }

    /**
     * Add given user as member of the project
     *
     * @todo check who is allowed to do that (site admin and/or project admin)
     *
     * @param Integer $groupId Project ID
     * @param String  $userLogin User login name
     *
     * @return Boolean
     */
    public function addProjectMember($groupId, $userLogin) {
        $project = $this->projectManager->getProject($groupId);
        if ($project && !$project->isError()) {
            return $this->feedbackToSoapFault(account_add_user_to_group($groupId, $userLogin));
        } else {
            throw new SoapFault('3000', "Invalid project id");
        }
    }

    /**
     * Remove given user from project members
     *
     * @todo check who is allowed to do that (site admin and/or project admin)
     *
     * @param Integer $groupId Project ID
     * @param String  $userLogin User login name
     *
     * @return Boolean
     */
    public function removeProjectMember($groupId, $userLogin) {
        $user = $this->userManager->getUserByUserName($userLogin);
        if (!$user) {
            throw new SoapFault('3100', "Invalid user name");
        }
        if ($user->isMember($groupId)) {
            return $this->feedbackToSoapFault(account_remove_user_from_group($groupId, $user->getId()));
        } else {
            return true;
        }
    }

    /**
     * Transform errors from feedback errors into SoapFault
     *
     * @throws SoapFault
     * @param Boolean $result Result of initial command
     *
     * @return Boolean
     */
    protected function feedbackToSoapFault($result) {
        if (!$result) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                foreach ($GLOBALS['Response']->_feedback->logs as $log) {
                    if ($log['level'] == 'error') {
                        throw new SoapFault('3100', $log['msg']);
                    }
                }
            }
        }
        return $result;
    }

    /**
     *
     * @see session_continue
     * 
     * @param String $sessionKey
     * 
     * @return Boolean
     */
    private function continueSession($sessionKey) {
        $user = $this->userManager->getCurrentUser($sessionKey);
        return $user->isLoggedIn();
    }

}

?>