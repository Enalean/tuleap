<?php


/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 *
 * SvnToDimensionsActions
 */
require_once ('common/mvc/Actions.class.php');
require_once ('common/include/HTTPRequest.class.php');
require_once ('PluginSvntodimensionsParametersDao.class.php');
require_once ('PluginSvntodimensionsP26CDao.class.php');
require_once ('common/user/UserManager.class.php');

class SvnToDimensionsActions extends Actions {

    var $_controler;

    function SvnToDimensionsActions(& $controler, $view = null) {
        $this->Actions($controler);
        $this->_controler = $controler;
    }

    // {{{ Actions
    /**
     * Save the currents parameters in the database
     */
    function saveParameters() {
        $request = & HTTPRequest :: instance();
        $group_id = $request->get('group_id');
        $product_name = $request->get('product');
        $database = $request->get('database');
        
	$p26c_dao = new PluginSvntodimensionsP26CDao(P26CDataAccess :: instance($database, $this->_controler));        
       if ($p26c_dao->da->db!=null && $p26c_dao->da->db!=0) {  
          $product = & $p26c_dao->searchProductByName($product_name);
            if ($product->rowCount() >= 1) {
		
                $parameters_dao = new PluginSvntodimensionsParametersDao(CodendiDataAccess :: instance());
                $parameters_results = & $parameters_dao->searchByGroupId($group_id);
                if ($parameters_results->rowCount() == 0) {
                    if (!$parameters_dao->create($group_id, $product_name, $database)) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'error_parameters_database'));
                        return;
                    }

                } else {
                    if (!$parameters_dao->updateByGroupId($group_id, $product_name, $database)) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'error_parameters_database'));
                        return;
                    }
                }
                
                
                //récupération des PLs              
               $dp_result = & $p26c_dao->searchDesignPartsByProduct($product_name);                
                if($dp_result->rowCount()>1){
                    $this->_controler->pl = $this->_resultset_to_array($dp_result, "PART_ID", $product_name);
                }else{
                    $this->_controler->pl = array();
                }
	        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_save'));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'error_product'));
            }
        }
    }

    /**
     * Execute all transfer steps and save the transfer in the log table
     */
    function doTransfer() {
        $request = & HTTPRequest :: instance();
        $group_id = $request->get('group_id');
        $password = $request->get('password');

        $parameters_dao = new PluginSvntodimensionsParametersDao(CodendiDataAccess :: instance());
        $result = & $parameters_dao->searchByGroupId($group_id);
        $current = $result->getRow();
        $product_name = $current['product'];
        $database = $current['dimensions_db'];

        $tmp_dir = $this->_controler->getProperty('temp_dir'); 
        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        $short_name = $group->getUnixName(false);
        $folder_temp = $tmp_dir.'/'.$short_name;
        //get the tag to transfert for each PL or for the applicative tag
        $tags = array();
        if($request->get('transfert_type') == "appli"){
            $tags = array($product_name => $request->get('tag_appli'));
        } else {
            foreach($this->_controler->pl as $pl){
                if($request->get('tag_pl'.$pl)!="none"){
                    $tags[$pl] = $request->get('tag_pl'.$pl);
                }
            }
        }
      
        //get user
        $um = & UserManager :: instance();
        $user = & $um->getCurrentUser();
        $p26c_dao = new PluginSvntodimensionsP26CDao(P26CDataAccess :: instance($database, $this->_controler));

        if ($p26c_dao->da->db!=null && $p26c_dao->da->db!=0) {
            $product = & $p26c_dao->searchProductByName($product_name);
            if ($product->rowCount() >= 1) {

                //ckeck PRODUCT-MANAGER role for CODENDIADM user on the given product
                $roles = & $p26c_dao->searchRoleByProductAndUser($product_name, "CODENDIADM");
                $roles_array = $this->_resultset_to_array($roles, "ROLE");
                $logs_dao = new PluginSvntodimensionsLogDao(CodendiDataAccess :: instance());
                //dmcli authentification command
                 $codendi_user_name = $user->getName();
                 $dmcli = $this->_controler->getProperty('dmcli');
                 $dsn = $this->_controler->getProperty('dsn');
                 $host = $this->_controler->getProperty('host');
                 $dmcli_authent = $dmcli . ' -user '.$codendi_user_name.' -pass ' . $password . ' -dbname ' . $database . ' -dsn ' . $dsn .
                        ' -host ' . $host . ' ';                      
                                
                        //for each tag to export : SVN to dimensions process
                        foreach ($tags as $pl => $tag){
                            if(in_array("PRODUCT-MANAGER", $roles_array)){
                                //check upload manager role
                                $user_name = strtoupper($codendi_user_name);
                                $roles = & $p26c_dao->searchRoleByProductAndUser($product_name, $user_name);
                                $user_roles_array = $this->_resultset_to_array($roles, "ROLE");
                                $requires_role = $this->_controler->getProperty('role');
                                if (in_array($requires_role, $user_roles_array)) {                            
                                    //save logs information
                                    $logs_dao->create($group_id, time(), $tag, $pl, $user->getID(), '1');
                                    $this->_controler->transferInProgress = true;
                                    $version_tag_svn = array();
                                    
                                    //workset managment
                                    $this->_getWorksetAndBaseline($product_name, $tag, $pl, & $workset, & $baseline, & $version_tag_svn);
                                    $worksets = & $p26c_dao->searchWorksetByProduct($product_name);
                                    $workset_array = $this->_resultset_to_array($worksets, "WORKSET_NAME");
                                    
                                    $logs_dao = new PluginSvntodimensionsLogDao(CodendiDataAccess::instance());
                                    $logs_result =& $logs_dao->searchByStateAndGroupId($group_id, '0');
                                  
                                    //récupérer les tags de GoRo antérieur et de GoRo identiques
                                    $ant_GoRo = false;
                                    $same_GoRo = false;
                                    $same_tag = false;

                                    while ($logs_result->valid() ){
                                            $row = $logs_result->current();
                                            $version_tag_log = array();
                                            $this->_controler->parseGoRoCo($row['tag'], $version_tag_log);
                                            if($row['design_part'] == $pl){
                                                if($version_tag_log['GO'] < $version_tag_svn['GO'] ) {
                                                   //tag antérieur déjà transféré pour ce PL
                                                   $ant_GoRo = true; 
                                                } else if ($version_tag_log['GO'] == $version_tag_svn['GO']
                                                   && $version_tag_log['RO'] < $version_tag_svn['RO']){
                                                       
                                                   //tag antérieur déjà transféré pour ce PL
                                                   $ant_GoRo = true; 
                                                   
                                                } else if ($version_tag_log['GO'] == $version_tag_svn['GO'] 
                                                   && $version_tag_log['RO'] == $version_tag_svn['RO']){
                                                     if($version_tag_log['CO'] == $version_tag_svn['CO']) {
                                                         //tag identique déjà transféré pour ce pl
                                                         $same_tag = true;
                                                     }  else{
                                                         //tag de même GoRo déjà transféré pour ce PL
                                                        $same_GoRo = true; 
                                                     }
                                                }
                                            }
					    $logs_result->next();
                                    }
                           
                                    if(!$same_tag){
                                        //retrieve the path location of the selected tag
                                        $folder = '';
                                        exec('cd '.$folder_temp.'; find -type d -name  "'.$tag.'" > '.$folder_temp.'/tag_path.txt');
                                        if (file_exists($folder_temp.'/tag_path.txt')){
                                            $file = fopen($folder_temp.'/tag_path.txt', 'r');
                                            if(!feof($file)){
                                                $line = fgets($file, 4096);
                                                $line = trim($line);
                                                if($line != ""){
                                                    $folder = $folder_temp.'/'.substr($line, 2);
                                                }
                                            }
                                            fclose($file);
                                            unlink($folder_temp.'/tag_path.txt');
                                        }   
                                        if(!$same_GoRo && !$ant_GoRo){
                                            //aucun tag de GoRo antérieur ou égal
                                            if(count($workset_array) == 0 || !in_array($workset, $workset_array)){
                                                $errors = $this->_createEmptyWorkset($product_name, $workset, $version_tag_svn, $dmcli_authent);
                                            }
                                        }else if ($same_GoRo){
                                            //tag de même GoRo
                                            //mise à jour par comparaison
                                            //remove files that are in dimensions and no more in svn
                                             $errors = $this->_removeFiles($p26c_dao, $workset, $product_name, $folder, $dmcli_authent, $pl);
                                        } else{
                                            //tag de GoRo antérieur
                                            if(!in_array($workset, $workset_array)){
                                                //creation workset à partir derniere baseline
                                                $last_baseline = & $p26c_dao->searchLastBaselineByProduct($product_name, $pl);
                                                $last_baseline_array = $this->_resultset_to_array($last_baseline, "BASELINE_ID");
                                                $cmd_ws_creation = '-cmd \'DWS "' . $product_name . ':' . $workset . '" ' .
                                                    '/DESC="workset issu de svn G' . $version_tag_svn['GO'] . 'R' . $version_tag_svn['RO'] . '"' .
                                                    '/BASELINE="' . $product_name . ':' . $last_baseline_array[0] . '"\' 2>&1';
                                                $output = shell_exec($dmcli_authent . $cmd_ws_creation);
                                                $errors = $this->_get_dmcli_errors($output);
                                                if (count($errors) < 1) {
                                                    $cmd_branch = '-cmd \'SWS "' . $product_name . ':' . $workset . '" ' .
                                                    '/TRUNK /NOAUTO_REV /VALID_BRANCHES=("' . $version_tag_svn['GO'] . '_' . $version_tag_svn['RO'] . '")\' 2>&1';
                                                    $output = shell_exec($dmcli_authent . $cmd_branch);
                                                    $errors = $this->_get_dmcli_errors($output);
                                                    if (count($errors) < 1)
                                                        //remove files that are in dimensions and no more in svn
                                                        $errors = $this->_removeFiles($p26c_dao, $workset, $product_name, $folder, $dmcli_authent, $pl);
                                                }
                                            }else{
                                                //alimenter workset par la derniere baseline
                                                $last_baseline = & $p26c_dao->searchLastBaselineByProduct($product_name, $pl);
                                                $col_names = array (
                                                                'BASELINE_ID',
                                                                'BASE_SEQ_NO'
                                                             );
                                                $last_baseline_array = $this->_resultset_to_array($last_baseline, $col_names);
                                                $last_baseline_cols=$last_baseline_array[0];

                                                // Compute intial workset
                                                $pattern = '/.*(G\d\dR\d\d)/';
                                                if (preg_match($pattern, $last_baseline_cols['BASELINE_ID'] , $matches)) {
                                                    $goro=$matches[1];
                                                }
                                                $initial_workset="WS_DEV_".$goro;

                                                // Set current workset to initial one
                                                $cmd_set_current_workset = '-cmd \'SCWS "' . $product_name . ':' . $initial_workset . '"\' 2>&1';
                                                $output = shell_exec($dmcli_authent . $cmd_set_current_workset);
                                                $errors = $this->_get_dmcli_errors($output);


                                                $baseline_elements = & $p26c_dao->searchBaselineElements($last_baseline_cols['BASE_SEQ_NO']);
                                                $col_names = array (
                                                                'ITEM_ID',
                                                                'ITEM_TYPE',
                                                                'VARIANT',
                                                                'REVISION'
                                                             );
                                                $base_elts_array = $this->_resultset_to_array($baseline_elements, $col_names);
                                                
                                                foreach ($base_elts_array as $elt) {  
                                                    $spec_item = $product_name . ':' . $elt['ITEM_ID'] . '.' . $elt['VARIANT'] . '-' . $elt['ITEM_TYPE'] . ';' . $elt['REVISION'];
                                                    $cmd_export_elements = '-cmd \'AIWS "' . $spec_item . '" ' .
                                                            '/WORKSET="' . $product_name . ':' . $workset . '"\' 2>&1';
                                                    $output = shell_exec($dmcli_authent . $cmd_export_elements);
                                                    $errors = $this->_get_dmcli_errors($output);
                                                    
                                                }
                                                //mise à jour par comparaison
                                                //remove files that are in dimensions and no more in svn
                                                $errors = $this->_removeFiles($p26c_dao, $workset, $product_name, $folder, $dmcli_authent, $pl);
                                            }
                                        }

                                        // import modules in Dimensions
                                        if (count($errors) < 1) {
                                            $this->_upload_in_dimensions($pl, $folder, $product_name, $database, $workset);
                                            $cmd_bl_creation = '-cmd \' CBL "' . $product_name . ':' . $baseline . '" ' .
                                            '/PART="' . $product_name . ':' . $pl . '.AAAA;1" ' .
                                            '/TEMPLATE_ID="ALL_ITEMS_LATEST" /LEVEL="0" ' .
                                            '/WORKSET="' . $product_name . ':' . $workset . '" /TYPE="REFERENCE" \' 2>&1';
                                            $output = shell_exec($dmcli_authent . $cmd_bl_creation);
                                            $errors = $this->_get_dmcli_errors($output);
                                            if (count($errors) < 1){
                                                $logs_dao->updateByTagAndState($tag, '0');
                                                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_svntodimensions', 'feedback_transfer_done', $tag));
                                            }else {
                                                //delete workset
                                                $cmd_bl_creation = '-cmd \' RWS "' . $product_name . ':' . $workset . '" ';
                                                $output = shell_exec($dmcli_authent . $cmd_bl_creation);
                                                $errors = $this->_get_dmcli_errors($output);
                                            }
                                            //exec('rm -rf ' . $folder_temp);
                                        }
                                        if (count($errors) > 0) {
                                            $errors_list = implode("<br>", $errors); 
                                            $logs_dao->updateByTagAndState($tag, '4', $errors_list);
                                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'error_transfert_cancel_dmcli', array($errors_list, $tag)));
                                        }
                                    
                                    }else{
                                        $error_msg_same_tag = "";
                                        if($request->get('transfert_type') == "appli"){
                                            $error_msg_same_tag = $GLOBALS['Language']->getText('plugin_svntodimensions', 'error_transfert_cancel_same_tag_appli');
                                        } else{
                                            $error_msg_same_tag = $GLOBALS['Language']->getText('plugin_svntodimensions', 'error_transfert_cancel_same_tag', $pl);
                                        }     
                                        $logs_dao->updateByTagAndState($tag, '3', $error_msg_same_tag);                        
                                        $GLOBALS['Response']->addFeedback('error', $error_msg_same_tag);
                                    }
                                    
                                } else {
                                    $logs_dao->updateByTagAndState($tag, '3', $GLOBALS['Language']->getText('plugin_svntodimensions', 'error_transfert_cancel_role', $tag));                        
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions', 'error_transfert_cancel_role', $tag));
                                }
                            }else {
                                $logs_dao->updateByTagAndState($tag, '3', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_configuration', $tag));                        
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_configuration', $tag));
                            }
                            $this->_controler->transferInProgress = false;
                    }
                     exec('rm -rf ' . $folder_temp);
                     $this->_controler->_getPLTags($group_id);          
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_product', $tag));
            }    
        }
    } 
                    
                  



    function _getWorksetAndBaseline($product_name, $tag, $pl, & $workset, & $baseline, & $version_tag_svn) {
        //GoRoCo modification to always have GxxRxxCxx
        $version_tag_svn = array ();
        $this->_controler->parseGoRoCo($tag, $version_tag_svn);

        $workset = 'WS_DEV_G' . $version_tag_svn['GO'] . 'R' . $version_tag_svn['RO'];
        if($product_name == $pl){
            $baseline = 'BL_SVN_G' . $version_tag_svn['GO'] . 'R' . $version_tag_svn['RO'] . 'C' . $version_tag_svn['CO'];
        } else {
            $baseline = 'BL_SVN_'.$pl.'_G' . $version_tag_svn['GO'] . 'R' . $version_tag_svn['RO'] . 'C' . $version_tag_svn['CO'];
        }
    }

    
    function _createEmptyWorkset($product_name, $workset, $version_tag_svn, $dmcli_authent){
        //no workset for this product
        $cmd_ws_creation = '-cmd \'DWS "' . $product_name . ':' . $workset . '" ' .
                            '/DESC="workset issu de cvs G' . $version_tag_svn['GO'] . 'R' . $version_tag_svn['RO'] . '"\' 2>&1';
        $output = shell_exec($dmcli_authent . $cmd_ws_creation);
        $errors = $this->_get_dmcli_errors($output);
        if (count($errors) < 1) {
            $cmd_branch = '-cmd \'SWS "' . $product_name . ':' . $workset . '" ' .
                          '/TRUNK /NOAUTO_REV /VALID_BRANCHES=("' . $version_tag_svn['GO'] . '_' . $version_tag_svn['RO'] . '")\' 2>&1';
            $output = shell_exec($dmcli_authent . $cmd_branch);
            $errors = $this->_get_dmcli_errors($output);
        }
        return $errors;
    }

    function _removeFiles($p26c_dao, $workset, $product, $folder, $dmcli_authent, $design_part) {
        $files = & $p26c_dao->searchWorksetElements($product, $design_part, $workset);
        $col_names = array (
            'FILENAME',
            'PATH',
            'ID',
            'VARIANT',
            'TYPE',
            'REVISION'
        );
        $dim_files = $this->_resultset_to_array($files, $col_names);
        exec("cd " . $folder . "; find . -depth -type f > files_list.txt");
        $svn_files = array ();
        $file = fopen($folder . "/files_list.txt", "r");
        $errors = array ();
        while (!feof($file)) {
            $line = fgets($file, 4096);
            $line = trim($line);
            if ($line != "") {
                $svn_files[] = $design_part.'/'.substr($line, 2);
            }
        }
        fclose($file);
        unlink($folder . "/files_list.txt");
        foreach ($dim_files as $file) {  
            $file_path =  $file['PATH'] . $file['FILENAME'];           
            if ($file['PATH']==NULL) 
                {
                    $file_path = $file['FILENAME'];
                }
            if (!in_array($file_path, $svn_files)) {
                    $spec_item = $product . ':' . $file['ID'] . '.' . $file['VARIANT'] . '-' . $file['TYPE'] . ';' . $file['REVISION'];
                    $cmd_remove_file = '-cmd \'RIWS "' . $spec_item . '" ' .
                    '/WORKSET="' . $product . ':' . $workset . '"\' 2>&1';
                    $output = shell_exec($dmcli_authent . $cmd_remove_file);
                    $errors = $this->_get_dmcli_errors($output);
                    if (count($errors) > 0) {
                        return $errors;
                    }
            }
        }
        return $errors;
    }

    function _upload_in_dimensions($design_part, $folder, $product_name, $database, $workset) {
       
        $upload_path = $this->_controler->getProperty('upload_path');
        $folder_workset = substr($folder,0,strrpos($folder, "/"));
        $folder_to_upload = $folder_workset.'/'.$design_part;       
        $output = shell_exec('mv '.$folder.' '.$folder_to_upload.';');        
        $output = shell_exec($upload_path . ' ' . $database . ' ' . $product_name . ' ' . $design_part . ' ' . $workset . ' ' . $folder_to_upload . ' '.$folder_workset);
        $output = shell_exec('mv '.$folder_to_upload.' '.$folder.';');
        
    }

    function _resultset_to_array($resultset, $col_name, $exception=null) {        
        $result_array = array ();
        while ($resultset->valid()) {
            $row = $resultset->current();
            if (!is_array($col_name)) {
                if($exception==null || $row[$col_name]!=$exception)
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

    function _get_dmcli_errors($output) {
        $array = explode("\n", $output);
        $errors = array ();
        foreach ($array as $line) {
            if (substr($line, 0, 6) == 'Error:') {
                $errors[] = substr($line, 6);
            }
        }
        return $errors;
    }

}
?>
