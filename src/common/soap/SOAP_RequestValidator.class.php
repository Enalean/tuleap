<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once 'common/project/ProjectManager.class.php';
require_once 'common/user/UserManager.class.php';

/**
 * This class is a convenient proxy for parameters checking when doing SOAP server
 * 
 * It's meant to assert that given parameters are valid and returns corresponding
 * objects.
 * If there are errors, exception are thrown.
 * 
 * It manipulates:
 * - user session
 * - project
 */
class SOAP_RequestValidator implements \Tuleap\SOAP\SOAPRequestValidator
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(ProjectManager $project_manager, UserManager $user_manager) {
        $this->project_manager = $project_manager;
        $this->user_manager    = $user_manager;
    }
    
    /**
     *
     * @see session_continue
     * 
     * @param String $session_key
     * 
     * @return PFUser
     */
    public function continueSession($session_key) {
        $user = $this->user_manager->getCurrentUser($session_key);
        if ($user->isLoggedIn()) {
            return $user;
        }
        throw new Exception('Invalid session', '3001');
    }

    public function assertUserCanAccessProject(PFUser $user, Project $project) {
        if (($project->isPublic() && $user->isRestricted() && ! $user->isMember($project->getGroupId())) ||
            (! $project->isPublic() && ! $user->isMember($project->getGroupId()))) {
            throw new Exception('User do not have access to the project', '3002');
        }
    }
    
    public function getProjectById($project_id, $method_name) {
        return $this->project_manager->getGroupByIdForSoap($project_id, $method_name);
    }
    
    public function getProjectByName($project_name, $method_name) {
        return $this->project_manager->getGroupByIdForSoap($project_name, $method_name, true);
    }

}

?>
