<?php

require_once 'pre.php';
require_once 'common/project/ProjectManager.class.php';
require_once 'www/project/create_project.php';
require_once 'www/include/account.php';

class SoapProjectManager {

    /**
     * Create a new project
     *
     * This method throw an exception if there is a conflict on names or
     * it there is an error during the creation process.
     * It assumes a couple of things:
     * * The project type is "Project" (Not modifiable)
     * * The template is the default one (project id 100).
     * * There is no "Project description" nor any "Project description
     * * fields" (long desc, patents, IP, other software)
     * * The project services are inherited from the template
     * * There is no trove cat selected
     * * The default Software Policy is "Site exchange policy".
     *
     * Projects are automatically accepted
     *
     * @param String $requesterLogin Login of the user on behalf of who you create the project
     * @param String $shortName      Unix name of the project
     * @param String $realName       Full name of the project
     * @param String $privacy        Either 'public' or 'private'
     *
     * @return Integer The ID of newly created project
     */
    function addProject($requesterLogin, $shortName, $realName, $privacy="public") {
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

        $user = UserManager::instance()->getUserByUserName($requesterLogin);
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


        $data['project']['form_license'] = 'xrx';
        $data['project']['form_license_other'] = '';
        $data['project']['form_short_description'] = '';
        $data['project']['built_from_template'] = 100;
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
        throw new SoapFault('Project creation failure');
    }

    /**
     */
    function addProjectMember($groupId, $userLogin) {
        $user = UserManager::instance()->getUserByUserName($userLogin);
        if (!$user->isMember($groupId)) {
            $res = account_add_user_to_group($groupId, $userLogin);
            if (!$res) {
                if ($GLOBALS['Response']->feedbackHasErrors()) {
                    foreach($GLOBALS['Response']->_feedback->logs as $log) {
                        if ($log['level'] == 'error') {
                            throw new SoapFault('3100', $log['msg']);
                        }
                    }
                }
            }
            return $res;
        } else {
            return true;
        }
    }

}

$server = new SoapServer(null, array('uri' => "http://localhost:3080/soap2/", 'cache_wsdl' => WSDL_CACHE_NONE));

$server->setClass('SoapProjectManager');

$server->handle();

?>