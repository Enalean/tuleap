<?php


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 *
 * CvsToDimensionsActions
 */
require_once ('common/mvc/Actions.class.php');
require_once ('common/include/HTTPRequest.class.php');
require_once ('PluginCvstodimensionsParametersDao.class.php');
require_once ('PluginCvstodimensionsP26CDao.class.php');
require_once ('common/include/UserManager.class.php');

class CvsToDimensionsActions extends Actions {

    var $_controler;

    function CvsToDimensionsActions(& $controler, $view = null) {
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
        $p26c_dao = new PluginCvstodimensionsP26CDao(P26CDataAccess :: instance($database, $this->_controler));
 
        if ($p26c_dao->da->db!=null && $p26c_dao->da->db!=0) {
            $product = & $p26c_dao->searchProductByName($product_name);

            if ($product->rowCount() >= 1) {
                $parameters_dao = new PluginCvstodimensionsParametersDao(CodexDataAccess :: instance());
                $parameters_results = & $parameters_dao->searchByGroupId($group_id);
                if ($parameters_results->rowCount() == 0) {
                    if (!$parameters_dao->create($group_id, $product_name, $database)) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_parameters_database'));
                        return;
                    }

                } else {
                    if (!$parameters_dao->updateByGroupId($group_id, $product_name, $database)) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_parameters_database'));
                        return;
                    }
                }
                $modules_dao = new PluginCvstodimensionsModulesDao(CodexDataAccess :: instance());
                $modules_results = & $modules_dao->searchByGroupId($group_id);
                if (!$modules_dao->deleteByGroupId($group_id)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_parameters_database'));
                    return;
                }
                $module_index = 0;
                foreach ($this->_controler->modules as $module) {
                    $module_index++;
                    if (!$modules_dao->create($group_id, $module, $_POST['module_' . $module_index])) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_parameters_database'));
                        return;
                    }
                }
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_save'));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_product'));
            }
        }
    }

    /**
     * Execute all transfer steps and save the transfer in the log table
     */
    function doTransfer() {
        $request = & HTTPRequest :: instance();
        $group_id = $request->get('group_id');
        $tag = $request->get('tag');
        $password = $request->get('password');

        $parameters_dao = new PluginCvstodimensionsParametersDao(CodexDataAccess :: instance());
        $result = & $parameters_dao->searchByGroupId($group_id);
        $current = $result->getRow();
        $product_name = $current['product'];
        $database = $current['dimensions_db'];

        //get user
        $um = & UserManager :: instance();
        $user = & $um->getCurrentUser();
        $p26c_dao = new PluginCvstodimensionsP26CDao(P26CDataAccess :: instance($database, $this->_controler));

        if ($p26c_dao->da->db!=null && $p26c_dao->da->db!=0) {
            $product = & $p26c_dao->searchProductByName($product_name);
            if ($product->rowCount() >= 1) {

                $modules_dao = new PluginCvstodimensionsModulesDao(CodexDataAccess :: instance());
                $modules_results = & $modules_dao->searchByGroupId($group_id);
                $this->_removeAdditionnalModules($modules_dao, $group_id, $modules_results);
                $design_part_missing = $this->_getMissingModulesInP26C($p26c_dao, $modules_dao, $product_name, $group_id);

                //ckeck PRODUCT-MANAGER role for CODEXADM user on the given product
                $roles = & $p26c_dao->searchRoleByProductAndUser($product_name, "CODEXADM");
                $roles_array = $this->_resultset_to_array($roles, "role");
                $logs_dao = new PluginCvstodimensionsLogDao(CodexDataAccess :: instance());
                //save logs information
                $logs_dao->create($group_id, time(), $tag, $user->getID(), '1');
                $this->_controler->transferInProgress = true;
                if(in_array("PRODUCT-MANAGER", $roles_array)){
                    //check upload manager role
                    $codex_user_name = $user->getName();
                    $user_name = strtoupper($codex_user_name);
    
                    $roles = & $p26c_dao->searchRoleByProductAndUser($product_name, $user_name);
                    $roles_array = $this->_resultset_to_array($roles, "role");
                    $requires_role = $this->_controler->getProperty('role');
                    
                    if (count($design_part_missing) == 0 && in_array($requires_role, $roles_array)) {            
                        $this->_tagExport($tag, & $folder, $group_id);
                        $modules_results = & $modules_dao->searchByGroupId($group_id);
                        
                        //CVS to dimensions process
    
                        //workset managment
                        $this->_getWorksetAndBaseline($tag, & $workset, & $baseline, & $version_tag_log);
                        $worksets = & $p26c_dao->searchWorksetByProduct($product_name);
                        $dmcli = $this->_controler->getProperty('dmcli');
                        $dsn = $this->_controler->getProperty('dsn');
                        $host = $this->_controler->getProperty('host');
                        $dmcli_authent = $dmcli . ' -user '.$codex_user_name.' -pass ' . $password . ' -dbname ' . $database . ' -dsn ' . $dsn .
                        ' -host ' . $host . ' ';

                        $workset_array = $this->_resultset_to_array($worksets, "workset_name");
                        if (count($workset_array) == 0) {
                            //no workset for this product
                            $cmd_ws_creation = '-cmd \'DWS "' . $product_name . ':' . $workset . '" ' .
                            '/DESC="workset issu de cvs G' . $version_tag_log['GO'] . 'R' . $version_tag_log['RO'] . '"\' 2>&1';
                            $output = shell_exec($dmcli_authent . $cmd_ws_creation);
                            $errors = $this->_get_dmcli_errors($output);
                            if (count($errors) < 1) {
                                $cmd_branch = '-cmd \'SWS "' . $product_name . ':' . $workset . '" ' .
                                '/TRUNK /NOAUTO_REV /VALID_BRANCHES=("' . $version_tag_log['GO'] . '_' . $version_tag_log['RO'] . '")\' 2>&1';
                                $output = shell_exec($dmcli_authent . $cmd_branch);
                                $errors = $this->_get_dmcli_errors($output);
                            }
    
                        } else
                            if (in_array($workset, $workset_array)) {
                                //the workset exists
                                //remove files that are in dimensions and no more in cvs
                                $errors = $this->_removeFiles($p26c_dao, $workset, $product_name, $folder, $dmcli_authent);
                            } else {
                                //the given workset doesn't exist but there are other worksets for this product
                                $last_baseline = & $p26c_dao->searchLastBaselineByProduct($product_name);
                                $last_baseline_array = $this->_resultset_to_array($last_baseline, "baseline_id");
                                $cmd_ws_creation = '-cmd \'DWS "' . $product_name . ':' . $workset . '" ' .
                                '/DESC="workset issu de cvs G' . $version_tag_log['GO'] . 'R' . $version_tag_log['RO'] . '"' .
                                '/BASELINE="' . $product_name . ':' . $last_baseline_array[0] . '"\' 2>&1';
                                $output = shell_exec($dmcli_authent . $cmd_ws_creation);
                                $errors = $this->_get_dmcli_errors($output);
                                if (count($errors) < 1) {
                                    $cmd_branch = '-cmd \'SWS "' . $product_name . ':' . $workset . '" ' .
                                    '/TRUNK /NOAUTO_REV /VALID_BRANCHES=("' . $version_tag_log['GO'] . '_' . $version_tag_log['RO'] . '")\' 2>&1';
                                    $output = shell_exec($dmcli_authent . $cmd_branch);
                                    $errors = $this->_get_dmcli_errors($output);
                                    if (count($errors) < 1)
                                        //remove files that are in dimensions and no more in cvs
                                        $errors = $this->_removeFiles($p26c_dao, $workset, $product_name, $folder, $dmcli_authent);
                                }
                            }
                        // import modules in Dimensions
                        if (count($errors) < 1) {
                            //first create a high level module (ex : /module1 becomes module1/module1)
    
                            $this->_upload_in_dimensions($modules_results, $folder, $product_name, $database, $workset);
                            $cmd_bl_creation = '-cmd \' CBL "' . $product_name . ':' . $baseline . '" ' .
                            '/PART="' . $product_name . ':' . $product_name . '.AAAA;1" ' .
                            '/TEMPLATE_ID="ALL_ITEMS_LATEST" /LEVEL="0" ' .
                            '/WORKSET="' . $product_name . ':' . $workset . '" /TYPE="REFERENCE" \' 2>&1';
                            $output = shell_exec($dmcli_authent . $cmd_bl_creation);
                            $errors = $this->_get_dmcli_errors($output);
                            if (count($errors) < 1){
                                $logs_dao->updateByTagAndState($tag, '0');
                                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'feedback_transfer_done'));
                            }else {
                                //delete workset
                                $cmd_bl_creation = '-cmd \' RWS "' . $product_name . ':' . $workset . '" ';
                                $output = shell_exec($dmcli_authent . $cmd_bl_creation);
                                $errors = $this->_get_dmcli_errors($output);
                            }
                            exec('rm -rf ' . $folder);
    
                        }
                        if (count($errors) > 0) {
                            $errors_list = implode("<br>", $errors);
                            $logs_dao->updateByTagAndState($tag, '4', $errors_list);
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_dmcli', $errors_list));
                            exec('rm -rf ' . $folder);
                        }
    
                    } else {
                        if (count($design_part_missing) != 0) {
                            $modules_list = implode(", ", $design_part_missing);
                            $logs_dao->updateByTagAndState($tag, '3', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_modules', $modules_list));
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_modules', $modules_list));
                        } else {
                            $logs_dao->updateByTagAndState($tag, '3', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_role'));                        
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_role'));
                        }
                    }
                }else {
                    $logs_dao->updateByTagAndState($tag, '3', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_configuration'));                        
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_configuration'));
                }
                $this->_controler->transferInProgress = false;
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_product'));
            }
        }
    }

    function _removeAdditionnalModules($modules_dao, $group_id, $modules_results) {
        //remove all additionals modules in the database (modules table)
        if ($modules_results->rowCount() != $this->_controler->modules->length) {
            while ($modules_results->valid()) {
                $row = $modules_results->current();
                if (!in_array($row['module'], $this->_controler->modules)) {
                    $modules_dao->deleteByGroupIdAndModule($group_id, $row['module']);
                }
                $modules_results->next();
            }
        }
    }

    function _getMissingModulesInP26C($p26c_dao, $modules_dao, $product_name, $group_id) {
        //check that all designs parts listing in the parameters screen exist for this product
        $design_parts_P26C = & $p26c_dao->searchDesignPartsByProduct($product_name);
        $design_parts_modules = & $modules_dao->searchByGroupId($group_id);
        $design_part_missing = array ();
        $design_parts_P26C_array = $this->_resultset_to_array($design_parts_P26C, "part_id");
        $design_parts_modules_array = $this->_resultset_to_array($design_parts_modules, "design_part");
        foreach ($design_parts_modules_array as $row) {
            if (!in_array($row, $design_parts_P26C_array)) {
                $design_part_missing[] = $row;
            }
        }
        return $design_part_missing;
    }

    function _getWorksetAndBaseline($tag, & $workset, & $baseline, & $version_tag_log) {
        //GoRoCo modification to always have GxxRxxCxx
        $version_tag_log = array ();
        $this->_controler->parseGoRoCo($tag, $version_tag_log);

        if (strlen($version_tag_log['GO']) == 1) {
            $version_tag_log['GO'] = '0' . $version_tag_log['GO'];
        }
        if (strlen($version_tag_log['RO']) == 1) {
            $version_tag_log['RO'] = '0' . $version_tag_log['RO'];
        }
        if (strlen($version_tag_log['CO']) == 1) {
            $version_tag_log['CO'] = '0' . $version_tag_log['CO'];
        }
        $workset = 'WS_DEV_G' . $version_tag_log['GO'] . 'R' . $version_tag_log['RO'];
        $baseline = 'BL_CVS_G' . $version_tag_log['GO'] . 'R' . $version_tag_log['RO'] . 'C' . $version_tag_log['CO'];
    }

    function _tagExport($tag, & $folder, $group_id) {
        //export the tag in var/tmp
        $tmp_dir = $this->_controler->getProperty('temp_dir');
        $res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
        $row_grp = db_fetch_array($res_grp);
        $folder = $tmp_dir . "/" . $row_grp['unix_group_name'] . "_" . $tag;
        exec('cd ' . $tmp_dir . ';cvs -d/cvsroot/' . $row_grp['unix_group_name'] . ' export -r ' . $tag . ' -d ' .$row_grp['unix_group_name'] . "_" . $tag.' .');
    }

    function _removeFiles($p26c_dao, $workset, $product, $folder, $dmcli_authent) {
        $files = & $p26c_dao->searchWorksetElementByProductAndWorkset($product, $workset);
        $col_names = array (
            'filename',
            'path',
            'id',
            'variant',
            'type',
            'revision'
        );
        $dim_files = $this->_resultset_to_array($files, $col_names);
        exec("cd " . $folder . "; find . -depth -type f > files_list.txt");
        $cvs_files = array ();
        $file = fopen($folder . "/files_list.txt", "r");
        $errors = array ();
        while (!feof($file)) {
            $line = fgets($file, 4096);
            $line = trim($line);
            if ($line != "") {
                $cvs_files[] = substr($line, 2);
            }
        }
        fclose($file);
        unlink($folder . "/files_list.txt");
        foreach ($dim_files as $file) {  
            $file_path =  $file['path'] . $file['filename'];           
            if ($file['path']==NULL) 
                {
                    $file_path = $file['filename'];
                }
                
            if (!in_array($file_path, $cvs_files)) {
                    $spec_item = $product . ':' . $file['id'] . '.' . $file['variant'] . '-' . $file['type'] . ';' . $file['revision'];
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

    function _upload_in_dimensions($modules_results, $folder, $product_name, $database, $workset) {
        $modules_results->rewind();
        while ($modules_results->valid()) {
            $row = $modules_results->current();
            $row['module'] = str_replace(' ', '\\ ', $row['module']);
            exec('cd ' . $folder . '; mkdir ' . $row['module'] . '_tmp; mv ' . $row['module'] . ' ' . $row['module'] . '_tmp; mv ' . $row['module'] . '_tmp ' . $row['module']);
            $modules_results->next();
        }
        $modules_results->rewind();
        while ($modules_results->valid()) {
            $row = $modules_results->current();
            $row['module'] = str_replace(' ', '\\ ', $row['module']);
            $upload_path = $this->_controler->getProperty('upload_path');
            $output = shell_exec($upload_path . ' ' . $database . ' ' . $product_name . ' ' . $row['design_part'] . ' ' . $workset . ' ' . $folder . '/' . $row['module']);
            $modules_results->next();
        }
        //upload root files (not in a module)
        $root_files = false;
        if ($handle = opendir($folder)) {
            while (false !== ($file = readdir($handle))) {
                if (!is_dir($folder.'/'.$file)) {
                    $file = str_replace(' ', '\\ ', $file);                
                    if(!$root_files){
                        exec('cd ' . $folder . '; mkdir root_tmp;');
                        $root_files = true;
                    }
                    exec('cd ' . $folder . '; mv ' . $file . ' root_tmp; ');
                }
            }
            closedir($handle);
        }
        if ($root_files){
            $output = shell_exec($upload_path . ' ' . $database . ' ' . $product_name . ' ' . $product_name . ' ' . $workset . ' ' . $folder . '/root_tmp');
        }
        
    }

    function _resultset_to_array($resultset, $col_name) {
        $result_array = array ();
        while ($resultset->valid()) {
            $row = $resultset->current();
            if (!is_array($col_name)) {
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