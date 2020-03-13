<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../www/include/account.php';
require_once __DIR__ .  '/../../www/include/utils_soap.php';

use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\Template\InsufficientPermissionToUseProjectAsTemplateException;
use Tuleap\Project\Registration\Template\ProjectTemplateIDInvalidException;
use Tuleap\Project\Registration\Template\ProjectTemplateNotActiveException;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;

/**
 * Wrapper for project related SOAP methods
 */
class Project_SOAPServer // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{

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

    /**
     * @var GenericUserFactory
     */
    private $generic_user_factory;

    /** @var Project_CustomDescription_CustomDescriptionFactory */
    private $description_factory;

    /** @var Project_CustomDescription_CustomDescriptionValueManager */
    private $description_manager;

    /** @var Project_CustomDescription_CustomDescriptionValueFactory */
    private $description_value_factory;

    /** @var Project_Service_ServiceUsageFactory */
    private $service_usage_factory;

    /** @var Project_Service_ServiceUsageManager */
    private $service_usage_manager;

    /** @var User_ForgeUserGroupPermissionsManager */
    private $forge_ugroup_permissions_manager;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $project_registration_user_permission_checker;

    public function __construct(
        ProjectManager $projectManager,
        ProjectCreator $projectCreator,
        UserManager $userManager,
        GenericUserFactory $generic_user_factory,
        SOAP_RequestLimitator $limitator,
        Project_CustomDescription_CustomDescriptionFactory $description_factory,
        Project_CustomDescription_CustomDescriptionValueManager $description_manager,
        Project_CustomDescription_CustomDescriptionValueFactory $description_value_factory,
        Project_Service_ServiceUsageFactory $service_usage_factory,
        Project_Service_ServiceUsageManager $service_usage_manager,
        User_ForgeUserGroupPermissionsManager $forge_ugroup_permissions_manager,
        ProjectRegistrationUserPermissionChecker $project_registration_user_permission_checker
    ) {
        $this->projectManager                   = $projectManager;
        $this->projectCreator                   = $projectCreator;
        $this->userManager                      = $userManager;
        $this->generic_user_factory             = $generic_user_factory;
        $this->limitator                        = $limitator;
        $this->description_factory              = $description_factory;
        $this->description_manager              = $description_manager;
        $this->description_value_factory        = $description_value_factory;
        $this->service_usage_factory            = $service_usage_factory;
        $this->service_usage_manager            = $service_usage_manager;
        $this->forge_ugroup_permissions_manager = $forge_ugroup_permissions_manager;
        $this->project_registration_user_permission_checker = $project_registration_user_permission_checker;
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
     * * 3105, Generic User creation failure
     * * 4000, SOAP Call Quota exceeded (you created to much project during the last hour, according to configuration)
     *
     * @param String  $sessionKey      Session key of the desired project admin
     * @param String  $adminSessionKey Session key of a site admin
     * @param String  $shortName       Unix name of the project
     * @param String  $publicName      Full name of the project
     * @param String  $privacy         Either 'public' or 'private'
     * @param int $templateId Id of template project
     *
     * @return int The ID of newly created project
     */
    public function addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId)
    {
        $requester          = $this->continueSession($sessionKey);
        $has_special_access = $this->doesUserHavePermission(
            $requester,
            new User_ForgeUserGroupPermission_ProjectApproval()
        );

        if (! $has_special_access) {
            $this->checkAdminSessionIsValid($adminSessionKey, $sessionKey);
        }

        try {
            $this->project_registration_user_permission_checker->checkUserCreateAProject($requester);

            $template = $this->getTemplateForProjectCreationById($templateId, $requester);

            $this->limitator->logCallTo('addProject');
            return $this->formatDataAndCreateProject($shortName, $publicName, $privacy, $template);
        } catch (Exception $e) {
            throw new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * @return bool
     */
    private function doesUserHavePermission(PFUser $user, User_ForgeUserGroupPermission $permission)
    {
        return $this->forge_ugroup_permissions_manager->doesUserHavePermission(
            $user,
            $permission
        );
    }

    private function getTemplateForProjectCreationById(int $project_id, PFUser $requester): TemplateFromProjectForCreation
    {
        try {
            return TemplateFromProjectForCreation::fromSOAPServer($project_id, $requester, $this->projectManager);
        } catch (ProjectTemplateIDInvalidException $exception) {
            throw new SoapFault('3100', 'Invalid template id ' . $project_id);
        } catch (ProjectTemplateNotActiveException | InsufficientPermissionToUseProjectAsTemplateException $ex) {
            throw new SoapFault('3104', 'Project is not a template');
        }
    }

    /**
     * Ensure the given session key belong to an authenticated site admin
     *
     * @param String  $adminSessionKey Session key of a site admin
     *
     * @return PFUser
     */
    private function checkAdminSessionIsValid($adminSessionKey, $sessionKey)
    {
        $admin = $this->userManager->getCurrentUser($adminSessionKey);
        if ($admin && $admin->isLoggedIn() && $admin->isSuperUser()) {
            $this->continueSession($sessionKey);
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
     *
     * @return int
     */
    private function formatDataAndCreateProject($shortName, $publicName, $privacy, TemplateFromProjectForCreation $template_for_project_creation)
    {
        $data = array(
            'project' => array(
                'form_short_description' => '',
                'is_test'                => false,
                'is_public'              => false,
                'services'               => array(),
            )
        );

        if ($privacy === Project::ACCESS_PUBLIC) {
            $data['project']['is_public'] = true;
        }

        foreach ($template_for_project_creation->getProject()->getServices() as $key => $service) {
            $is_used = $service->isActive() && $service->isUsed();
            $data['project']['services'][$service->getId()]['is_used'] = $is_used;
        }

        $project = $this->projectCreator->create($shortName, $publicName, $template_for_project_creation, $data);
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
     * @param int $groupId Project ID
     * @param String  $userLogin User login name
     *
     * @return bool
     */
    public function addProjectMember($sessionKey, $groupId, $userLogin)
    {
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
     * @param int $groupId Project ID
     * @param String  $userLogin User login name
     *
     * @return bool
     */
    public function removeProjectMember($sessionKey, $groupId, $userLogin)
    {
        $project      = $this->getProjectIfUserIsAdmin($groupId, $sessionKey);
        $userToAdd    = $this->getProjectMember($project, $userLogin);
        $user_removal = new UserRemover(
            ProjectManager::instance(),
            EventManager::instance(),
            new ArtifactTypeFactory(false),
            new UserRemoverDao(),
            UserManager::instance(),
            new ProjectHistoryDao(),
            new UGroupManager()
        );

        $result = $user_removal->removeUserFromProject($groupId, $userToAdd->getId());

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
     * @param int $groupId The Project id where the User Group is defined
     * @param int $ugroupId The User Group where the user should be added
     * @param int $userId The user id to add
     *
     * @return bool
     */
    public function addUserToUGroup($sessionKey, $groupId, $ugroupId, $userId)
    {
        $this->getProjectIfUserIsAdmin($groupId, $sessionKey);
        if ($user = $this->userManager->getUserById($userId)) {
            try {
                $ugroup = new ProjectUGroup(array('ugroup_id' => $ugroupId, 'group_id' => $groupId));
                $ugroup->addUser($user);
            } catch (Exception $e) {
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
     * @param int $groupId The Project id where the User Group is defined
     * @param int $ugroupId The User Group where the user should be removed
     * @param int $userId The user id to remove
     *
     * @return bool
     */
    public function removeUserFromUGroup($sessionKey, $groupId, $ugroupId, $userId)
    {
        $this->getProjectIfUserIsAdmin($groupId, $sessionKey);
        if ($user = $this->userManager->getUserById($userId)) {
            try {
                $ugroup = new ProjectUGroup(array('ugroup_id' => $ugroupId, 'group_id' => $groupId));
                $ugroup->removeUser($user);
            } catch (Exception $e) {
                throw new SoapFault((string) $e->getCode(), $e->getMessage());
            }
            $this->feedbackToSoapFault();
            return true;
        } else {
            throw new SoapFault('3203', "Invalid user id $userId");
        }
    }

    /**
     * Create a generic user
     *
     * @param String  $session_key The project admin session hash
     * @param int $group_id The Project id where the User Group is defined
     * @param String  $password    The password of the generic user about to be created
     *
     * @return UserInfo
     */
    public function setProjectGenericUser($session_key, $group_id, $password)
    {
        if (! $this->isRequesterAdmin($session_key, $group_id)) {
            throw new SoapFault('3201', 'Permission denied: need to be project admin.');
        }
        $user = $this->generic_user_factory->fetch($group_id);

        if (! $user) {
            $user = $this->generic_user_factory->create($group_id, $password);
            if (! $user) {
                throw new SoapFault('3105', "Generic User creation failure");
            }
        } else {
            $user->setPassword($password);
            $this->generic_user_factory->update($user);
        }

        $this->addGenericUserInProject($user, $session_key, $group_id);
        return user_to_soap($user->getId(), $user, $this->userManager->getCurrentUser());
    }

    private function addGenericUserInProject(PFUser $user, $session_key, $group_id)
    {
        if (! $user->isMember($group_id)) {
            $this->addProjectMember($session_key, $group_id, $user->getUnixName());
        }
    }
    /**
     *
     * @param String  $session_key  The project admin session hash
     * @param int $group_id The Project id where the Generic user is
     */
    public function unsetGenericUser($session_key, $group_id)
    {
        if (! $this->isRequesterAdmin($session_key, $group_id)) {
            throw new SoapFault('3201', 'Permission denied: need to be project admin.');
        }

        $user = $this->generic_user_factory->fetch($group_id);
        if (! $user) {
            throw new SoapFault('3300', "Generic User is not created for this project");
        }
        $this->removeProjectMember($session_key, $group_id, $user->getUserName());
    }

    /**
     * Get a generic user
     *
     * @param String  $sessionKey The project admin session hash
     * @param int $groupId The Project id where the User Group is defined
     *
     * @return UserInfo
     */
    public function getProjectGenericUser($sessionKey, $groupId)
    {
        if (! $this->isRequesterAdmin($sessionKey, $groupId)) {
            throw new SoapFault('3201', 'Permission denied: need to be project admin.');
        }

        $user = $this->generic_user_factory->fetch($groupId);

        if (! $user) {
            throw new SoapFault('3106', "Generic User does not exist");
        }
        return user_to_soap($user->getId(), $user, $this->userManager->getCurrentUser());
    }

    /**
     * Get all the description fields
     *
     * * Error codes:
     *   * 3107, No custom project description fields
     *
     * @param String  $sessionKey The project admin session hash
     *
     * @return ArrayOfDescFields
     */
    public function getPlateformProjectDescriptionFields($sessionKey)
    {
        $this->continueSession($sessionKey);
        $project_desc_fields = $this->description_factory->getCustomDescriptions();
        $soap_return = array();
        if (empty($project_desc_fields)) {
                throw new SoapFault('3107', "No custom project description fields");
        }
        foreach ($project_desc_fields as $desc_field) {
             $soap_return[] = $this->extractDescFieldSOAPDatas($desc_field);
        }
        return $soap_return;
    }

    private function extractDescFieldSOAPDatas(Project_CustomDescription_CustomDescription $desc_field)
    {
        $field_datas = array();
        $field_datas['id']           = $desc_field->getId();
        $field_datas['name']         = $desc_field->getName();
        $field_datas['is_mandatory'] = $desc_field->isRequired();
        return $field_datas;
    }

    /**
     * Set description fields
     *
     * * Error codes:
     *   * 3000, Invalid project id
     *   * 3108, The given project description field does not exist
     *   * 3201, Permission denied: need to be project admin
     *
     * @param String  $session_key        The project admin session hash
     * @param int     $group_id           The Id of the project
     * @param int     $field_id_to_update The Id of the field
     * @param String  $field_value        The new value to set
     *
     */
    public function setProjectDescriptionFieldValue($session_key, $group_id, $field_id_to_update, $field_value)
    {
        $project = $this->getProjectIfUserIsAdmin($group_id, $session_key);

        if (! $this->descriptionFieldExists($field_id_to_update)) {
            throw new SoapFault('3108', "The given project description field does not exist");
        }

        $this->description_manager->setCustomDescription($project, $field_id_to_update, $field_value);
    }

    private function descriptionFieldExists($field_id_to_update)
    {
        $project_desc_fields = $this->description_factory->getCustomDescription($field_id_to_update);
        if ($project_desc_fields) {
            return true;
        }

        return false;
    }

    /**
     * get all the description fields value for a
     * given project
     *
     * * Error codes:
     *   * 3000, Invalid project id
     *   * 3203, Permission denied: need to be project admin
     *
     * @param String  $session_key        The project admin session hash
     * @param int     $group_id           The Id of the project
     *
     * @return ArrayOfDescFieldsValues
     */
    public function getProjectDescriptionFieldsValue($session_key, $group_id)
    {
        $project = $this->projectManager->getProject($group_id);

        if (! $project || $project->isError()) {
             throw new SoapFault('3000', "Invalid project id");
        }

        $user      = $this->continueSession($session_key);
        $is_member = $this->getProjectMember($project, $user->getUserName());

        if (! $is_member) {
            throw new SoapFault('3203', 'Permission denied: need to be project admin');
        }

        return $this->description_value_factory->getDescriptionFieldsValue($project);
    }

    /**
     * get all the services uage value for a
     * given project
     *
     * * Error codes:
     *   * 3000, Invalid project id
     *   * 3203, Permission denied: need to be project admin
     *
     * @param String  $session_key        The project admin session hash
     * @param int     $group_id           The Id of the project
     *
     * @return ArrayOfServicesValues
     */
    public function getProjectServicesUsage($session_key, $group_id)
    {
        $project         = $this->getProjectIfUserIsAdmin($group_id, $session_key);
        $soap_return     = array();
        $services_usages = $this->service_usage_factory->getAllServicesUsage($project);

        foreach ($services_usages as $services_usage) {
             $soap_return[] = $this->extractServicesUsageSOAPDatas($services_usage);
        }
        return $soap_return;
    }

    private function extractServicesUsageSOAPDatas(Project_Service_ServiceUsage $service_usage)
    {
        $field_datas = array();
        $field_datas['id']         = $service_usage->getId();
        $field_datas['short_name'] = $service_usage->getShortName();
        $field_datas['is_used']    = (int) $service_usage->isUsed();
        return $field_datas;
    }

    /**
     * Activate a service in a given project
     *
     * * Error codes:
     *   * 3000, Invalid project id
     *   * 3019, The service does not exist
     *   * 3203, Permission denied: need to be project admin
     *
     * @param String  $session_key        The project admin session hash
     * @param int     $group_id           The Id of the project
     * @param int     $service_id         The Id of the service
     *
     * @return bool
     */
    public function activateService($session_key, $group_id, $service_id)
    {
        $project = $this->getProjectIfUserIsAdmin($group_id, $session_key);
        $service = $this->service_usage_factory->getServiceUsage($project, $service_id);

        if (! $service) {
            throw new SoapFault('3019', "The service does not exist");
        }

        return $this->service_usage_manager->activateService($project, $service);
    }

    /**
     * Deactivate a service in a given project
     *
     * * Error codes:
     *   * 3000, Invalid project id
     *   * 3019, The service does not exist
     *   * 3203, Permission denied: need to be project admin
     *
     * @param String  $session_key        The project admin session hash
     * @param int     $group_id           The Id of the project
     * @param int     $service_id         The Id of the service
     *
     * @return bool
     */
    public function deactivateService($session_key, $group_id, $service_id)
    {
        $project = $this->getProjectIfUserIsAdmin($group_id, $session_key);
        $service = $this->service_usage_factory->getServiceUsage($project, $service_id);

        if (! $service) {
            throw new SoapFault('3019', "The service does not exist");
        }

        return $this->service_usage_manager->deactivateService($project, $service);
    }

    /**
     * Return a user member of project
     *
     * @param String  $userLogin
     *
     * @return PFUser
     */
    private function getProjectMember(Project $project, $userLogin)
    {
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
     * @param int $groupId
     * @param String  $sessionKey
     *
     * @return Project
     */
    private function getProjectIfUserIsAdmin($groupId, $sessionKey)
    {
        $project   = $this->projectManager->getProject($groupId);
        if ($project && !$project->isError()) {
            if ($this->isRequesterAdmin($sessionKey, $project->getID())) {
                return $project;
            }
            throw new SoapFault('3201', 'Permission denied: need to be project admin.');
        }
        throw new SoapFault('3000', "Invalid project id");
    }

    protected function isRequesterAdmin($sessionKey, $project_id)
    {
        $requester = $this->continueSession($sessionKey);

        return $requester->isMember($project_id, 'A');
    }

    /**
     * Transform errors from feedback errors into SoapFault and return a boolean value accordingly
     *
     * @throws SoapFault
     * @param bool $result Result of initial command
     *
     * @return bool
     */
    private function returnFeedbackToSoapFault($result)
    {
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
    private function feedbackToSoapFault()
    {
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
    private function continueSession($sessionKey)
    {
        $user = $this->userManager->getCurrentUser($sessionKey);
        if ($user->isLoggedIn()) {
            return $user;
        }
        throw new SoapFault('3001', 'Invalid session');
    }
}
