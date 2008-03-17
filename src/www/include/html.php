<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

function html_feedback_top($feedback) {
    echo $GLOBALS['HTML']->feedback($GLOBALS['feedback']);
}

function html_feedback_bottom($feedback) {
  echo $GLOBALS['HTML']->feedback($GLOBALS['feedback']);
}

function html_a_group($grp) {
	print '<A /project/?group_id='.$grp.'>' . group_getname($grp) . '</A>';
}

function html_blankimage($height,$width) {
	return html_image('blank.png',array('height'=>$height,'width'=>$width,'alt'=>' '));
}

function html_image($src,$args,$display=1) {
	GLOBAL $img_size;
	$return = ('<IMG src="'.util_get_dir_image_theme().$src.'"');
	reset($args);
	while(list($k,$v) = each($args)) {
		$return .= ' '.$k.'="'.$v.'"';
	}

	// ## insert a border tag if there isn't one
	if (!isset($args['border']) || !$args['border']) $return .= (" border=0");

	// ## if no height AND no width tag, insert em both
	if ((!isset($args['height']) || !$args['height']) && 
            (!isset($args['width'])  || !$args['width'])) {
		/* Check to see if we've already fetched the image data */
		if($img_size){
                    if((!isset($img_size[$src]) || !$img_size[$src]) && is_file($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src)){
				$img_size[$src] = @getimagesize($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src);
			}
		} else {
			if(is_file($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src)){		
				$img_size[$src] = @getimagesize($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src);
			}
		}
		$return .= ' ' . $img_size[$src];
	}

	// ## insert alt tag if there isn't one
	if (!isset($args['alt']) || !$args['alt']) $return .= " alt=\"$src\"";

	$return .= ('>');
	if ($display) {
		print $return;
	} else {
		return $return;
	}
}

function html_get_timezone_popup ($title='timezone',$selected='xzxzxzx') {
    global $TZs;
    return html_build_select_box_from_arrays ($TZs,$TZs,$title,$selected,false);
}

/**
 * html_get_language_popup() - Pop up box of supported languages
 *
 * @param		object	BaseLanguage object
 * @param		string	The title of the popup box
 * @param		string	Which element of the box is to be selected
 */
function html_get_language_popup ($Language,$title='language_id',$selected='xzxzxz') {
  global $Language;
	$res=$Language->getLanguages();
	return html_build_select_box ($res,$title,$selected,false);
}


function html_build_list_table_top ($title_arr,$links_arr=false,$mass_change=false,$full_width=true, $id=null, $class=null, $cellspacing=1, $cellpadding=2) {

	/*
		Takes an array of titles and builds
		The first row of a new table

		Optionally takes a second array of links for the titles
	*/
	GLOBAL $HTML;
	$return = '
       <TABLE ';
        if ($full_width) $return.='WIDTH="100%" ';
        if($id) $return .='id="'.$id.'"';
        if($class) $return .=' class="'.$class.'" ';
	$return .= 'BORDER="0" CELLSPACING="'. $cellspacing .'" CELLPADDING="'. $cellpadding .'">
		<TR class="boxtable">';

	if ($mass_change) $return .= '<TD class="boxtitle">Select?</TD>';
	$count=count($title_arr);
	if ($links_arr) {
		for ($i=0; $i<$count; $i++) {
			$return .= '
			<TD class="boxtitle"><a class=sortbutton href="'.$links_arr[$i].'">'.$title_arr[$i].'</A></TD>';
		}
	} else {
		for ($i=0; $i<$count; $i++) {
			$return .= '
			<TD class="boxtitle">'.$title_arr[$i].'</TD>';
		}
	}
	return $return.'</TR>';
}

//deprecated
function util_get_alt_row_color ($i) {
	return html_get_alt_row_color ($i);
}

//function util_get_alt_row_color ($i) {
function html_get_alt_row_color ($i) {
	GLOBAL $HTML;
	if ($i % 2 == 0) {
		return 'boxitem';
	} else {
		return 'boxitemalt';
	}
}

