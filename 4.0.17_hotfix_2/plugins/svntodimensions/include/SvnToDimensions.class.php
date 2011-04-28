<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 *
 * SvnToDimensions */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('SvnToDimensionsViews.class.php');
require_once('SvnToDimensionsActions.class.php');
require_once('common/dao/CodendiDataAccess.class.php');
require_once('pre.php');
require_once('P26CDataAccess.class.php');

class SvnToDimensions extends Controler {
	
	var $modules = array();
	var $tags = array();
        var $pl = array();
	var $transferInProgress;
        var $plugin;
    
    function SvnToDimensions(&$plugin) {
    	$request =& HTTPRequest::instance();
        $group_id = $request->get('group_id'); 
    	session_require(array('group'=>$request->get('group_id'),'admin_flags'=>'A'));
    	$this->transferInProgress = false;
        $this->plugin =& $plugin;
        // Get group properties
        $res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
        $row_grp = db_fetch_array($res_grp);
        //export project svnroot/unix_group_name
        if (is_dir($GLOBALS['svn_prefix'].'/'.$row_grp['unix_group_name'].'/')){
              
            $tmp_dir = $this->getProperty('temp_dir');
             
            exec('cd ' . $tmp_dir . ';rm -rf '.$row_grp['unix_group_name'].'; svn export file:///svnroot/'.$row_grp['unix_group_name']);         
            $this->_getPLTags($group_id);            
             $this->_fillPL($group_id);	   
        }else{
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_no_svn_project'));
        }
        
 
    }
    
    function _getPLTags($group_id){
        $tmp_dir = $this->getProperty('temp_dir');
        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        $short_name = $group->getUnixName(false);
        $folder = $tmp_dir.'/'.$short_name;
        $logs_dao = new PluginSvntodimensionsLogDao(CodendiDataAccess::instance());
        $logs_result =& $logs_dao->searchByStateAndGroupId($group_id, '0');        
        $transfered_tags = array();
        if($logs_result->rowCount() >= 1){
            $transfered_tags = $this->_resultset_to_array($logs_result, "tag");  
        }
        exec('cd '.$folder.'; find . -regextype "posix-extended" -regex ".*tags" -type d > '.$folder.'/tags_folders_list.txt');
        if (file_exists($folder.'/tags_folders_list.txt')){
            $file = fopen($folder.'/tags_folders_list.txt', 'r');
            while(!feof($file)){
                $line = fgets($file, 4096);
                $line = trim($line);
                if($line != ""){
                    $tag_folder = substr($line, 0);
                    exec('cd '.$folder.';cd '.$tag_folder.'; find . -regextype "posix-extended" -regex ".*G[0-9]{1,2}R[0-9]{1,2}C[0-9]{1,2}[^/]*" -type d >> '.$folder.'/tags_list.txt ; ');
                }
            }
            fclose($file);
            unlink($folder.'/tags_folders_list.txt');
            if (file_exists($folder.'/tags_list.txt')){
                $file = fopen($folder.'/tags_list.txt', 'r');
                while(!feof($file)){
                    $line = fgets($file, 4096);
                    $line = trim($line);
                    if($line != ""){
                        $tag = substr(strrchr($line, "/"),1);
                        if(!in_array($tag, $transfered_tags)){
                            $this->tags[] = $tag;
                        }
                    }
                }
                fclose($file);
                unlink($folder.'/tags_list.txt');
            }
        }
    }
    
    function getProperty($name) {
        $info =& $this->plugin->getPluginInfo();
        return $info->getPropertyValueForName($name);
    }
    
