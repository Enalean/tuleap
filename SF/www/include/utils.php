<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

// This function returns a string of the date $value with the format $format and
// if this date is not set, return the default value $default_value
function format_date($format,$value,$default_value = '-') {
    if ( $value == 0 ) {
        return $default_value;
    } else {
        return date($format,$value);
    }
}

// Convert a date as used in the bug tracking system and other services (YYYY-MM-DD)
// into a Unix time. if string is empty return 0 (Epoch time)
// Returns a list with two values: the unix time and a boolean saying whether the conversion
// went well (true) or bad (false)
function util_date_to_unixtime($date) {
    $time = 0;
    if ($date) {
	list($year,$month,$day) = util_date_explode($date);
	$time = mktime(0, 0, 0, $month, $day, $year);
    }
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

function util_make_links ($data='',$group_id = 0) {
	if(empty($data)) { return $data; }

	$lines = split("\n",$data);
	while ( list ($key,$line) = each ($lines)) {
		$line = eregi_replace("([ \t]|^)www\."," http://www.",$line);
		$text = eregi_replace("([[:alnum:]]+)://([^[:space:]]*)([[:alnum:]>#?/&=])", "<a href=\"\\1://\\2\\3\" target=\"_blank\" target=\"_new\">\\1://\\2\\3</a>", $line);
		$text = eregi_replace("(([a-z0-9_]|\\-|\\.)+@([^[:space:]&>]*)([[:alnum:]-]))", "<a href=\"mailto:\\1\" target=\"_new\">\\1</a>", $text);
		// If $group_id is assigned then we can replace the pattern: bug #id task #id sr #id
		if ( $group_id ) {
		    $text = eregi_replace("bug[ ]?#([0-9]+)", "<a href=\"/bugs/?func=detailbug&bug_id=\\1&group_id=$group_id\">Bug #\\1</a>", $text);
		    $text = eregi_replace("task[ ]?#([0-9]+)", "<a href=\"/pm/task.php?func=detailtask&project_task_id=\\1&group_id=$group_id\">Task #\\1</a>", $text);
		    $text = eregi_replace("sr[ ]?#([0-9]+)", "<a href=\"/support/index.php?func=detailsupport&support_id=\\1&group_id=$group_id\">Sr #\\1</a>", $text);
		    $text = eregi_replace("patch[ ]?#([0-9]+)", "<a href=\"/patch/?func=detailpatch&patch_id=\\1&group_id=$group_id\">Patch #\\1</a>", $text);
		}
		$lines[$key] = $text;
	}
	return join("\n", $lines);
}

function util_user_link ($username) {
    if ( $username == 'None' || empty($username)) { return $username; }
    return '<a href="/users/'.$username.'">'.$username.'</a>';
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

	echo '<P class="small"><B>'.($msg ? $msg : 'Priority Colors:').'</B><BR>

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
		(defined in $sys_urlroot/themes/<selected theme>/theme.php)
	*/
	global $bgpri;
	
	return $bgpri[$index];
}

function build_priority_select_box ($name='priority', $checked_val='5') {
	/*
		Return a select box of standard priorities.
		The name of this select box is optional and so is the default checked value
	*/
	?>
	<SELECT NAME="<?php echo $name; ?>">
	<OPTION VALUE="1"<?php if ($checked_val=="1") {echo " SELECTED";} ?>>1 - Lowest</OPTION>
	<OPTION VALUE="2"<?php if ($checked_val=="2") {echo " SELECTED";} ?>>2</OPTION>
	<OPTION VALUE="3"<?php if ($checked_val=="3") {echo " SELECTED";} ?>>3</OPTION>
	<OPTION VALUE="4"<?php if ($checked_val=="4") {echo " SELECTED";} ?>>4</OPTION>
	<OPTION VALUE="5"<?php if ($checked_val=="5") {echo " SELECTED";} ?>>5 - Medium</OPTION>
	<OPTION VALUE="6"<?php if ($checked_val=="6") {echo " SELECTED";} ?>>6</OPTION>
	<OPTION VALUE="7"<?php if ($checked_val=="7") {echo " SELECTED";} ?>>7</OPTION>
	<OPTION VALUE="8"<?php if ($checked_val=="8") {echo " SELECTED";} ?>>8</OPTION>
	<OPTION VALUE="9"<?php if ($checked_val=="9") {echo " SELECTED";} ?>>9 - Highest</OPTION>
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
	####   Be sure to include (HTML_Graphs.php) before hitting these graphing functions
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
		$bars[$i]='';
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

// this function permit including site specific content with ease
function util_get_content($file){
    // Default language
    $lang = $GLOBALS['sys_lang'];
    
    // Retrieve the user language if not guest
    // TODO

    // Test first the custom directory
    $fn = $GLOBALS['sys_incdir']."custom/".$lang."/".$file.".txt";
    $fp = @fopen ($fn, "r");
    
    if ( $fp ) {
        // The custom file exists. 
        fclose($fp);
        include($fn);
        return;
    } else {
        // Use the default file
        // Check first if exist
        $fn = $GLOBALS['sys_incdir'].$lang."/".$file.".txt";
        $fp = @fopen ($fn, "r");
        if ( $fp ) {
            // The file exists. 
            fclose($fp);
            include($fn);
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
// Requirement: $theme and $font_size are already set (done by theme.php in pre.php)
function util_get_css_theme(){
    global $theme,$font_size;

    return "/css/".$theme."/".$theme.getFontsizeName($font_size).".css";
}

// This function get the image file for the theme.
// The theme may be specified as an optional second parameter.
// If no theme parameter is given, the current global theme is used.
// If $absolute is true, then the generated path will be absolute,
// otherwise it is relative to $sys_urlroot.
function util_get_image_theme($fn, $the_theme=false, $absolute=false){

    global $theme;
    global $sys_urlroot;

    if (! $the_theme) {
      $the_theme = $theme;
    }

    $path = '/images/'.$the_theme.'.theme/'.$fn;
    
    if ($absolute) {
      $path = $sys_urlroot . $path;
    }

    return $path;
}

// this function get the image directory for the theme
function util_get_dir_image_theme(){

    global $theme;

    return "/images/".$theme.".theme/";
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

?>