function html_build_select_box_from_array ($vals,$select_name,$checked_val='xzxz',$samevals = 0) {
	/*
		Takes one array, with the first array being the "id" or value
		and the array being the text you want displayed

		The second parameter is the name you want assigned to this form element

		The third parameter is optional. Pass the value of the item that should be checked
	*/

	$return = '
		<SELECT NAME="'.$select_name.'" id="'.$select_name.'">';

	$rows=count($vals);

	for ($i=0; $i<$rows; $i++) {
		if ( $samevals ) {
			$return .= "\n\t\t<OPTION VALUE=\"" . $vals[$i] . "\"";
			if ($vals[$i] == $checked_val) {
				$return .= ' SELECTED';
			}
		} else {
			$return .= "\n\t\t<OPTION VALUE=\"" . $i .'"';
			if ($i == $checked_val) {
				$return .= ' SELECTED';
			}
		}
		$return .= '>'.$vals[$i].'</OPTION>';
	}
	$return .= '
		</SELECT>';

	return $return;
}

function html_build_select_box_from_arrays ($vals,$texts,$select_name,$checked_val='xzxz',$show_100=true,$text_100='',$show_any=false,$text_any='',$show_unchanged=false,$text_unchanged='', $purify_level=CODEX_PURIFIER_DISABLED) {
        global $Language;
        $return = '';
        $isAValueSelected = false;
        $hp =& CodeX_HTMLPurifier::instance();
        
	/*

		The infamous '100 row' has to do with the
			SQL Table joins done throughout all this code.
		There must be a related row in users, categories, etc, and by default that
			row is 100, so almost every pop-up box has 100 as the default
		Most tables in the database should therefore have a row with an id of 100 in it
			so that joins are successful

		There is now another infamous row called the Any row. It is not
		in any table as opposed to 100. it's just here as a convenience mostly
		when using select boxes in queries (bug, task,...). The 0 value is reserved
		for Any and must not be used in any table.

		Params:

		Takes two arrays, with the first array being the "id" or value
		and the other array being the text you want displayed

		The third parameter is the name you want assigned to this form element

		The fourth parameter is optional. Pass the value of the item that should be checked

		The fifth parameter is an optional boolean - whether or not to show the '100 row'

		The sixth parameter is optional - what to call the '100 row' defaults to none
		The 7th parameter is an optional boolean - whether or not to show the 'Any row'

		The 8th parameter is optional - what to call the 'Any row' defaults to nAny	*/

        // Position default values for special menu items
        if ($text_100 == '') { $text_100 = $Language->getText('global','none'); }
        if ($text_any == '') { $text_any = $Language->getText('global','any'); }
        if ($text_unchanged == '') { $text_unchanged = $Language->getText('global','unchanged'); }

	if ( is_array($checked_val) ) {
		$return .= '
			<SELECT id="'.$select_name.'" NAME="'.$select_name.'[]" MULTIPLE SIZE="6">';
	} else {
		$return .= '
			<SELECT id="'.$select_name.'" NAME="'.$select_name.'">';
	}

	/*
		Put in the Unchanged box
	*/
	if ($show_unchanged) {
	  $return .= "\n".'<OPTION VALUE="'.$text_unchanged.'" SELECTED>'.$hp->purify($text_unchanged, $purify_level).'</OPTION>';
      $isAValueSelected = true;
	}

	//we don't always want the default any  row shown
	if ($show_any) {
		if ( is_array($checked_val) ) {
			if ( in_array(0,$checked_val) ) {
				$selected = "SELECTED";
                $isAValueSelected = true;
			} else {
				$selected = "";
			}
		} else {
	    	$selected = ( $checked_val == 0 ? 'SELECTED':'');
            if ($checked_val == 0) { 
                $isAValueSelected = true;
            }
	    }
	    $return .= "\n<OPTION VALUE=\"0\" $selected>".$hp->purify($text_any, $purify_level)."</OPTION>";
	}

	//we don't always want the default 100 row shown
	if ($show_100) {
		if ( is_array($checked_val) ) {
			if ( in_array(100,$checked_val) ) {
				$selected = "SELECTED";
                $isAValueSelected = true;
			} else {
				$selected = "";
			}
		} else {
		    $selected = ( $checked_val == 100 ? 'SELECTED':'');
            if ($checked_val == 100) {
                $isAValueSelected = true;
            }
		}
	    $return .= "\n<OPTION VALUE=\"100\" $selected>".$hp->purify($text_100,$purify_level)."</OPTION>";
	}

	$rows=count($vals);
	if (count($texts) != $rows) {
		$return .= 'ERROR - uneven row counts';
	}

	for ($i=0; $i<$rows; $i++) {
	    //  uggh - sorry - don't show the 100 row and Any row
	    //  if it was shown above, otherwise do show it
	    if ( (($vals[$i] != '100') && ($vals[$i] != '0')) || 
		 ($vals[$i] == '100' && !$show_100) ||
		 ($vals[$i] == '0' && !$show_any) ) {
			$return .= '
				<OPTION VALUE="'.$vals[$i].'"';
			if ( is_array($checked_val) ) {
				if ( in_array($vals[$i],$checked_val) ) {
					$return .= ' SELECTED';
                    $isAValueSelected = true;
				}
			} else {
				if ($vals[$i] == $checked_val) {
					$return .= ' SELECTED';
                    $isAValueSelected = true;
				}
			}
			$return .= '>'.$hp->purify($texts[$i],$purify_level).'</OPTION>';
		}
	}
    if ($checked_val && $checked_val != 'xzxz' && ! $isAValueSelected) {
        $return .= '<OPTION VALUE="'.$checked_val.'" SELECTED>'.$hp->purify($Language->getText('include_html','unknown_value'),$purify_level).'</OPTION>';
    }
    $return .= '
		</SELECT>';
	return $return;
}

