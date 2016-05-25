<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Default_Login extends CLI_Action {
    function __construct() {
        parent::__construct('login', 'Log into Codendi server');
        $this->addParam(array(
            'name'           => 'loginname',
            'description'    => '--username=<username> or -U <username>    Specify the user name',
            'parameters'     => array('username', 'U'),
        ));
        $this->addParam(array(
            'name'           => 'passwd',
            'description'    => '--password=<password> or -p <password>    Specify the password. If none is entered, it
      will be asked (note that this is the UNIX name of the project)',
            'parameters'     => array('password', 'p'),
        ));
        $this->addParam(array(
            'name'           => 'projectname',
            'description'    => '--project=<projectname>                   (Optional) Select a project to work on',
            'parameters'     => array('project'),
            'soap'           => false
        ));
        $this->addParam(array(
            'name'           => 'host',
            'description'    => '--host=<hostname>                         (Optional) domain of the server: www.example.com',
            'soap'           => false
        ));
        $this->addParam(array(
            'name'           => 'proxy',
            'description'    => '--proxy=<proxy_host:proxy_port>           (Optional) proxy: for instance myproxy:8008',
            'soap'           => false
        ));
        $this->addParam(array(
            'name'           => 'secure',
            'description'    => '--secure or -s                            (Optional) use https',
            'parameters'     => array('secure', 's'),
            'value_required' => false,
            'soap'           => false,
        ));
    }

    function addProjectParam() {
    }
    function validate_loginname(&$loginname) {
        // If no username is specified, use the system user name
        if (!$loginname) {
            if (array_key_exists("USER", $_ENV)) {
                $loginname = $_ENV["USER"];
            } else {
                exit_error("You must specify the user name with the --username parameter");
            }
        }
        return true;
    }
    function validate_passwd(&$passwd) {
        // If no password is specified, ask for it
        if (!$passwd) {
            $passwd = $this->module->get_user_input("Password: ", true);
        }
        return true;
    }
    function before_soapCall(&$loaded_params) {
    	$GLOBALS['soap']->endSession();

        if (isset($loaded_params['others']['host'])) {
            if (isset($loaded_params['others']['host']) && $loaded_params['others']['secure']) {
                $protocol = "https";
            } else {
                $protocol = "http";
            }
            $GLOBALS['soap']->setWSDLString($protocol."://".$loaded_params['others']['host']."/soap/codendi.wsdl.php?wsdl");
        }
    	if (isset($loaded_params['others']['proxy'])) {
    		$proxy = $loaded_params['others']['proxy'];
            $GLOBALS['soap']->setProxy($proxy);
        }

    }
	function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {
        if (!$loaded_params['others']['quiet']) $this->show_output($soap_result);
        $session_string = $soap_result->session_hash;
        $user_id = $soap_result->user_id;
        $GLOBALS['LOG']->add("Logged in as user ".$loaded_params['soap']['loginname']." (user_id=".$user_id."), using session string ".$session_string);
        if (!$loaded_params['others']['quiet']) echo "Logged in.\n";
        $GLOBALS['soap']->setSessionString($session_string);
        $GLOBALS['soap']->setSessionUser($loaded_params['soap']['loginname']);
        $GLOBALS['soap']->setSessionUserID($user_id);

        // If project was specified, get project information and store for future use
        if (isset($loaded_params['others']['projectname'])) {
            $group_id = $this->get_group_id($loaded_params['others']['projectname']);
            if (!$group_id) {
                exit_error('Project "'.$loaded_params['others']['projectname'].'" doesn\'t exist');
            }

            $GLOBALS['soap']->setSessionGroupID($group_id);
            $GLOBALS['LOG']->add("Using group #".$group_id);
        }

        $GLOBALS['soap']->saveSession();
    }

    function use_extra_params() {
        return false;
    }

}
