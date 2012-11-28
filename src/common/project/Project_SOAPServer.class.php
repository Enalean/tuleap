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
require_once 'common/soap/SOAP_RequestLimitator.class.php';

/**
 * Wrapper for project related SOAP methods
 */
class Project_SOAPServer {
    const PRIVACY_PUBLIC  = 'public';
    const PRIVACY_PRIVATE = 'private';

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

    /**
     * @var SOAP_RequestLimitator 
     */
    private $limitator;

    public function __construct(ProjectManager $projectManager, ProjectCreator $projectCreator, UserManager $userManager, SOAP_RequestLimitator $limitator) {
        $this->projectManager = $projectManager;
        $this->projectCreator = $projectCreator;
        $this->userManager    = $userManager;
        $this->limitator      = $limitator;
    }

    /**
     * Create a new project
     *
     * This method throw an exception if there is a conflict on names or if there is an error during the creation process.
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
     * Error codes:
     * * 3001, Invalid session (wrong $sessionKey)
     * * 3200, Only site admin is allowed to create project on behalf of users (wrong $adminSessionKey)
     * * 3100, Invalid template id (correponding project doesn't exist)
     * * 3101, Project creation failure
     * * 3102, Invalid short name
     * * 3103, Invalid full name
     * * 3104, Project is not a template
     * * 4000, SOAP Call Quota exceeded (you created to much project during the last hour, according to configuration)
     * 
     * @param String  $sessionKey      Session key of the desired project admin
     * @param String  $adminSessionKey Session key of a site admin
     * @param String  $shortName       Unix name of the project
     * @param String  $publicName      Full name of the project
     * @param String  $privacy         Either 'public' or 'private'
     * @param Integer $templateId      Id of template project
     *
     * @return Integer The ID of newly created project
     */
    public function addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId) {
        $this->continueAdminSession($adminSessionKey);
        $requester = $this->continueSession($sessionKey);
        $template  = $this->getTemplateById($templateId, $requester);
        try {
            $this->limitator->logCallTo('addProject');
            return $this->formatDataAndCreateProject($shortName, $publicName, $privacy, $template);
        } catch (Exception $e) {
            throw new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }
    
    /**
     * Return a project the user is authorized to use as template
     * 
     * @param Integer $id
     * @param PFUser    $requester
     * 
     * @return Project
     */
    private function getTemplateById($id, PFUser $requester) {
        $project = $this->projectManager->getProject($id);
        if ($project && !$project->isError()) {
            if ($project->isTemplate() || $requester->isMember($project->getID(), 'A')) {
                return $project;
            }
            throw new SoapFault('3104', 'Project is not a template');
        }
        throw new SoapFault('3100', 'Invalid template id ' . $id);
    }
    
    /**
     * Ensure the given session key belong to an authenticated site admin
     * 
     * @param String  $adminSessionKey Session key of a site admin
     * 
     * @return PFUser
     */
    private function continueAdminSession($adminSessionKey) {
        $admin = $this->userManager->getCurrentUser($adminSessionKey);
        if ($admin && $admin->isLoggedIn() && $admin->isSuperUser()) {
            return $admin;
        }
        throw new SoapFault('3200', 'Only site admin is allowed to create project on behalf of users');
    }

    /**
     * Create the data array needed by create_project and create the project
     * 
     * @param String $shortName
     * @param String $publicName
     * @param String $privacy
     * @param Project $template
     * 
     * @return Integer
     */
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

        if ($privacy === self::PRIVACY_PUBLIC) {
            $data['project']['is_public'] = true;
        }

        foreach ($template->services as $key => $service) {
            $is_used = $service->isActive() && $service->isUsed();
            $data['project']['services'][$service->getId()]['is_used'] = $is_used;
        }

        $project = $this->projectCreator->create($shortName, $publicName, $data);
        $this->projectManager->activate($project);
        return $project->getID();
    }

    /**
     * Add given user as member of the project
     *
     * Error codes:
     * * 3000, Invalid project id
     * * 3201, Permission denied: need to be project admin
     *
     * @param String  $sessionKey The project admin session hash
     * @param Integer $groupId Project ID
     * @param String  $userLogin User login name
     *
     * @return Boolean
     */
    public function addProjectMember($sessionKey, $groupId, $userLogin) {
        $project = $this->getProjectIfUserIsAdmin($groupId, $sessionKey);
        $result  = account_add_user_to_group($project->getID(), $userLogin);
        return $this->returnFeedbackToSoapFault($result);
    }

    /**
     * Remove given user from project members
     *
     * Error codes:
     * * 3000, Invalid project id
     * * 3201, Permission denied: need to be project admin
     * * 3202, Invalid user login
     * * 3203, User not member of project
     * 
     * @param String  $sessionKey The project admin session hash
     * @param Integer $groupId Project ID
     * @param String  $userLogin User login name
     *
     * @return Boolean
     */
    public function removeProjectMember($sessionKey, $groupId, $userLogin) {
        $project   = $this->getProjectIfUserIsAdmin($groupId, $sessionKey);
        $userToAdd = $this->getProjectMember($project, $userLogin);
        $result    = account_remove_user_from_group($groupId, $userToAdd->getId());
        return $this->returnFeedbackToSoapFault($result);
    }

    /**
     * Add user to a User Group
     * 
     * * Error codes:
     *   * 3000, Invalid project id
     *   * 3201, Permission denied: need to be project admin
     *   * 3203, Invalid user id
     *   * 3301, User Group doesn't exist
     * 
     * @param String  $sessionKey The project admin session hash
     * @param Integer $groupId    The Project id where the User Group is defined
     * @param Integer $ugroupId   The User Group where the user should be added
     * @param Integer $userId     The user id to add
     * 
     * @return Boolean 
     */
    public function addUserToUGroup($sessionKey, $groupId, $ugroupId, $userId) {
        $this->getProjectIfUserIsAdmin($groupId, $sessionKey);
        if ($user = $this->userManager->getUserById($userId)) {
            try {
                $ugroup = new UGroup(array('ugroup_id' => $ugroupId, 'group_id' => $groupId));
                $ugroup->addUser($user);
            }  catch (Exception $e) {
                throw new SoapFault((string) $e->getCode(), $e->getMessage());
            }
            $this->feedbackToSoapFault();
            return true;
        } else {
            throw new SoapFault('3203', "Invalid user id $userId");
        }
    }
    
    /**
     * Remove User from User Group
     * 
     * * Error codes:
     *   * 3000, Invalid project id
     *   * 3201, Permission denied: need to be project admin
     *   * 3203, Invalid user id
     *   * 3301, User Group doesn't exist
     * 
     * @param String  $sessionKey The project admin session hash
     * @param Integer $groupId    The Project id where the User Group is defined
     * @param Integer $ugroupId   The User Group where the user should be removed
     * @param Integer $userId     The user id to remove
     * 
     * @return Boolean 
     */
    public function removeUserFromUGroup($sessionKey, $groupId, $ugroupId, $userId) {
        $this->getProjectIfUserIsAdmin($groupId, $sessionKey);
        if ($user = $this->userManager->getUserById($userId)) {
            try {
                $ugroup = new UGroup(array('ugroup_id' => $ugroupId, 'group_id' => $groupId));
                $ugroup->removeUser($user);
            }  catch (Exception $e) {
                throw new SoapFault((string) $e->getCode(), $e->getMessage());
            }
            $this->feedbackToSoapFault();
            return true;
        } else {
            throw new SoapFault('3203', "Invalid user id $userId");
        }
    }
    
    /**
     * Return a user member of project
     * 
     * @param Project $project
     * @param String  $userLogin
     * 
     * @return PFUser
     */
    private function getProjectMember(Project $project, $userLogin) {
        $user = $this->userManager->getUserByUserName($userLogin);
        if (!$user) {
            throw new SoapFault('3202', "Invalid user login");
        }
        if ($user->isMember($project->getID())) {
            return $user;
        }
        throw new SoapFault('3203', "User not member of project");
    }
    
    /**
     * Return a Project is the given user is authorized to administrate it
     * 
     * @param Integer $groupId
     * @param String  $sessionKey
     * 
     * @return Project
     */
    private function getProjectIfUserIsAdmin($groupId, $sessionKey) {
        $requester = $this->continueSession($sessionKey);
        $project   = $this->projectManager->getProject($groupId);
        if ($project && !$project->isError()) {
            if ($requester->isMember($project->getID(), 'A')) {
                return $project;
            }
            throw new SoapFault('3201', 'Permission denied: need to be project admin.');
        }
        throw new SoapFault('3000', "Invalid project id");
    }
    
    /**
     * Transform errors from feedback errors into SoapFault and return a boolean value accordingly
     *
     * @throws SoapFault
     * @param Boolean $result Result of initial command
     *
     * @return Boolean
     */
    private function returnFeedbackToSoapFault($result) {
        if (!$result) {
            $this->feedbackToSoapFault();
        }
        return $result;
    }
    
    /**
     * Transform errors from feedback errors into SoapFault
     *
     * @throws SoapFault
     */
    private function feedbackToSoapFault() {
        if ($GLOBALS['Response']->feedbackHasErrors()) {
            foreach ($GLOBALS['Response']->_feedback->logs as $log) {
                if ($log['level'] == 'error') {
                    throw new SoapFault('3100', $log['msg']);
                }
            }
        }
    }

    /**
     *
     * @see session_continue
     * 
     * @param String $sessionKey
     * 
     * @return PFUser
     */
    private function continueSession($sessionKey) {
        $user = $this->userManager->getCurrentUser($sessionKey);
        if ($user->isLoggedIn()) {
            return $user;
        }
        throw new SoapFault('3001', 'Invalid session');
    }

}

?>