<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

  //$Language->loadLanguageMsg('include/include');

// This function returns a string of the date $value with the format $format and
// if this date is not set, return the default value $default_value
function format_date($format,$value,$default_value = '-') {
    if ( $value == 0 ) {
        return $default_value;
    } else {
        return date($format,$value);
    }
}


// Convert a date in sys_datefmt (Y-M-d H:i ex: 2004-Feb-03 16:13)
// into a Unix time. if string is empty return 0 (Epoch time)
// Returns a list with two values: the unix time and a boolean saying whether the conversion
// went well (true) or bad (false)
function util_importdatefmt_to_unixtime($date) {
    $time = 0;
    if (!$date||$date=="") {
    	return array($time,false);
    }

    if (strstr($date,"/") !== false) {
      list($year,$month,$day,$hour,$minute) = util_xlsdatefmt_explode($date);
      $time = mktime($hour, $minute, 0, $month, $day, $year);
      return array($time,true);
    }
    
    if (strstr($date,"-") !== false) {
      list($year,$month,$day,$hour,$minute) = util_sysdatefmt_explode($date);
      $time = mktime($hour, $minute, 0, $month, $day, $year);
      return array($time,true);
    }

    return array($time,false);
}

// Explode a date in the form of (n/j/Y H:i) into its a list of 5 parts (YYYY,MM,DD,H,i)
// if DD and MM are not defined then default them to 1
function util_xlsdatefmt_explode($date) {

  $res = preg_match("/\s*(\d+)\/(\d+)\/(\d+) (\d+):(\d+)/",$date,$match);
  if ($res == 0) { 
    //if it doesn't work try (n/j/Y) only
    $res = preg_match("/\s*(\d+)\/(\d+)\/(\d+)/",$date,$match);
    if ($res == 0) { 
      // nothing is valid return Epoch time
      $year = '1970'; $month='1'; $day='1'; $hour='0'; $minute='0';
    } else {
      list(,$month,$day,$year) = $match; $hour='0'; $minute='0';
    }
  } else {
    list(,$month,$day,$year,$hour,$minute) = $match;
  }

  return array($year,$month,$day,$hour,$minute);
}


// Convert a date as used in the bug tracking system and other services (YYYY-MM-DD)
// into a Unix time. if string is empty return 0 (Epoch time)
// Returns a list with two values: the unix time and a boolean saying whether the conversion
// went well (true) or bad (false)
function util_date_to_unixtime($date) {
    $time = 0;
    if (!$date||$date=="") {
    	return array($time,false);
    }
    
	list($year,$month,$day) = util_date_explode($date);
	$time = mktime(0, 0, 0, $month, $day, $year);
    return array($time,true);
}

// Explode a date in the form of (YYYY-MM-DD) into its a list of 3 parts (YYYY,MM,DD)
// if DD and MM are not defined then default them to 1
function util_date_explode($date) {
    $res = preg_match("/\s*(\d+)-(\d+)-(\d+)/",$date,$match);
    if ($res == 0) { 
	// if it doesn't work try YYYY-MM only
	$res = preg_match("/\s*(\d+)-(\d+)/",$date,$match);
	if ($res == 0) {
	    // if it doesn't work try YYYY only
	    $res = preg_match("/\s*(\d+)/",$date,$match);return array('1970','1','1');
	    if ($res == 0) {
		// nothing is valid return Epoch time
		$year = '1970'; $month='1'; $day='1';
	    } else {
		list(,$year) = $match ; $month='1'; $day='1';
	    }
	    
	} else {
	    list(,$year,$month) = $match ; $day='1';
	}
	
    } else {
	list(,$year,$month,$day) = $match;
    }
    return array($year,$month,$day);
}

// Convert a date in sys_datefmt (Y-M-d H:i ex: 2004-Feb-03 16:13)
// into a Unix time. if string is empty return 0 (Epoch time)
// Returns a list with two values: the unix time and a boolean saying whether the conversion
// went well (true) or bad (false)
function util_sysdatefmt_to_unixtime($date) {
    $time = 0;
    if (!$date||$date=="") {
    	return array($time,false);
    }
    
    list($year,$month,$day,$hour,$minute) = util_sysdatefmt_explode($date);
    $time = mktime($hour, $minute, 0, $month, $day, $year);
    return array($time,true);
}

