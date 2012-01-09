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
 
require_once 'common/project/ProjectManager.class.php';
require_once 'www/project/create_project.php';
require_once 'www/include/account.php';

class Project_SOAPServer {

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
     * @param String  $requesterLogin Login of the user on behalf of who you create the project
     * @param String  $shortName      Unix name of the project
     * @param String  $realName       Full name of the project
     * @param String  $privacy        Either 'public' or 'private'
     * @param Integer $templateId     Id of template project
     *
     * @return Integer The ID of newly created project
     */
    public function addProject($sessionKey, $requesterLogin, $shortName, $realName, $privacy="public", $templateId=100) {
        if (session_continue($sessionKey)) {
            /*
             $data['project']['form_unix_name']
             $data['project']['form_full_name']
             $data['project']['form_license']
             $data['project']['form_license_other']
             $data['project']['form_short_description']
             $data['project']['built_from_template']
             $data['project']['is_test']
             $data['project']['is_public']
             $data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]
             foreach($data['project']['trove'] as $root => $values);
             $data['project']['services'][$arr['service_id']]['is_used'];
             $data['project']['services'][$arr['service_id']]['server_id'];
             */
    
            $data = array();
    
            $user = UserManager::instance()->findUser($requesterLogin);
            if (!$user) {
                throw new SoapFault('3100', 'Invalid requester name');
            }
            $data['requester'] = $user;
    
            $rule = new Rule_ProjectName();
            if (!$rule->isValid($shortName)) {
                throw new SoapFault('3100', $rule->getErrorMessage());
            }
            $data['project']['form_unix_name'] = $shortName;
    
            //@TODO: add long name already exists check
            $rule = new Rule_ProjectFullName();
            if (!$rule->isValid($realName)) {
                throw new SoapFault('3100', $rule->getErrorMessage());
            }
            $data['project']['form_full_name'] = $realName;
    
            if ($privacy === 'public') {
                $data['project']['is_public'] = true;
            } else {
                $data['project']['is_public'] = false;
            }
    
            $template = ProjectManager::instance()->getProject($templateId);
            if ($template && !$template->isError()) {
                $data['project']['built_from_template'] = $template->getID();
            } else {
                throw new SoapFault('3000', 'Invalid template id '.$templateId);
            }
            
            $data['project']['form_license'] = 'xrx';
            $data['project']['form_license_other'] = '';
            $data['project']['form_short_description'] = '';
            $data['project']['is_test'] = false;
    
            $data['project']['services'] = array();
    
            $pm = ProjectManager::instance();
            $p = $pm->getProject($data['project']['built_from_template']);
            foreach($p->services as $key => $service) {
                if ($service->isActive() && $service->isUsed()) {
                    $data['project']['services'][$service->getId()]['is_used'] = true;
                } else {
                    $data['project']['services'][$service->getId()]['is_used'] = false;
                }
            }
    
    
            /*$data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]*/
            /*foreach($data['project']['trove'] as $root => $values);
             */
    
            $id = create_project($data, true);
            if ($id) {
                $project = $pm->getProject($id);
                return $pm->activate($project);
                //return $id;
            }
            throw new SoapFault('3100', 'Project creation failure');
        } else {
            throw new SoapFault('3001', 'Invalid session');
        }
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
        $project = ProjectManager::instance()->getProject($groupId);
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
        $user = UserManager::instance()->getUserByUserName($userLogin);
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
                foreach($GLOBALS['Response']->_feedback->logs as $log) {
                    if ($log['level'] == 'error') {
                        throw new SoapFault('3100', $log['msg']);
                    }
                }
            }
        }
        return $result;
    }
}
?>