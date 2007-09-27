<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 *
 * CvsToDimensions */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('CvsToDimensionsViews.class.php');
require_once('CvsToDimensionsActions.class.php');
require_once('common/dao/CodexDataAccess.class.php');
require_once('pre.php');
require_once('P26CDataAccess.class.php');

$GLOBALS['Language']->loadLanguageMsg('cvstodimensions', 'cvstodimensions');

class CvsToDimensions extends Controler {
	
	var $modules = array();
	var $tags = array();
	var $transferInProgress;
    var $plugin;
    
    function CvsToDimensions(&$plugin) {
    	$request =& HTTPRequest::instance();
        $group_id = $request->get('group_id'); 
    	session_require(array('group'=>$request->get('group_id'),'admin_flags'=>'A'));
    	$this->transferInProgress = false;
        $this->plugin =& $plugin;
        // Get group properties
        $res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
        $row_grp = db_fetch_array($res_grp);

        //get modules list of /cvsroot/unix_group_name
        $dir = '/cvsroot/'.$row_grp['unix_group_name'].'/';
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && $file != "CVS" && $file != "CVSROOT" && is_dir($dir.$file)) {
                    $this->modules[] = $file;
                }
            }
            closedir($handle);
        }

        //get tags from /CVSROOT/val-tags file
        if (file_exists($GLOBALS['cvs_prefix'].'/'.$row_grp['unix_group_name'].'/CVSROOT/val-tags')){
            $file = fopen($GLOBALS['cvs_prefix'].'/'.$row_grp['unix_group_name'].'/CVSROOT/val-tags', 'r');
            while(!feof($file)){
                $line = fgets($file, 4096);
                $line = trim($line);
                if($line != ""){
                    $this->tags[] = substr($line, 0, strpos($line, ' '));   
                }
            }
            fclose($file);
            //the project was created in CVS but there isn't any module
            if (count($this->modules)==0){
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_no_modules'));                
            }else if(count($this->tags)==0){
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_no_tags'));
            }
        }else{
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_no_cvs_project'));
        }
        
 
    }
    
    function getProperty($name) {
        $info =& $this->plugin->getPluginInfo();
        return $info->getPropertyValueForName($name);
    }
    
    function request() {
        $request =& HTTPRequest::instance();  
        switch($request->get('action')) {
        	case 'parameters':
        	   	$this->view = 'parameters';
        	   	break;
        	case 'saveParameters':
        		if (!$this->_checkParameters($request)){
        			$this->action = 'saveParameters';
        		}
        	   	$this->view = 'parameters';
        	   	break;
        	case 'transfer':
        		$this->_isTransferPossible($request->get('group_id'));
        		$this->view = 'transfer';
        		break;
        	case 'doTransfer':
        		$this->_isTransferPossible($request->get('group_id'));
        		if(!$this->_checkParametersForTransfert($request) && $this->transferInProgress==false){
        			if(!$this->_isReloadPage($request)){
        				$this->action = 'doTransfer';
        			}
        		}
        		$this->view = 'transfer';
        		break;
        	case 'historic':
        		$this->view = 'historic';
        	   	break;
        	default:
        	   $this->view = 'intro';
        	   break;
        }
    }
    
    /** 
     *  Check parameters fields 
     *  @return the number of log messages
     */
    function _checkParameters($request){
        $error = false;
    	if(empty($_POST['product'])){
            $error = true;    	    
        	$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_cvstodimensions','parameters_product')));
        }
        if(empty($_POST['database'])){
        	$error = true;
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_cvstodimensions','parameters_database')));
        }
        $module_index = 0;
        foreach($this->modules as $module){
        	$module_index ++;
        	if(empty($_POST['module_'.$module_index])){
        		$error = true;
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_missing_field',$module));
        	}
        } 
        return $error;
    }
    
    /** 
     *  Check parameters fields and transfer fields
     *  @return the number of log messages
     */
    function _checkParametersForTransfert($request){
		$group_id = $request->get('group_id');    	
    	$parameters_dao = new PluginCvstodimensionsParametersDao(CodexDataAccess::instance());
    	$parameters_results =& $parameters_dao->searchByGroupId($group_id);
        $error = false;
    	if(!$row = $parameters_results->getRow()){
            $error = true;    	    
    		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_cvstodimensions','parameters_product')));
    		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_cvstodimensions','parameters_database')));
    	}
    	$modules_dao = new PluginCvstodimensionsModulesDao(CodexDataAccess::instance());
    	$modules_results =& $modules_dao->searchByGroupId($group_id);
    	$modules_cvs_db = array();
    	while ($row = $modules_results->getRow()){
    		$modules_cvs_db[] = $row['module'];
    	}
    	foreach($this->modules as $module){
    		if(!in_array($module, $modules_cvs_db)){
                $error = true;    		    
    			$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_transfer_missing_field', $module));
    		}
    	}
    	if(empty($_POST['password'])){
            $error = true;     	    
    		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_cvstodimensions','transfer_password')));
    	}
    	return $error;
    }
    
    /**
     * set the global variable transferInProgress to true if there is some log with inprogress state
     */
    function _isTransferPossible($group_id){
    	$logs_dao = new PluginCvstodimensionsLogDao(CodexDataAccess::instance());
    	$logs_result =& $logs_dao->searchByStateAndGroupId($group_id, '1');
    	if($logs_result->rowCount()!=0){
    		$GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_transfer_wait'));
    		$this->transferInProgress = true;
    	}
    }
    
    /**
     * check if the tag to transfer is already in the log table with success state
     * 
     */
    function _isReloadPage($request){
    	$group_id = $request->get('group_id');
    	$logs_dao = new PluginCvstodimensionsLogDao(CodexDataAccess::instance());
    	//check if the tag has already been transfered
    	$logs_result =& $logs_dao->searchByGroupIdTagAndState($group_id, $_POST['tag'],0);
    	if($logs_result->rowCount()==0){
    		return false;
    	}else{
    		$GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_transfer_alreadyDone'));
    		return true;
    	}
    }
    
    function parseGoRoCo($tag, &$version_tag){
        $version_tag['GO'] = substr($tag, strpos($tag, 'G')+1, strpos($tag, 'R')-strpos($tag, 'G')-1);
        $version_tag['RO'] = substr($tag, strpos($tag, 'R')+1, strpos($tag, 'C')-strpos($tag, 'R')-1);
        $version_tag['CO'] = substr($tag, strpos($tag, 'C')+1);
    }
    
}

?>