function html_build_select_box ($result, $name, $checked_val="xzxz",$show_100=true,$text_100='',$show_any=false,$text_any='',$show_unchanged=false,$text_unchanged='', $purify_level=CODEX_PURIFIER_DISABLED) {
        global $Language;
	/*
		Takes a result set, with the first column being the "id" or value
		and the second column being the text you want displayed

		The second parameter is the name you want assigned to this form element

		The third parameter is optional. Pass the value of the item that should be checked

		The fourth parameter is an optional boolean - whether or not to show the '100 row'

		The fifth parameter is optional - what to call the '100 row' defaults to none
	*/

        // Position default values for special menu items
        if ($text_100 == '') { $text_100 = $Language->getText('global','none'); }
        if ($text_any == '') { $text_any = $Language->getText('global','any'); }
        if ($text_unchanged == '') { $text_unchanged = $Language->getText('global','unchanged'); }

	return html_build_select_box_from_arrays (util_result_column_to_array($result,0),util_result_column_to_array($result,1),$name,$checked_val,$show_100,$text_100,$show_any,$text_any,$show_unchanged,$text_unchanged, $purify_level);
}

function html_build_multiple_select_box($result,$name,$checked_array,$size='8',$show_100=true,$text_100='', $show_any=false,$text_any='',$show_unchanged=false,$text_unchanged='',$show_value=true) {
    if (is_array($result)) {
        $array =& $result;
    } else {
        $array = array();
        while($row = db_fetch_array($result)) {
            $array[] = array('value' => $row[0], 'text' => $row[1]);
        }
    }
    return html_build_multiple_select_box_from_array($array,$name,$checked_array,$size,$show_100,$text_100, $show_any,$text_any,$show_unchanged,$text_unchanged,$show_value);
}
function html_build_multiple_select_box_from_array($array,$name,$checked_array,$size='8',$show_100=true,$text_100='', $show_any=false,$text_any='',$show_unchanged=false,$text_unchanged='',$show_value=true) {
        global $Language;
	/*
		Takes a result set, with the first column being the "id" or value
		and the second column being the text you want displayed

		The second parameter is the name you want assigned to this form element

		The third parameter is an array of checked values;

		The fourth parameter is optional. Pass the size of this box

		Fifth to eigth params determine whether to show None and Any

		Ninth param determine whether to show numeric values next to
		the menu label (default true for backward compatibility
	*/

        // Position default values for special menu items
        if ($text_100 == '') { $text_100 = $Language->getText('global','none'); }
        if ($text_any == '') { $text_any = $Language->getText('global','any'); }
        if ($text_unchanged == '') { $text_unchanged = $Language->getText('global','unchanged'); }

	$checked_count=count($checked_array);
//      echo '-- '.$checked_count.' --';
    $id = str_replace('[]', '', $name);
	$return = '
		<SELECT NAME="'.$name.'" id="'.$id.'" MULTIPLE SIZE="'.$size.'">';

	/*
		Put in the Unchanged box
	*/
	if ($show_unchanged)
	  $return .= "\n".'<OPTION VALUE="'.$text_unchanged.'" SELECTED>'.$text_unchanged.'</OPTION>';

	/*
		Put in the Any box
	*/
	if ($show_any) {
	    $return .= '
		<OPTION VALUE="0"';
	    for ($j=0; $j<$checked_count; $j++) {
		if ($checked_array[$j] == '0') {
		    $return .= ' SELECTED';
		}
	    }
	    $return .= '>'.$text_any.'</OPTION>';
	}

	/*
		Put in the default NONE box
	*/
	if ($show_100) {
	    $return .= '
		<OPTION VALUE="100"';
	    for ($j=0; $j<$checked_count; $j++) {
		if ($checked_array[$j] == '100') {
		    $return .= ' SELECTED';
		}
	    }
	    $return .= '>'.$text_100.'</OPTION>';
	}

	foreach($array as $row) {
        $val = $row['value'];
        if ($val != '100') {
			$return .= '
				<OPTION VALUE="'.$val.'"';
			/*
				Determine if it's checked
			*/
			for ($j=0; $j<$checked_count; $j++) {
				if ($val == $checked_array[$j]) {
					$return .= ' SELECTED';
				}
			}
			$return .= '>'. ($show_value?$val.'-':'').
			    substr($row['text'],0,60). '</OPTION>';
		}
	}
	$return .= '
		</SELECT>';
    return $return;
}