// Explode a date in the form of (Y-M-d H:i) into its a list of 5 parts (YYYY,MM,DD,H,i)
// if DD and MM are not defined then default them to 1
function util_sysdatefmt_explode($date) {
  $months = array("Jan"=>1, "Feb"=>2, "Mar"=>3, "Apr"=>4, "May"=>5, "Jun"=>6, "Jul"=>7, "Aug"=>8, "Sep"=>9, "Oct"=>10, "Nov"=>11, "Dec"=>12);

  $res = preg_match("/\s*(\d+)-(.+)-(\d+) (\d+):(\d+)/",$date,$match);
  if ($res == 0) { 
    //if it doesn't work try (Y-M-d) only
    $res = preg_match("/\s*(\d+)-(.+)-(\d+)/",$date,$match);
    if ($res == 0) { 
      
      // if it doesn't work try Y-M only
      $res = preg_match("/\s*(\d+)-(.+)/",$date,$match);
      if ($res == 0) {
	// if it doesn't work try YYYY only
	$res = preg_match("/\s*(\d+)/",$date,$match);
	if ($res == 0) {
	  // nothing is valid return Epoch time
	  $year = '1970'; $month='1'; $day='1'; $hour='0'; $minute='0';
	} else {
	  list(,$year) = $match ; $month='1'; $day='1'; $hour='0'; $minute='0';
	}
	
      } else {
	list(,$year,$month) = $match ; $day='1'; $hour='0'; $minute='0';
      }
      
    } else {
      list(,$year,$month,$day) = $match; $hour='0'; $minute='0';
    }
  } else {
    list(,$year,$month,$day,$hour,$minute) = $match;
  }

  return array($year,getMonth($month,$ok),$day,$hour,$minute);
}

//accept now month either in format Jan-Dec or 1-12
function getMonth($month,&$ok) {
  $months = array("Jan"=>1, "Feb"=>2, "Mar"=>3, "Apr"=>4, "May"=>5, "Jun"=>6, "Jul"=>7, "Aug"=>8, "Sep"=>9, "Oct"=>10, "Nov"=>11, "Dec"=>12);
  if (array_key_exists($month,$months)) {
    $ok = true;
    return $months[$month];
  } else if (in_array($month,$months)) {
    $ok = true;
    return $month;
  } 
  $ok = false; 
  return 1;

}

function util_prep_string_for_sendmail($body) {
	$body=str_replace("\\","\\\\",$body);
	$body=str_replace("\"","\\\"",$body);
	$body=str_replace("\$","\\\$",$body);
        $body=str_replace("`","\\`",$body);
	return $body;
}

function util_unconvert_htmlspecialchars($string) {
	if (strlen($string) < 1) {
		return '';
	} else {
		$string=str_replace('&nbsp;',' ',$string);
		$string=str_replace('&quot;','"',$string);
		$string=str_replace('&gt;','>',$string);
		$string=str_replace('&lt;','<',$string);
		$string=str_replace('&amp;','&',$string);
		return $string;
	}
}

function util_result_column_to_array($result, $col=0) {
	/*
		Takes a result set and turns the optional column into
		an array
	*/
	$rows=db_numrows($result);

	if ($rows > 0) {
		$arr=array();
		for ($i=0; $i<$rows; $i++) {
			$arr[$i]=db_result($result,$i,$col);
		}
	} else {
		$arr=array();
	}
	return $arr;
}

function merge_hashtable ($arr_1, $arr_2) {
    while (list($k,$v) = each($arr_1)) {
        if (!in_array($v,$arr_2)) {
            $arr_2[$k] = $v;
        }
    }
    return $arr_2;
}

function util_result_build_array($result, $col_id=0, $col_value=1) {
	$rows=db_numrows($result);

	if ($rows > 0) {
		$arr=array();
		for ($i=0; $i<$rows; $i++) {
			$arr[db_result($result,$i,$col_id)]=db_result($result,$i,$col_value);
		}
	} else {
		$arr=array();
	}
	return $arr;
}

function result_column_to_array($result, $col=0) {
	/*
		backwards compatibility
	*/
	return util_result_column_to_array($result, $col);
}

function util_wrap_find_space($string,$wrap) {
	//echo"\n";
	$start=$wrap-5;
	$try=1;
	$found=false;
	
	while (!$found) {
		//find the first space starting at $start
		$pos=@strpos($string,' ',$start);
		
		//if that space is too far over, go back and start more to the left
		if (($pos > ($wrap+5)) || !$pos) {
			$try++;
			$start=($wrap-($try*5));
			//if we've gotten so far left , just truncate the line
			if ($start<=10) {
				return $wrap;
			}       
			$found=false;
		} else {
			$found=true;
		}       
	}       
	
	return $pos;
}