    function request() {
        $request =& HTTPRequest::instance();  
        switch($request->get('action')) {
        	case 'refreshTags':
        		if (!$this->_checkParameters($request)){
        			$this->action = 'saveParameters';
        		}
        	   	$this->view = 'transfer';
        	   	break;
        	case 'transfer':
        		$this->_isTransferPossible($request->get('group_id'));
        		//$this->_fillPL($request->get('group_id'));
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
        	$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_svntodimensions','parameters_product')));
        }
        if(empty($_POST['database'])){
        	$error = true;
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_svntodimensions','parameters_database')));
        }
        $module_index = 0;
        foreach($this->modules as $module){
        	$module_index ++;
        	if(empty($_POST['module_'.$module_index])){
        		$error = true;
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_missing_field',$module));
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
    	$parameters_dao = new PluginSvntodimensionsParametersDao(CodendiDataAccess::instance());
    	$parameters_results =& $parameters_dao->searchByGroupId($group_id);
        $error = false;
    	if(!$row = $parameters_results->getRow()){
            $error = true;    	    
    		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_svntodimensions','parameters_product')));
    		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_svntodimensions','parameters_database')));
    	}

    	if(empty($_POST['password'])){
            $error = true;     	    
    		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_missing_field', $GLOBALS['Language']->getText('plugin_svntodimensions','transfer_password')));
    	}
    	return $error;
    }
    
    /**
     * set the global variable transferInProgress to true if there is some log with inprogress state
     */
    function _isTransferPossible($group_id){
    	$logs_dao = new PluginSvntodimensionsLogDao(CodendiDataAccess::instance());
    	$logs_result =& $logs_dao->searchByStateAndGroupId($group_id, '1');
    	if($logs_result->rowCount()!=0){
    		$GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_transfer_wait', $tag));
    		$this->transferInProgress = true;
    	}
    }

    function _fillPL($group_id){ 
      $parameters_dao = new PluginSvntodimensionsParametersDao(CodendiDataAccess::instance());
       $parameters_results =& $parameters_dao->searchByGroupId($group_id);
       if($row = $parameters_results->getRow()){
            $p26c_dao = new PluginSvntodimensionsP26CDao(P26CDataAccess :: instance($row['dimensions_db'], $this));
            if ($p26c_dao->da->db!=null && $p26c_dao->da->db!=0) {
                $product_name = $row['product'];
                $product = & $p26c_dao->searchProductByName($product_name);
                if ($product->rowCount() >= 1) {                    
                    $dp_result =  & $p26c_dao->searchDesignPartsByProduct($product_name);
                    if($dp_result->rowCount()>1){
                        $this->pl = $this->_resultset_to_array($dp_result, "PART_ID", $product_name);
                    }else{
                        $this->pl = array();
                    }

                }
            }
       }     
    }  


    function _resultset_to_array($resultset, $col_name, $exception=null) {
        $result_array = array (); 
        while ($resultset->valid()) {
            $row = $resultset->current();
            if (!is_array($col_name)) {
                if($exception==null || $exception!=$row[$col_name]) 
                    $result_array[] = $row[$col_name];
            } else {
                $current_row = array ();
                foreach ($col_name as $col) {
                    $current_row[$col] = $row[$col];
                }
                $result_array[] = $current_row;
            }

            $resultset->next();
        }
        return $result_array;
    }


    /**
     * check if the tag to transfer is already in the log table with success state
     * 
     */
    function _isReloadPage($request){
    	$group_id = $request->get('group_id');
    	$logs_dao = new PluginSvntodimensionsLogDao(CodendiDataAccess::instance());
    	//check if the tag has already been transfered
    	$logs_result =& $logs_dao->searchByGroupIdTagAndState($group_id, $_POST['tag'],0);
    	if($logs_result->rowCount()==0){
    		return false;
    	}else{
    		$GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_transfer_alreadyDone', $tag));
    		return true;
    	}
    }
    
    function parseGoRoCo($tag, &$version_tag){
        $find_goroco = false;
        while (!$find_goroco && strlen($tag)>0) {
        	//serach the first possible tag GoRoCo
            $postion_g = strpos($tag, 'G');
            $postion_r = strpos(substr($tag, $postion_g), 'R')+$postion_g;
            $postion_c = strpos(substr($tag, $postion_r), 'C')+$postion_r;
            
            if(($postion_r-$postion_g)<4 && ($postion_c-$postion_r)<4){
                $version_tag['GO'] = substr($tag, $postion_g+1, $postion_r-$postion_g-1);
                $version_tag['RO'] = substr($tag, $postion_r+1, $postion_c-$postion_r-1);
                $version_tag['CO'] = substr($tag, $postion_c+1, 2);
            }
            //check if it's a valid tag, with numeric value'
            if(is_numeric($version_tag['GO']) && is_numeric($version_tag['RO'])){
                    if(!is_numeric($version_tag['CO'])){
                        $version_tag['CO'] = $version_tag['CO']{0};
                        if(is_numeric($version_tag['CO'])){
                            $find_goroco = true;
                        }
                    }else{
                        $find_goroco = true;
                    }
            }
            //find the next possible tag
            if(!$find_goroco){
                $tag = substr($tag, strpos($tag, 'G')+1);
            }
        }
        if (strlen($version_tag['GO']) == 1) {
            $version_tag['GO'] = '0' . $version_tag['GO'];
        }
        if (strlen($version_tag['RO']) == 1) {
            $version_tag['RO'] = '0' . $version_tag['RO'];
        }
        if (strlen($version_tag['CO']) == 1) {
            $version_tag['CO'] = '0' . $version_tag['CO'];
        }

    }
    
}

?>
