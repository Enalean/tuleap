<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 *
 * SvnToDimensionsViews
 */
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginSvntodimensionsParametersDao.class.php');
require_once('PluginSvntodimensionsLogDao.class.php');

class SvnToDimensionsViews extends Views {
    
    var $_controler;
    var $tags;
    
    function SvnToDimensionsViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $this->_controler = $controler;
    }
    
    function header() {
        $request =& HTTPRequest::instance();
        $title = $GLOBALS['Language']->getText('plugin_svntodimensions','title');
        $GLOBALS['HTML']->header(array(
        	'title'  => $title,
            'group'  => $request->get('group_id'),
            'toptab' => 'svntodimensions')
        );
        $this->_toolBar($request->get('group_id'));
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    function _toolBar($group_id){
    	echo '<b><a href="?group_id='. $group_id .'&action=intro">'. $GLOBALS['Language']->getText('plugin_svntodimensions', 'toolbar_intro') .'</a></b>';    	
    	echo' | ';
    	echo '<b><a href="?group_id='. $group_id .'&action=transfer">'. $GLOBALS['Language']->getText('plugin_svntodimensions', 'toolbar_transfer') .'</a></b>';
    	echo' | ';
    	echo '<b><a href="?group_id='. $group_id .'&action=historic">'. $GLOBALS['Language']->getText('plugin_svntodimensions', 'toolbar_historic') .'</a></b>';
   		echo' | ';
    	echo '<b><a href="javascript:help_window(\''.get_server_url().'/plugins/svntodimensions/documentation/fr_FR/\')">'. $GLOBALS['Language']->getText('plugin_svntodimensions', 'toolbar_help') .'</a></b>';
    }   
    
   
    // {{{ Views
    function intro(){
    	echo '<h2>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'intro_title').'</h2>';
    	echo $GLOBALS['Language']->getText('plugin_svntodimensions', 'intro_text');
    }
    
    
    function transfer() {
    	$request =& HTTPRequest::instance();
    	$group_id = $request->get('group_id');
        
        //use to set fileds according to the db
        $parameters_dao = new PluginSvntodimensionsParametersDao(CodendiDataAccess::instance());
        $parameters_results =& $parameters_dao->searchByGroupId($group_id);
        
    	$output = '';
        
        //if there is a line in parameters table which matches with the group_id we initialize the form with DB values
        $initialized = false;
        if($row = $parameters_results->getRow()){           
            $initialized = true;
        }
    	$output .= '<h2>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'transfer_title').'</h2>';
    	$output .= $GLOBALS['Language']->getText('plugin_svntodimensions', 'transfer_text');

        // We need prototype
        $output .= '<script type="text/javascript" src="/scripts/prototype/prototype.js"></script>'."\n";

        $output .= '<script language="JavaScript" type="text/javascript">'."\n";
        $output .= '<!-- '."\n".'function change_radio() {'."\n";
        $output .= '  $(\'id2\').select(\'select\').each( function (s) { s.disabled=!$(\'r2\').checked;  });'."\n";
        $output .=   '$(\'id1\').select(\'select\').each( function (s) { s.disabled=!$(\'r1\').checked; }); }'."\n";
        $output .= 'document.observe(\'dom:loaded\', change_radio); // --> </script>';
        
        $output .= "\n<FIELDSET><LEGEND>Param&egrave;tres</LEGEND>\n";

        $output .= $this->_fillRefreshForm($initialized, $row, $request, $group_id);
        $output .= '</FIELDSET>';
//       / $output .= '<br>';
        $output .= "\n<FIELDSET><LEGEND>Transferts</LEGEND>\n";
        $output .= $this->_fillTransferForm($group_id, $request);
    	$output .= '</FIELDSET>';
		
		echo $output;

    }
    
    
    function historic(){
    	$request =& HTTPRequest::instance();
    	$group_id = $request->get('group_id');
    	$output = '';
    	
    	$output .= '<h2>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'historic_title').'</h2>';
    	
    	$titles = array();
    	$titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','historic_tag');
        $titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','historic_dp');
    	$titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','historic_date');
    	$titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','historic_submission');
    	$titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','historic_state');
    	
    	$output .= html_build_list_table_top($titles);
    	
    	$logs_dao = new PluginSvntodimensionsLogDao(CodendiDataAccess::instance());
    	$logs_result =& $logs_dao->searchByGroupId($group_id);
    	
    	$row_index = 0;
    	while($logs_result->valid()){
    		$row = $logs_result->current();
    		$output .= '<tr class="'.html_get_alt_row_color($row_index).'" >';
    		$output .= '<td>'.$row['tag'].'</td>';
            $output .= '<td>'.$row['design_part'].'</td>';
        	$output .= '<td>'.date('Y-m-d H:i', $row['date']).'</td>';
        	$output .= '<td>'.user_getname($row['user_id']).'</td>';
        	if($row['state']==0){
        		$output .= '<td>'.$GLOBALS['Language']->getText('plugin_svntodimensions','historic_state_success').'</td>';
        	}elseif($row['state']==1){
        		$output .= '<td>'.$GLOBALS['Language']->getText('plugin_svntodimensions','historic_state_inprogress').'</td>';
        	}elseif($row['state']==4){
        		$output .= '<td>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'error_transfert_cancel_dmcli_log', $row['error']).'</td>';
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
    	$pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
    	$short_name = $group->getUnixName(false);
    	$output = '';
    	
    	$output .= '<tr>';
    	$output .= '<td><b>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'parameters_product').' : </b></td>';
    	
		if (!$request->exist('product')){
			if($initialized){
				$output .= '<td><input type="text" name="product" value="'.$row['product'].'" size="20" maxlength="40"></td>';
			}else {
				$output .= '<td><input type="text" name="product" value="'.$short_name.'" size="20" maxlength="40"></td>';
			}
		} else {
			$output .= '<td><input type="text" name="product" value="'.$request->get('product').'" size="20" maxlength="40"></td>';
		}
		
		$output .= '</tr>';
		return $output;
    }
    
    
    function _fillDatabase($initialized, $row, $request){
    	$output = '';
    	$output .= '<tr>';
		
		$output .= '<td><b>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'parameters_database').' : </b></td>';
		//this test is usefull if there is some missing fields: the displayed value is the last one filled by the user and not the one of the db
		if (!$request->exist('database')){
			if($initialized){
				$output .= '<td><input type="text" name="database" value="'.$row['dimensions_db'].'" size="20" maxlength="40"></td>';
			}else {
				$output .= '<td><input type="text" name="database" value="" size="20" maxlength="40"></td>';
			}		
		} else {
			$output .= '<td><input type="text" name="database" value="'.$request->get('database').'" size="20" maxlength="40"></td>';
		}
		
		$output .= '</tr>';
		return $output;
    }
    
    function _fillRefreshForm($initialized, $row, $request, $group_id){
        $output = '';
        $output .= '<form action="?group_id='.$group_id.'&action=refreshTags" method="post">';
        
        $output .= '<table >';
        $output .= $this->_fillProduct($initialized, $row, $request, $group_id);
        $output .= $this->_fillDatabase($initialized, $row, $request);
        
        $output .= '<tr>';
        $output .= '<td colspan="2" align="center"><input type="submit" name="refresh" value="'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'transfer_refresh').'"';
        $output .= '</tr>';
        $output .= '</table>';
        $output .= '</form>';
        
        return $output;
    }
    
    function _fillTransferForm($group_id, $request){
        $output = '';
        $output .= '<form action="?group_id='.$group_id.'&action=doTransfer" method="post">';
        
        $output .= "\n<table>\n";
        
        $output .= '<tr>';
        $output .= '<td valign="top">';
        $output .= $this->_fillAppliTag($group_id, $request);
        $output .= "</td>\n";
        $output .= "<td> </td>\n";
        $output .= '<td>';
        $output .= $this->_fillPlTag($group_id, $request);
        $output .= '</td>';
        $output .= "</tr>\n";

        $output .= '<tr>';
        
        $output .= '<td colspan="3" align="center"><b>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'transfer_password')." : </b>\n";
        $output .= '<input type="password" name="password" value="" size="10" maxlength="20"></td>';

        $output .= "</tr>\n";
        
        $output .= '<tr>';
        
        $output .= '<td colspan="3" align="center"><input type="submit" name="transfer" value="'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'transfer_transfer').'"';
        $transferableTags = $this->_getTransferableTags($this->_controler->tags, $group_id);
        $transferInProgress = $this->_controler->transferInProgress;
        if(count($transferableTags)==0 || $transferInProgress){
            $output .= ' disabled = "true"';
        }
        $output .= '></td></tr>';
        $output .= '</table>';
        $output .= "</form>\n";
        
        
        return $output;
        
    }
    
    function _fillAppliTag($group_id, $request){
        $output = '';
        $output .='<table>';
        $transferInProgress = $this->_controler->transferInProgress;
        $output .= '<tr>';
        $output .= '<td align="left">';
        $output .= '<input type="radio" name="transfert_type" value="appli" id="r1" onClick="change_radio();" ';
        if($transferInProgress){
		$output .= 'disabled = "true"';
        }
        $output .= ' ><b>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'transfer_appli').'</b>';
        $output .= '</td>';
        $output .= '</tr>';
        
        $output .= '<tr id="id1">';
        $output .= '<td align="center">';
        $output .= '<select name="tag_appli"';
        if($transferInProgress){
                $output .= ' disabled = "true" ';
        } 
        $output .= '>';
        $transferableTags = $this->_getTransferableTags($this->_controler->tags, $group_id);
        foreach($transferableTags as $tag){
            $output .= '<option value="'.$tag.'"';
           if($request->exist('tag') && $_POST['tag'] == $tag){
                $output .= ' selected';
            }
            $output .= '>'.$tag;
        }
        $output .='</select>';
        $output .= '</td>';
        $output .= '</tr>';
        
        $output .='</table>';
        
        return $output;
    }
    
    function _fillPlTag($group_id, $request){
        $no_pl = true;
        if(sizeof($this->_controler->pl)>0){
            $no_pl = false;
        }
        $transferInProgress = $this->_controler->transferInProgress;
        $output = '';
        $output .='<table>';
        
        $output .= '<tr>';
        $output .= '<td align="left">';
        $output .= '<input type="radio" name="transfert_type" value="pl"  id="r2" onClick="change_radio();" ';
        if($no_pl || $transferInProgress){
            $output .= 'disabled ="true" ';
        }
        $output .= '><b>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'transfer_pl').'</b>';
        $output .= '</td>';
        $output .= '</tr>';
        
        $output .= '<tr>';
        $output .= '<td align="center">';
       
        $output .= '<table border="1" id="id2">';
        $output .= '<tr>';
        $output .= '<td>';
        $output .= '<table>';
        if($no_pl){
            $output .= '<tr>';
            $output .= '<td>';
            $output .= $GLOBALS['Language']->getText('plugin_svntodimensions', 'transfer_no_pl');
            $output .= '</td>';
            $output .= '</tr>';
        } else{
            foreach($this->_controler->pl as $pl){           
		$output .= '<tr>';
                $output .= '<td><b>'.$pl.':</b></td>';
                $output .= '<td><select name="tag_pl'.$pl;
                if($transferInProgress){
                    $output .= ' disabled = "true"';
                }
                $output .= '">';
                $output .= '<option value="none">Aucun';
                $transferableTags = $this->_getTransferableTags($this->_controler->tags, $group_id);
                foreach($transferableTags as $tag){
                    $output .= '<option value="'.$tag.'"';
                    if($request->exist('tag') && $_POST['tag'] == $tag){
                        $output .= ' selected';
                    }
                    $output .= '>'.$tag;
                }
                
                $output .= '</td>';
                $output .= '</tr>';
            }
        }
        
        $output .= '</table>';
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';
       
        $output .= '</td>';
        $output .= '</tr>';
        
        $output .='</table>';
        return $output;
    }
    
    
    function _getTransferableTags($tags, $group_id){
        $transferableTags = array();
    	$logs_dao = new PluginSvntodimensionsLogDao(CodendiDataAccess::instance());
    	$logs_result =& $logs_dao->searchByGroupId($group_id);
    	//$tags = array('fdsG2r54C45lks', 'fjGhjR01C1bg', 'fdG2R54Cfds', 'gfdsG01R23C1fdsd', 'GezRhCfhG01R2C5fgsd', 'sdjqG5R3C55gfd');
       // $tags = array('sdjqG5R3C55gfd');
        foreach($tags as $tag){
    		if(preg_match("`.*G[0-9]{1,2}R[0-9]{1,2}C[0-9]{1,2}.*`", $tag)){
    			$same_tag = false;
    			$version_tag_svn = array();
                //$this->_controler->parseGoRoCo($tag, $version_tag_svn);
                while (!$same_tag && $logs_result->valid()){
    				$row = $logs_result->current(); 
    				if(($row['state']==0 || $row['state']==1) && $row['tag'] ==  $tag){
    					/*$version_tag_log = array();
    					$this->_controler->parseGoRoCo($row['tag'], $version_tag_log);
    					if($version_tag_log['GO'] == $version_tag_svn['GO'] 
                            && $version_tag_log['RO'] == $version_tag_svn['RO'] 
                            && $version_tag_log['CO'] == $version_tag_svn['CO']){
    						$same_tag = true;
    					}*/
                        $same_tag = true;
    				}
    				$logs_result->next();
    			}
                //$transferableTags[] = 'G'.$version_tag_svn['GO'].'R'.$version_tag_svn['RO'].'C'.$version_tag_svn['CO'];
    			$transferableTags[] = $tag;
                $logs_result->rewind();
    		}
    	}
    	return $transferableTags;
    }
    
}


?>