function util_line_wrap ($text, $wrap = 80, $break = "\n") {
	$paras = explode("\n", $text);
			
	$result = array();
	$i = 0;
	while ($i < count($paras)) {
		if (strlen($paras[$i]) <= $wrap) {
			$result[] = $paras[$i];
			$i++;
		} else {
			$pos=util_wrap_find_space($paras[$i],$wrap);
			
			$result[] = substr($paras[$i], 0, $pos);
			
			$new = trim(substr($paras[$i], $pos, strlen($paras[$i]) - $pos));
			if ($new != '') {
				$paras[$i] = $new;
				$pos=util_wrap_find_space($paras[$i],$wrap);
			} else {
				$i++;
			}       
		}       
	}		       
	return implode($break, $result);
}

function util_make_links ($data='',$group_id = 0, $group_artifact_id = 0) {
    if(empty($data)) { return $data; }
    
    // www.yahoo.com => http://www.yahoo.com
    $data = eregi_replace("([ \t]|^)www\."," http://www.",$data);

    // http://www.yahoo.com => <a href="...">...</a>
    $data = eregi_replace("([[:alnum:]]+)://([^[:space:]<]*)([[:alnum:]>#?/&=])", "<a href=\"\\1://\\2\\3\" target=\"_blank\" target=\"_new\">\\1://\\2\\3</a>", $data);

    // john.doe@yahoo.com => <a href="mailto:...">...</a>
    $data = eregi_replace("(([a-z0-9_]|\\-|\\.)+@([^[:space:]<&>]*)([[:alnum:]-]))", "<a href=\"mailto:\\1\" target=\"_new\">\\1</a>", $data);

    if ($group_id) { $group_param="&group_id=$group_id";}
    $data = eregi_replace("([^[:blank:]()\$\&\!\;\~\#\|\{\}\%\,\?\=\+\'\"\.\:\/\>]+)[ ]?#([0-9]+)", "<a href=\"/tracker/?func=gotoid$group_param&aid=\\2&atn=\\1\">\\1 #\\2</a>", $data);

    return $data;
}

function util_user_link ($username) {
  global $Language;
    if ( $username == $Language->getText('global','none') || empty($username)) { return $username; }
    return '<a href="/users/'.$username.'">'.$username.'</a>';
}

function util_multi_user_link ($usernames) {
	
	$users = explode(", ",$usernames);
	if ( count($users) > 1 ) {
		// Multiple users
				
		$str = "";
		for($i=0;$i<count($users)-1;$i++) {
			$str .= util_user_link($users[$i]).", ";
		}
		$str .= util_user_link($users[$i]);
		return $str;
		
	} else {
		// Single user name
		return util_user_link ($usernames);
	}
}

function util_double_diff_array($arr1, $arr2) {
    
    // first transform both arrays in hashes
    reset($arr1); reset($arr2);
    while ( list(,$v) = each($arr1)) { $h1[$v] = $v; }
    while ( list(,$v) = each($arr2)) { $h2[$v] = $v; }

    $deleted = array();
    while ( list($k,) = each($h1)) {
	if (!isset($h2[$k])) { $deleted[] = $k; }
    }

    $added = array();
    while ( list($k,) = each($h2)) {
	if (!isset($h1[$k])) { $added[] = $k; }
    }

    return array($deleted, $added);
}

function show_priority_colors_key($msg='') {
  global $Language;

	echo '<P class="small"><B>'.($msg ? $msg : $Language->getText('include_utils','prio_colors').':').'</B><BR>

		<TABLE BORDER=0><TR>';

	for ($i=1; $i<10; $i++) {
		echo '
			<TD class="'.get_priority_color($i).'">'.$i.'</TD>';
	}
	echo '</tr></table>';
}


function get_priority_color ($index) {
	/*
		Return the color value for the index that was passed in
		(defined in $sys_urlroot/css/<selected theme>)
	*/
	global $bgpri;
	
	return $bgpri[$index];
}

