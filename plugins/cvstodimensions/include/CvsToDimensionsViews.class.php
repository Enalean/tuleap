<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 *
 * CvsToDimensionsViews
 */
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginCvstodimensionsParametersDao.class.php');
require_once('PluginCvstodimensionsModulesDao.class.php');
require_once('PluginCvstodimensionsLogDao.class.php');

class CvsToDimensionsViews extends Views {
    
    var $_controler;
    
    
    function CvsToDimensionsViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $this->_controler = $controler;
        $GLOBALS['Language']->loadLanguageMsg('cvstodimensions', 'cvstodimensions');
    }
    
    function header() {
        $request =& HTTPRequest::instance();
        $title = $GLOBALS['Language']->getText('plugin_cvstodimensions','title');
        $GLOBALS['HTML']->header(array(
        	'title'  => $title,
            'group'  => $request->get('group_id'),
            'toptab' => 'cvstodimensions')
        );
        $this->_toolBar($request->get('group_id'));
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    function _toolBar($group_id){
    	echo '<b><a href="?group_id='. $group_id .'&action=intro">'. $GLOBALS['Language']->getText('plugin_cvstodimensions', 'toolbar_intro') .'</a></b>';    	
    	echo' | ';
    	echo '<b><a href="?group_id='. $group_id .'&action=parameters">'. $GLOBALS['Language']->getText('plugin_cvstodimensions', 'toolbar_parameters') .'</a></b>';
    	echo' | ';
    	echo '<b><a href="?group_id='. $group_id .'&action=transfer">'. $GLOBALS['Language']->getText('plugin_cvstodimensions', 'toolbar_transfer') .'</a></b>';
    	echo' | ';
    	echo '<b><a href="?group_id='. $group_id .'&action=historic">'. $GLOBALS['Language']->getText('plugin_cvstodimensions', 'toolbar_historic') .'</a></b>';
   		echo' | ';
    	echo '<b><a href="javascript:help_window(\''.get_server_url().'/plugins/cvstodimensions/documentation/fr_FR/\')">'. $GLOBALS['Language']->getText('plugin_cvstodimensions', 'toolbar_help') .'</a></b>';
    }   
    
   
    // {{{ Views
    function intro(){
    	echo '<h2>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'intro_title').'</h2>';
    	echo $GLOBALS['Language']->getText('plugin_cvstodimensions', 'intro_text');
    }
    
    function parameters() {
    	$request =& HTTPRequest::instance();
    	$group_id = $request->get('group_id');
    	
    	//use to set fileds according to the db
    	$parameters_dao = new PluginCvstodimensionsParametersDao(CodexDataAccess::instance());
    	$parameters_results =& $parameters_dao->searchByGroupId($group_id);
    	
    	$output = '';

		//if there is a line in parameters table which matches with the group_id we initialize the form with DB values
		$initialized = false;
		if($row = $parameters_results->getRow()){
			$initialized = true;
		}
		    	
    	$output .= '<h2>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'parameters_title').'</h2>';
    	$output .= $GLOBALS['Language']->getText('plugin_cvstodimensions', 'parameters_text');
    	$output .= '<form action="?group_id='.$group_id.'&action=saveParameters" method="post">';
    	
    	$output .= '<table>';
    	
    	$output .= $this->_fillProduct($initialized, $row, $request, $group_id);
		$output .= $this->_fillDatabase($initialized, $row, $request);
		$output .= $this->_fillModules($request, $group_id);
		
		$output .= '<tr>';
		$output .= '<td rowspan="2"><input type="submit" name="ok" value="'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'parameters_save').'"></td>';
		$output .= '</tr>';
		
		$output .='</table>';
		$output .= '</form>';
		
		echo $output;
			
		
    }
    
    function transfer() {
    	$request =& HTTPRequest::instance();
    	$group_id = $request->get('group_id');
    	$output = '';
    	
    	$output .= '<h2>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'transfer_title').'</h2>';
    	$output .= $GLOBALS['Language']->getText('plugin_cvstodimensions', 'transfer_text');
    	
    	$output .= '<form action="?group_id='.$group_id.'&action=doTransfer" method="post">';
    	
    	$output .= '<table>';
    	$output .= '<tr>';
    	
    	$output .= '<td><b>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'transfer_tag').' : </b></td>';
		$output .= '<td><select name="tag">';
		
		$transferableTags = $this->_getTransferableTags($this->_controler->tags, $group_id);
		$transferInProgress = $this->_controler->transferInProgress;
		foreach($transferableTags as $tag){
			$output .= '<option value="'.$tag.'"';
			if($request->exist('tag') && $_POST['tag'] == $tag){
				$output .= ' selected';
			}
			$output .= '>'.$tag;
		}
		$output .='</select></td>';
		
		$output .= '</tr>';
		$output .= '<tr>';
		
		$output .= '<td><b>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'transfer_password').' : </b></td>';
		$output .= '<td><input type="password" name="password" value="" size="10" maxlength="20"></td>';

		$output .= '</tr>';
		$output .= '<tr>';
		
		$output .= '<td rowspan="2"><input type="submit" name="transfer" value="'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'transfer_transfer').'"';
		if(count($transferableTags)==0 || $transferInProgress){
			$output .= ' disabled = "true"';
		}
		$output .= '></td>';
		
		$output .= '</table>';
		
		$output .= '</form>';
		
		echo $output;

    }
    
    function historic(){
    	$request =& HTTPRequest::instance();
    	$group_id = $request->get('group_id');
    	$output = '';
    	
    	$output .= '<h2>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'historic_title').'</h2>';
    	
    	$titles = array();
    	$titles[] = $GLOBALS['Language']->getText('plugin_cvstodimensions','historic_tag');
    	$titles[] = $GLOBALS['Language']->getText('plugin_cvstodimensions','historic_date');
    	$titles[] = $GLOBALS['Language']->getText('plugin_cvstodimensions','historic_submission');
    	$titles[] = $GLOBALS['Language']->getText('plugin_cvstodimensions','historic_state');
    	
    	$output .= html_build_list_table_top($titles);
    	
    	$logs_dao = new PluginCvstodimensionsLogDao(CodexDataAccess::instance());
    	$logs_result =& $logs_dao->searchByGroupId($group_id);
    	
    	$row_index = 0;
    	while($logs_result->valid()){
    		$row = $logs_result->current();
    		$output .= '<tr class="'.html_get_alt_row_color($row_index).'" >';
    		$output .= '<td>'.$row['tag'].'</td>';
        	$output .= '<td>'.date('Y-m-d H:i', $row['date']).'</td>';
        	$output .= '<td>'.user_getname($row['user_id']).'</td>';
        	if($row['state']==0){
        		$output .= '<td>'.$GLOBALS['Language']->getText('plugin_cvstodimensions','historic_state_success').'</td>';
        	}elseif($row['state']==1){
        		$output .= '<td>'.$GLOBALS['Language']->getText('plugin_cvstodimensions','historic_state_inprogress').'</td>';
        	}elseif($row['state']==4){
        		$output .= '<td>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'error_transfert_cancel_dmcli', $row['error']).'</td>';
        	}else{
        	    $output .= '<td>'.$row['error'].'</td>';
        	}
        	$output .= '</tr>';
    		$row_index ++;
    		$logs_result->next();
    	}
        $output .= '</table>';
        
        echo $output;
    }
    
    // }}}
    
    
    // {{{ functions to fill a form field
    function _fillProduct($initialized, $row, $request, $group_id){
    	$group = group_get_object($group_id);
    	$short_name = $group->getUnixName();
    	$output = '';
    	
    	$output .= '<tr>';
    	$output .= '<td><b>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'parameters_product').' : </b></td>';
    	
		if (!$request->exist('product')){
			if($initialized){
				$output .= '<td><input type="text" name="product" value="'.$row['product'].'" size="20" maxlength="40"></td>';
			}else {
				$output .= '<td><input type="text" name="product" value="'.$short_name.'" size="20" maxlength="40"></td>';
			}
		} else {
			$output .= '<td><input type="text" name="product" value="'.$_POST['product'].'" size="20" maxlength="40"></td>';
		}
		
		$output .= '</tr>';
		return $output;
    }
    
    
    function _fillDatabase($initialized, $row, $request){
    	$output = '';
    	$output .= '<tr>';
		
		$output .= '<td><b>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'parameters_database').' : </b></td>';
		//this test is usefull if there is some missing fields: the displayed value is the last one filled by the user and not the one of the db
		if (!$request->exist('database')){
			if($initialized){
				$output .= '<td><input type="text" name="database" value="'.$row['dimensions_db'].'" size="20" maxlength="40"></td>';
			}else {
				$output .= '<td><input type="text" name="database" value="" size="20" maxlength="40"></td>';
			}		
		} else {
			$output .= '<td><input type="text" name="database" value="'.$_POST['database'].'" size="20" maxlength="40"></td>';
		}
		
		$output .= '</tr>';
		return $output;
    }
    
    function _fillModules($request, $group_id){
    	$output = '';
		
		$module_index = 0;
		foreach($this->_controler->modules as $module){
			$output .= '<tr>';
			$module_index ++;
			$output .= '<td><b>'.$module.' : </b></td>';
			$modules_dao = new PluginCvstodimensionsModulesDao(CodexDataAccess::instance());
			
			if (!$request->exist('module_'.$module_index)){
				$design_part = $modules_dao->searchByGroupIdAndModule($group_id, $module);
				if($row = $design_part->getRow()){
					$output .= '<td><input type="text" name="module_'.$module_index.'" value="'.$row['design_part'].'" size="10" maxlength="20"></td>';
				}else{
					$output .= '<td><input type="text" name="module_'.$module_index.'" value="" size="10" maxlength="20"></td>';
				}
			} else {
				$output .= '<td><input type="text" name="module_'.$module_index.'" value="'.$_POST['module_'.$module_index].'" size="10" maxlength="20"></td>';
			}
			
			$output .= '</tr>';
		}
		
    	return $output;
    }
    
    function _fillStatus($initialized, $row, $request){
    	$output = '';
    	$output .= '<tr>';
		//To know which option to select
		$option_selected = 0;
		if(!$request->exist('status')){
			if($initialized){
				$option_selected = $row['status'];
			}
		}else{
			$option_selected = $_POST['status'];
		}
		
		$output .= '<td><b>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'parameters_status').' : </b></td>';
		$output .= '<td><select name="status">';
		$output .= '<option value="0"';
		if($option_selected == 0){
			$output .= ' selected';
		}
		$output .= '>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'parameters_status_initial');
		$output .= '<option value="1"';
		if($option_selected == 1){
			$output .= ' selected';
		}
		$output .= '>'.$GLOBALS['Language']->getText('plugin_cvstodimensions', 'parameters_status_final').'</select></td>';
		
		

		$output .= '</tr>';
    	return $output;
    }
    
    function _getTransferableTags($tags, $group_id){
    	$transferableTags = array();
    	$logs_dao = new PluginCvstodimensionsLogDao(CodexDataAccess::instance());
    	$logs_result =& $logs_dao->searchByGroupId($group_id);
    	foreach($tags as $tag){
    		if(preg_match("`^G[0-9]{1,2}R[0-9]{1,2}C[0-9]{1,2}$`", $tag)){
    			$posterior_tag = false;
			    $most_recent_CO = '';
    			while ($logs_result->valid()){
    				$row = $logs_result->current(); 
    				if($row['state']==0 || $row['state']==1 ){
    					$version_tag_log = array();
    					$this->_controler->parseGoRoCo($row['tag'], $version_tag_log);
    					$version_tag_cvs = array();
    					$this->_controler->parseGoRoCo($tag, $version_tag_cvs);
    					if($version_tag_log['GO'] > $version_tag_cvs['GO'] ){
    						$posterior_tag = true;
    					}elseif($version_tag_log['GO'] == $version_tag_cvs['GO']){
							if($version_tag_log['RO'] > $version_tag_cvs['RO']){
								$posterior_tag = true;
							}elseif($version_tag_log['RO'] == $version_tag_cvs['RO']){
								if($most_recent_CO == '' || $most_recent_CO < $version_tag_log['CO']){
									$most_recent_CO = $version_tag_log['CO'];
								}
							}
    					}
    				}
    				$logs_result->next();
    			}
    			if(($most_recent_CO!='' && $most_recent_CO<$version_tag_cvs['CO'])||($most_recent_CO=='' && !$posterior_tag)){
    				$transferableTags[] = $tag;
    			}
    			$logs_result->rewind();
    		}
    	}
    	return $transferableTags;
    }
    
}


?>