function html_buildpriority_select_box ($name='priority', $checked_val='5') {
	/*
		Return a select box of standard priorities.
		The name of this select box is optional and so is the default checked value
	*/
  global $Language;
	?>
	<SELECT NAME="<?php echo $name; ?>">
    <OPTION VALUE="1"<?php if ($checked_val=="1") {echo " SELECTED";} ?>>1 - <?php echo $Language->getText('include_html','lowest'); ?></OPTION>
	<OPTION VALUE="2"<?php if ($checked_val=="2") {echo " SELECTED";} ?>>2</OPTION>
	<OPTION VALUE="3"<?php if ($checked_val=="3") {echo " SELECTED";} ?>>3</OPTION>
	<OPTION VALUE="4"<?php if ($checked_val=="4") {echo " SELECTED";} ?>>4</OPTION>
	<OPTION VALUE="5"<?php if ($checked_val=="5") {echo " SELECTED";} ?>>5 - <?php echo $Language->getText('include_html','medium'); ?></OPTION>
	<OPTION VALUE="6"<?php if ($checked_val=="6") {echo " SELECTED";} ?>>6</OPTION>
	<OPTION VALUE="7"<?php if ($checked_val=="7") {echo " SELECTED";} ?>>7</OPTION>
	<OPTION VALUE="8"<?php if ($checked_val=="8") {echo " SELECTED";} ?>>8</OPTION>
	<OPTION VALUE="9"<?php if ($checked_val=="9") {echo " SELECTED";} ?>>9 - <?php echo $Language->getText('include_html','highest'); ?></OPTION>
	</SELECT>
<?php

}

function html_buildcheckboxarray($options,$name,$checked_array) {
	$option_count=count($options);
	$checked_count=count($checked_array);

	for ($i=1; $i<=$option_count; $i++) {
		echo '
			<BR><INPUT type="checkbox" name="'.$name.'" value="'.$i.'"';
		for ($j=0; $j<$checked_count; $j++) {
			if ($i == $checked_array[$j]) {
				echo ' CHECKED';
			}
		}
		echo '> '.$options[$i];
	}
}

/*!     @function site_user_header
        @abstract everything required to handle security and
                add navigation for user pages like /my/ and /account/
        @param params array() must contain $user_id
        @result text - echos HTML to the screen directly
*/
function site_header($params) {
    GLOBAL $HTML;
    /*
                Check to see if active user
                Check to see if logged in
    */

    if (isset($params['group'])) {
	  $project=project_get_object($params['group']);
	  if ($project->isTemplate()) {
	    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('include_layout','template_warning'));
	  }
	}
    echo $HTML->header($params);
    echo html_feedback_top($GLOBALS['feedback']);
}

function site_footer($params) {
    GLOBAL $HTML;
    echo html_feedback_bottom($GLOBALS['feedback']);
    $HTML->footer($params);
}