function build_priority_select_box ($name='priority', $checked_val='5') {
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

// ########################################### checkbox array
// ################# mostly for group languages and environments

function utils_buildcheckboxarray($options,$name,$checked_array) {
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

Function GraphResult($result,$title) {

/*
	GraphResult by Tim Perdue, PHPBuilder.com

	Takes a database result set.
	The first column should be the name,
	and the second column should be the values

	####
	####   Be sure to require(HTML_Graphs.php) before hitting these graphing functions
	####
*/

	/*
		db_ should be replaced with your database, aka mysql_ or pg_
	*/
	$rows=db_numrows($result);

	if ((!$result)) {
		echo 'None Found.';
	} else {
		$names=array();
		$values=array();

		for ($j=0; $j<db_numrows($result); $j++) {
			if (db_result($result, $j, 0) != '' && db_result($result, $j, 1) != '' ) {
				$names[$j]= db_result($result, $j, 0);
				$values[$j]= db_result($result, $j, 1);
			}
		}

	/*
		This is another function detailed below
	*/
		GraphIt($names,$values,$title);
	}
}

Function GraphIt($name_string,$value_string,$title) {

	/*
		GraphIt by Tim Perdue, PHPBuilder.com
	*/
	$counter=count($name_string);

	/*
		Can choose any color you wish
	*/
	$bars=array();

	for ($i = 0; $i < $counter; $i++) {
		$bars[$i]=util_get_image_theme('bargraph.png');
	}

	$counter=count($value_string);

	/*
		Figure the max_value passed in, so scale can be determined
	*/

	$max_value=0;

	for ($i = 0; $i < $counter; $i++) {
		if ($value_string[$i] > $max_value) {
			$max_value=$value_string[$i];
		}
	}

	if ($max_value < 1) {
		$max_value=1;
	}

	/*
		I want my graphs all to be 800 pixels wide, so that is my divisor
	*/

	$scale=(400/$max_value);

	/*
		I create a wrapper table around the graph that holds the title
	*/

	$title_arr=array();
	$title_arr[]=$title;

	echo html_build_list_table_top ($title_arr);
	echo '<TR><TD>';
	/*
		Create an associate array to pass in. I leave most of it blank
	*/

	$vals =  array(
	'vlabel'=>'',
	'hlabel'=>'',
	'type'=>'',
	'cellpadding'=>'',
	'cellspacing'=>'0',
	'border'=>'',
	'width'=>'',
	'background'=>'',
	'vfcolor'=>'',
	'hfcolor'=>'',
	'vbgcolor'=>'',
	'hbgcolor'=>'',
	'vfstyle'=>'',
	'hfstyle'=>'',
	'noshowvals'=>'',
	'scale'=>$scale,
	'namebgcolor'=>'',
	'valuebgcolor'=>'',
	'namefcolor'=>'',
	'valuefcolor'=>'',
	'namefstyle'=>'',
	'valuefstyle'=>'',
	'doublefcolor'=>'');

	/*
		This is the actual call to the HTML_Graphs class
	*/

	html_graph($name_string,$value_string,$bars,$vals);

	echo '
		</TD></TR></TABLE>
		<!-- end outer graph table -->';
}

Function  ShowResultSet($result,$title="Untitled",$linkify=false)  {
	global $group_id,$HTML;
	/*
		Very simple, plain way to show a generic result set
		Accepts a result set and title
		Makes certain items into HTML links
	*/

	if  ($result)  {
		$rows  =  db_numrows($result);
		$cols  =  db_numfields($result);

		echo '
			<TABLE BORDER="0" WIDTH="100%">';

		/*  Create the title  */

		echo '
		<TR class="boxtitle">
		<TD COLSPAN="'.$cols.'" class="boxitem"><B>'.$title.'</B></TD></TR>';

		/*  Create  the  headers  */
		echo '
			<tr>';
		for ($i=0; $i < $cols; $i++) {
			echo '<td><B>'.db_fieldname($result,  $i).'</B></TD>';
		}
		echo '</tr>';

		/*  Create the rows  */
		for ($j = 0; $j < $rows; $j++) {
			echo '<TR class="'. html_get_alt_row_color($j) .'">';
			for ($i = 0; $i < $cols; $i++) {
				if ($linkify && $i == 0) {
					$link = '<A HREF="'.$PHP_SELF.'?';
					$linkend = '</A>';
					if ($linkify == "bug_cat") {
						$link .= 'group_id='.$group_id.'&bug_cat_mod=y&bug_cat_id='.db_result($result, $j, 'bug_category_id').'">';
					} else if($linkify == "bug_group") {
						$link .= 'group_id='.$group_id.'&bug_group_mod=y&bug_group_id='.db_result($result, $j, 'bug_group_id').'">';
					} else if($linkify == "patch_cat") {
						$link .= 'group_id='.$group_id.'&patch_cat_mod=y&patch_cat_id='.db_result($result, $j, 'patch_category_id').'">';
					} else if($linkify == "support_cat") {
						$link .= 'group_id='.$group_id.'&support_cat_mod=y&support_cat_id='.db_result($result, $j, 'support_category_id').'">';
					} else if($linkify == "pm_project") {
						$link .= 'group_id='.$group_id.'&project_cat_mod=y&project_cat_id='.db_result($result, $j, 'group_project_id').'">';
					} else {
						$link = $linkend = '';
					}
				} else {
					$link = $linkend = '';
				}
				echo '<td>'.$link . db_result($result,  $j,  $i) . $linkend.'</td>';

			}
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo db_error();
	}
}

// Clean up email address (remove spaces...) and put to lower case
function util_cleanup_emails ($addresses) {
    return strtolower(preg_replace("/\s/","", $addresses));
}

// Clean up email address (remove spaces...) and add @... if it is a simple
// login name
function util_normalize_email ($address) {
    global $sys_users_host;
    $address = util_cleanup_emails($address);
    if (validate_email($address))
	return $address;
    else
	return $address."@$sys_users_host";
}

// Clean up email address (remove spaces...) and split comma or semi-colon separated emails
function util_split_emails($addresses) {
    $addresses = util_cleanup_emails($addresses);
    return split('[,;]',$addresses);
}

// One Email Verification
function validate_email ($address) {
	return (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'. '@'. '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.' . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $address));
}

// Verification of comma separated list of email addresses
function validate_emails ($addresses) {
    $arr = util_split_emails($addresses);
    while (list(, $addr) = each ($arr)) {
	if (!validate_email($addr)) { return false; echo "nV: $addr";}
    }	    
    return true;
}

/**
     * Return if the email addresses are valid
     *
     * @param arr_email: list of email addresses
     * @param message (OUT): error message if an error is found
     *
     * @return boolean
     */
    function util_validateCCList($arr_email, &$message) {
      global $Language;
        $valid = true;
        $message = "";
        
        while (list(,$cc) = each($arr_email)) {
            // Make sure that the address is valid
            if (! validate_email($cc)) {
                // check for a valid CodeX user.
                $res = user_get_result_set_from_unix($cc);
                if (db_numrows($res) == 0) {
                    $valid = false;
                    $message .= "$cc<br>";
                    continue;
                }
            }
        }
        
        if (! $valid) {
            $message = $Language->getText('include_utils','address_problem').":"
                . "<blockquote>$message</blockquote>"
                . $Language->getText('include_utils','email_explain');
        }
        
        return $valid;
    }


function util_is_valid_filename ($file) {
	if (ereg("[]~`! ~@#\"$%^,&*();=|[{}<>?/]",$file)) {
		return false;
	} else {
		if (strstr($file,'..')) {
			return false;
		} else {
			return true;
		}
	}
}

function util_get_content($file){
    // Default language
    $lang = $GLOBALS['sys_lang'];
    
    // Retrieve the user language if not guest
    // TODO

    // Test first the custom directory
    $custom_fn = getenv('SF_LOCAL_INC_PREFIX')."/etc/codex/site-content/".$lang."/".$file.".txt";   
    if ( file_exists($custom_fn) ) {
        // The custom file exists. 
        return $custom_fn;
    } else {
        // Use the default file
        // Check first if exist
        $fn = $GLOBALS['sys_incdir'].$lang."/".$file.".txt";
	if ( file_exists($fn) ) {
	    // The custom file exists. 
	    return $fn;
	} else {
	    // return empty content to avoid include error
	    return $GLOBALS['sys_incdir'].$lang."/others/empty.txt";
	}
    }
}

// Return the string value of fontsize
function getFontsizeName($value) {
    switch ( $value ) {
    case 1:
        // Small
        return "_small";
        break;
    case 2:
        // Normal
        return "_normal";
        break;
    case 3:
        // Large
        return "_large";
        break;
    default:
        return "_normal";
        break;
    }    
}

// this function get the css file for the theme
// Requirement: $sys_user_theme and $sys_user_font_size are already
// set (done by theme.php in pre.php)
//
function util_get_css_theme(){

    $path = $GLOBALS['sys_user_theme']."/".$GLOBALS['sys_user_theme'].
	getFontsizeName($GLOBALS['sys_user_font_size']).".css";

    if ($GLOBALS['sys_is_theme_custom'])
	$path = "/css/custom/".$path;
    else
	$path = "/css/".$path;

    return $path;
}

// This function get the image file for the theme.
// The theme may be specified as an optional second parameter.
// If no theme parameter is given, the current global theme is used.
// If $absolute is true, then the generated path will be absolute,
// otherwise it is relative to $sys_urlroot.
function util_get_image_theme($fn, $the_theme=false, $absolute=false){

    $path = util_get_dir_image_theme($the_theme);
    if ($absolute) {
      $path = $GLOBALS['sys_urlroot'] . $path;
    }

    return $path.$fn;
}

// this function get the image directory for the theme
// (either given or current theme)
function util_get_dir_image_theme($the_theme=false){

    if (! $the_theme) {
      $the_theme = $GLOBALS['sys_user_theme'];
    }

    if ($GLOBALS['sys_is_theme_custom'])
	$path = '/images/custom/'.$the_theme.'.theme/';
    else
	$path = '/images/'.$the_theme.'.theme/';

    return $path;
}

// Format a size in byte into a size in Mb
function formatByteToMb($size_byte) {
    return intval($size_byte/(1024*1024));
}


// Return a HTTP URL to a resource on the local host.
function make_local_url($path) {
    $info = parse_url("http://" . $GLOBALS['sys_default_domain']);
    $port = isset($info['port'])? ":".$info['port'] : "";
    return "http://localhost" . $port . "/" . $path;
}

// Return server URL
// Used e.g. when inserting links in emails
function get_server_url() {
    if (session_issecure()) {
        return "https://".$GLOBALS['sys_https_host'];
    } else {
        return "http://".$GLOBALS['sys_default_domain'];
    }
}


// Return mailing list server URL
// Used e.g. when inserting links in emails
function get_list_server_url() {
    if (session_issecure()) {
        return "https://".$GLOBALS['sys_lists_host'];
    } else {
        return "http://".$GLOBALS['sys_lists_host'];
    }
}


/**
 * util_check_fileupload() - determines if a filename is appropriate for upload
 *
 * @param	   string  The name of the file being uploaded
 */
function util_check_fileupload($filename) {

	/* Empty file is a valid file.
	This is because this function should be called
	unconditionally at the top of submit action processing
	and many forms have optional file upload. */
	if ($filename == 'none' || $filename == '') {
		return true;
	}

	/* This should be enough... */
	if (!is_uploaded_file($filename)) {
	  //echo "$filename is not uploaded file";
		return false;
	}
	/* ... but we'd rather be paranoic */
	if (strstr($filename, '..')) {
		return false;
	}
	if (!is_file($filename)) {
	  //echo "$filename is not file";
		return false;
	}
	if (!file_exists($filename)) {
	  //echo "$filename does not exist";
		return false;
	}
	return true;
}



/**
 * Return the group name (i.e. project name) from the group_id
 */
function util_get_group_name_from_id($group_id) {
    $sql = "SELECT group_name FROM groups WHERE group_id = ".$group_id;
    $result = db_query($sql);
    return db_result($result,0,0);
}


/**
 * Retrieve the artifact group_id, artifact_type_id and item name using the artifact id
 *
 * @param aid: the artifact id
 * @param group_id: the group id (OUT)
 * @param group_artifact_id: the tracker id (OUT)
 * @param art_name: the item name corresponding to this tracker (OUT) e.g. 'bug', 'defect', etc.
 *
 * @return boolean
 */
function util_get_ids_from_aid($aid,&$art_group_id,&$atid,&$art_name) {
    $sql = "SELECT group_artifact_id FROM artifact WHERE artifact_id = ".$aid;
    
    $result = db_query($sql);
    if ($result && db_numrows($result) > 0) {
        $atid = db_result($result,0,0);
        
        $sql = "SELECT group_id,item_name FROM artifact_group_list WHERE group_artifact_id = ".$atid;
        
        $result = db_query($sql);
        $rows=db_numrows($result);
        if (!$result || $rows < 1) {
            return false;
        }
        $art_group_id = db_result($result,0,'group_id');
        $art_name = db_result($result,0,'item_name');
        return true;
    } else {
        return false;
    }

}

/**
 * Return group id (i.e. project) the legacy artifact belongs to.
 * 
 * @param atn: the legacy tracker name in lower case: 'bug' 'sr' or 'task' exclusively
 * @param aid: the 'artifact' id
 *
 * @return group_id, or 0 if group does not exist
 */
function util_get_group_from_legacy_id($atn,$aid) {
    if ($atn=='bug') {
        $sql="select group_id from bug where bug_id=$aid";
    } else if ($atn=='sr') {
        $sql="select group_id from support where support_id=$aid";
    } else if ($atn=='patch') {
        $sql="select group_id from patch where patch_id=$aid";
    } else if ($atn=='task') {
        // A bit more complicated since the group_id and project_task_id are not in the same table...
        $sql="SELECT project_group_list.group_id FROM project_task,project_group_list".
            " WHERE project_task.group_project_id=project_group_list.group_project_id".
            " AND project_task.project_task_id=$aid";
    } else {
        return 0;
    }
    $result = db_query($sql);
    return db_result($result,0,0);
}    

/**
 * Return the group id (i.e. project) the commit belongs to
 * 
 * @param cid: the commit id
 *
 * @return group_id, or 0 if group does not exist
 */
function util_get_group_from_commit_id($cid) {
  $sql = "SELECT repositoryid FROM cvs_checkins WHERE commitid=$cid";
  $res = db_query($sql);
  $repository_id = db_result($res, 0, 'repositoryid');
  if (!$repository_id) return 0;

  $sql = "SELECT repository FROM cvs_repositories WHERE id=$repository_id";
  $res = db_query($sql);
  $repository = db_result($res, 0, 'repository');
  if (!$repository) return 0;

  // Remove "/cvsroot/" to get the project unix name
  $projname=eregi_replace("/cvsroot/","",$repository);
  if (!$projname) return 0;

  $sql = "SELECT group_id FROM groups WHERE unix_group_name='$projname'";
  $res = db_query($sql);
  return db_result($res, 0, 'group_id');
}    

/**
 * getStringFromServer - get a string from Server environment
 *
 * @param string $key key of the wanted value
 * @param string $defaultValue if we can't find the wanted value, it returns the default value
 * @return string the value
 */
function getStringFromServer($key) {
        $serverArray = & _getServerArray();
        if(isset($serverArray[$key])) {
                return $serverArray[$key];
        }
        else {
                return '';
        }
}

/**
 * _getServerArray - wrapper to get the SERVER array
 *
 * @return array the SERVER array
 */
function & _getServerArray() {
        return _getPredefinedArray('_SERVER', 'HTTP_SERVER_VARS');
}

/**
 * _getPredefinedArray - get one of the predefined array (GET, POST, COOKIE...)
 *
 * @param string $superGlobalName name of the super global array (_POST, _GET)
 * @param string $oldName name of the old array (HTTP_POST_VARS, HTTP_GET_VARS) for older php versions
 * @return array a predefined array
 */
function & _getPredefinedArray($superGlobalName, $oldName) {
        if(isset($$superGlobalName)) {
                $array = & $$superGlobalName;
        }
        elseif(isset($GLOBALS[$oldName])) {
                $array = & $GLOBALS[$oldName];
        }
        else {
                $array = array();
        }
        return $array;
}


/**
 * checkRestrictedAccess - check that a restricted user can access the current URL.
 *
 * @param string $request_uri is the original REQUEST_URI
 * @param string $script_name is the original SCRIPT_NAME
 * @return true is access is granted
 * @return false is access is forbidden
*/

function util_check_restricted_access($request_uri, $script_name) {
    // Note:
    // Currently, we don't restrict access to 'shownotes.php' and to tracker file attachment downloads

    if (user_isrestricted()) {
        // Make sure the URI starts with a single slash
        $req_uri='/'.trim($request_uri, "/");

        /* Examples of input params:
         Script: /projects, Uri=/projects/ljproj/
         Script: /survey/index.php, Uri=/survey/?group_id=101
         Script: /project/admin/index.php, Uri=/project/admin/?group_id=101
         Script: /tracker/index.php, Uri=/tracker/index.php?group_id=101
         Script: /tracker/index.php, Uri=/tracker/?func=detail&aid=14&atid=101&group_id=101
        */

        // Restricted users cannot access any page belonging to a project they are not a member of.
        // In addition, the following URLs are forbidden (value overriden in site-content file)
        $forbidden_url = array( 
          '/snippet/',     // Code Snippet Library
          '/softwaremap/', // browsable software map
          '/new/',         // list of the newest releases made on the CodeX site
          '/search/',      // search for people, projects, and artifacts in trackers!
          '/people/',      // people skills and profile
          '/stats/',       // CodeX site statistics
          '/top/',         // projects rankings (active, downloads, etc)
          '/register/',    // Register a new project
          '/export/',      // CodeX XML feeds
          '/info.php'      // PHP info
          );
        
        // Default values are very restrictive, but they can be overriden in the site-content file
        $allow_codex_welcome_page=false; // Allow access to CodeX welcome page (at e.g. http://codex.xerox.com/)
        $allow_news_browsing=false;      // Allow restricted users to read/comment news, including for their project
        $allow_user_browsing=false;      // Allow restricted users to access other user's page (Developer Profile)
        $allow_access_to_codex_forums=false;   // CodeX help forums are accessible through the 'Discussion Forums' link
        $allow_access_to_codex_trackers=false; // CodeX trackers are used for support requests on CodeX
        $allow_access_to_codex_docs=false; // CodeX documents (Note that the User Guide is always accessible)
        $allow_access_to_codex_mail=false; // CodeX mailing lists (Developers Channels)


        // Customizable security settings for restricted users:
        include(util_get_content('include/restricted_user_permissions'));
        // End of customization
        


        foreach ($forbidden_url as $str) {
            $pos = strpos($req_uri,$str);
            if ($pos === false) {
                // Not found
            } else {
                if ($pos == 0) {
                    // beginning of string
                    return false;
                }
            }
        }

        // Welcome page
        if (!$allow_codex_welcome_page) {
            $sc_name='/'.trim($script_name, "/");
            if ($sc_name == '/index.php') {
                return false;
            }
        }

        
        // Forbid access to other user's page (Developer Profile)
        if ((strpos($req_uri,'/users/') !== false)&&(!$allow_user_browsing)) {
            if ($req_uri != '/users/'.user_getname())
                return false;
        }


        // Get group_id for project pages that don't have it
        
        // /projects/ and /viewcvs/
        if ((strpos($req_uri,'/projects/') !== false)||(strpos($req_uri,'/viewcvs.php/') !== false)){
            // Check that the user is a member of this project
            if (strpos($req_uri,'/projects/') !== false){
                $pieces = explode("/", $request_uri);
                $this_proj_name=$pieces[2];
            } else if (strpos($req_uri,'/viewcvs.php/') !== false) {
                preg_match("/root=([a-zA-Z0-9_-]+)/",$req_uri, $matches);
                $this_proj_name=$matches[1];
            }
            $res_proj=db_query("SELECT group_id FROM groups WHERE unix_group_name='$this_proj_name'");
            if (db_numrows($res_proj) < 1) { # project does not exist
                return false;
            }
            $group_id=db_result($res_proj,0,'group_id');
        }
        
        
        // File downloads. It might be a good idea to restrict access to shownotes.php too...
        if (strpos($req_uri,'/file/download.php') !== false) {
            list(,$group_id, $file_id) = explode('/', $PATH_INFO);
        }
        
        // Now check special cases
        $user_is_allowed=false;
        
        // Forum and news. Each published news is a special forum of project 'news'
        if (strpos($req_uri,'/forum/') !== false) {
            if ($forum_id) {
                // Get corresponding project
                $result=db_query("SELECT group_id FROM forum_group_list WHERE group_forum_id='$forum_id'");
                $group_id=db_result($result,0,'group_id');
                // News
                if ($allow_news_browsing) {
                    if ($group_id==$GLOBALS['sys_news_group']) {
                        $user_is_allowed=true;
                    }
                }
                // CodeX forums
                if ($allow_access_to_codex_forums) {
                    if ($group_id==1) {
                        $user_is_allowed=true;
                    }
                }
            }
        }
        
        // CodeX trackers
        if (strpos($req_uri,'/tracker/') !== false) {
            if ($allow_access_to_codex_trackers) {
                if ($group_id==1) {
                    $user_is_allowed=true;
                }
            }
        }

        // CodeX documents
        if (strpos($req_uri,'/docman/') !== false) {
            if ($allow_access_to_codex_docs) {
                if ($group_id==1) {
                    $user_is_allowed=true;
                }
            }
        }
        
        // CodeX mailing lists page
        if (strpos($req_uri,'/mail/') !== false) {
            if ($allow_access_to_codex_mail) {
                if ($group_id==1) {
                    $user_is_allowed=true;
                }
            }
        }
        
        // Now check group_id
        if ($group_id) { 
            if (!$user_is_allowed) { 
                if (!user_ismember($group_id)) {
                    return false;
                }
            }
        }
    } 
    return true;
}


?>