/*! 	@function site_project_header
	@abstract everything required to handle security and state checks for a project web page
	@param params array() must contain $toptab and $group
	@result text - echos HTML to the screen directly
*/
function site_project_header($params) {
  GLOBAL $HTML, $Language;

	/*
		Check to see if active
		Check to see if project rather than foundry
		Check to see if private (if private check if user_ismember)
	*/

	$group_id=$params['group'];

	//get the project object 
	$project=project_get_object($group_id);

	//group doesn't exist
	if ($project->isError()) {
		exit_error($Language->getText('include_html','invalid_g'),$Language->getText('include_html','g_not_exist'));
	}

	//group is private
	if (!$project->isPublic()) {
		//if its a private group, you must be a member of that group
		session_require(array('group'=>$group_id));
	}

	//for dead projects must be member of admin project
	if (!$project->isActive()) {
		//only SF group can view non-active, non-holding groups
		session_require(array('group'=>'1'));
	}

        if (isset($params['pv']) && $params['pv'] != 0) {
            // Printer version: no right column, no tabs...
            echo $HTML->pv_header($params);
        } else {
            site_header($params);
        }
}

/*!     @function site_project_footer
	@abstract currently a simple shim that should be on every project page, 
		rather than a direct call to site_footer() or theme_footer()
	@param params array() empty
	@result text - echos HTML to the screen directly
*/
function site_project_footer($params) {
	GLOBAL $HTML;

        if (isset($params['pv']) && $params['pv'] != 0) {
            // Printer version
            echo $HTML->pv_footer($params);
        } else {
            echo html_feedback_bottom($GLOBALS['feedback']);
            echo $HTML->footer($params);
        }
}


function html_display_boolean($value,$true_value='Yes',$false_value='No') {
    global $Language;

    // Position default values for special menu items
    if (!isset($true_value)) { $true_value = $Language->getText('global','yes'); }
    if (!isset($false_value)) { $false_value = $Language->getText('global','no'); }
    if ( ($value == 1)||($value == true) ) {
        echo $true_value;
    } else {
        echo $false_value;
    }
}

function html_trash_image($alt) {
    return '<img src="'.util_get_image_theme("ic/trash.png").'" '.
        'height="16" width="16" border="0" alt="'.$alt.'" title="'.$alt.'">';
}

function html_trash_link($link, $warn, $alt) {
    return '<a href="'.$link.'" onClick="return confirm(\''.$warn.'\')">'.html_trash_image($alt).'</a>';
}

/**
 * 
 *  Returns a date operator field
 * 
 *  @param value: initial value
 *  @param ro: if true, the field is read-only
 *
 *	@return	string
 */
function html_select_operator($name='', $value='', $ro=false) {    
    if ($ro) {
        $html = htmlspecialchars($value);
    } else {
        $html = '<select name="'.$name.'">'.
			'<option value="1"'.(($value == '1') ? 'selected="selected"':'').'>&gt;</option>'.
			'<option value="0"'.(($value == '0') ? 'selected="selected"':'').'>=</option>'.
			'<option value="-1"'.(($value == '-1') ? 'selected="selected"':'').'>&lt;</option>'.
			'</select>';
    }
    return($html);	
}

/**
 *  Returns a date field
 * 
 *  @param value: initial value
 *  @param size: the field size
 *  @param maxlength: the max field size
 *  @param ro: if true, the field is read-only
 *
 *	@return	string
 */
function html_field_date($field_name='',
                         $value='',
                         $ro=false,
                         $size='10',
                         $maxlength='10',
                         $form_name='artifact_form',
                         $today=false) {
    if ($ro) {
        $html = $value;
    }
    else {
		$timeval = ($today ? 'null' : 'document.'.$form_name.'.elements[\''.$field_name.'\'].value'); 
        
		$html = '<input type="text" name="'.$field_name.'"'.
            ' size="'.$size.'"'.
            ' maxlength="'.$maxlength.'"'.
            ' value="'.$value.'" />'.
            '<a href="javascript:show_calendar(\'document.'.$form_name.'.elements[\\\''.$field_name.'\\\']\','.$timeval.',\''.util_get_css_theme().'\',\''.util_get_dir_image_theme().'\');">'.
            '<img src="'.util_get_image_theme("calendar/cal.png").'" width="16" height="16" border="0" alt="pick_date"></a>';
    }
    return($html);
}

?>
