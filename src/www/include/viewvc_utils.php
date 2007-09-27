<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$


/**
 * return the file name that is part of the request_uri
 */
function viewvc_utils_getfile($script_name) {
  $request_uri = getStringFromServer('REQUEST_URI');
  $query_string = getStringFromServer('QUERY_STRING');

  $begin = strlen($script_name)+1;
  $length = strpos($request_uri,"?") - $begin;
  if ($length > 0) {
    $file = substr($request_uri,$begin,$length);
    //sort out magic paths like *checkout*, *docroot*
    if (strpos($file,"*") === 0) $file = substr($file, strpos($file,"*",1)+2);
  } else {
    $file = "";
  }
  return urldecode($file);
}


/**
 * return true if we have to display the codex header
 * and footer around the viewvc output
 */
function viewvc_utils_display_header() {
  $request_uri = getStringFromServer('REQUEST_URI');
  $query_string = getStringFromServer('QUERY_STRING');

  if (strpos($request_uri,"view=patch") !== false) return false;
  if (strpos($request_uri,"view=graphimg") !== false) return false;
  if (strpos($request_uri,"annotate=") !== false) return true;
  if (strpos($request_uri,"view=redirect_path") !== false) return false;

  if ( strpos($request_uri,"/?") === false && 
       strpos($request_uri,"&r1=") === false &&
       strpos($request_uri,"&r2=") === false &&
       (strpos($request_uri,"view=") === false ||
         strpos($request_uri,"view=co") !== false ) ) {
    return false;
  } else {
    return true;
  }
}  

/**
 * call the viewvc.cgi and echo the parsed output
 */
function viewvc_utils_passcommand() {
global $DOCUMENT_ROOT;

  $parse = viewvc_utils_display_header();
  $request_uri = getStringFromServer('REQUEST_URI');

  //this is very important ...
  if (getStringFromServer('PATH_INFO') == "") {
    $path = "/";
    //echo "no path<br>\n";
  } else {
    $path = getStringFromServer('PATH_INFO');
    // hack: path must always end with /
    if (strrpos($path,"/") != (strlen($path)-1)) $path .= "/";
    //echo "path=$path<br>\n";
  }
  
  // "view=auto" is not well supported in wrapped mode. See SR 341 on Partners.
  $query_string = str_replace("view=auto","view=markup",getStringFromServer('QUERY_STRING'));

  $command = 'HTTP_COOKIE="'.getStringFromServer('HTTP_COOKIE').'" '.
           'REMOTE_ADDR="'.getStringFromServer('REMOTE_ADDR').'" '.
           'QUERY_STRING="'.$query_string.'" '.
           'SERVER_SOFTWARE="'.getStringFromServer('SERVER_SOFTWARE').'" '.
           'SCRIPT_NAME="'.getStringFromServer('SCRIPT_NAME').'" '.
           'HTTP_USER_AGENT="'.getStringFromServer('HTTP_USER_AGENT').'" '.
           'HTTP_ACCEPT_ENCODING="'.getStringFromServer('HTTP_ACCEPT_ENCODING').'" '.
           'HTTP_ACCEPT_LANGUAGE="'.getStringFromServer('HTTP_ACCEPT_LANGUAGE').'" '.
           'PATH_INFO="'.$path.'" '.
           'PATH="'.getStringFromServer('PATH').'" '.
           'HTTP_HOST="'.getStringFromServer('HTTP_HOST').'" '.
           'DOCUMENT_ROOT="'.$DOCUMENT_ROOT.'" '.
           'CODEX_LOCAL_INC="'.getStringFromServer('CODEX_LOCAL_INC').'" '. 
           '/var/www/cgi-bin/viewvc.cgi 2>&1';

  ob_start();
  passthru($command);

  $content = ob_get_contents();
  ob_end_clean();

  // Set content type header from the value set by ViewVC
  // No other headers are generated by ViewVC because generate_etags
  // is set to 0 in the ViewVC config file
  $found = false;
  $line = strtok($content,"\n\t\r\0\x0B");
  while ($line && !$found) {
	if (preg_match('/^Content-Type:(.*)$/',$line,$matches)) {
		$viewvc_content_type=$matches[1];
 		$found = true;
 	}
	$line = strtok("\n\t\r\0\x0B");	
  }
  $content = substr($content, strpos($content,$line));
  // Now look for 'Location:' header line (e.g. generated by 'view=redirect_pathrev'
  // parameter, used when browsing a directory at a certain revision number)
  $found = false;
  $line = strtok($content,"\n\t\r\0\x0B");
  $viewvc_location = false;
  while ($line && !$found && strlen($line)>1 ) {
        if (preg_match('/^Location:(.*)$/',$line,$matches)) {
                $viewvc_location=$matches[1];
                $found = true;
        }
        $line = strtok("\n\t\r\0\x0B");
  }
  if ($found) $content = substr($content, strpos($content,$line));
  if ($parse) {
    //parse the html doc that we get from viewvc.
    //remove the http header part as well as the html header and
    //html body tags
    $begin_body = strpos($content,"<body");

    if ($begin_body === false) {
      $begin_body = strpos($content,"<BODY");
      $begin_doc = strpos($content,">",$begin_body)+1;
    } else {
      $begin_doc = strpos($content,">",$begin_body)+1;
    }
    $length = strpos($content, "</body>\n</html>") - $begin_doc;
   
    // Now insert references, and display
    echo util_make_reference_links(substr($content,$begin_doc,$length),$GLOBALS['group_id']);

  } else {
    if ($viewvc_location) {
	header('Location: '.$viewvc_location);
	exit(1);
    }
    header('Content-Type:' . $viewvc_content_type);
    echo $content;
  }
}


function viewvc_utils_track_browsing($group_id, $type) {
  $query_string = getStringFromServer('QUERY_STRING');
  $request_uri = getStringFromServer('REQUEST_URI');

  if (strpos($query_string,"view=markup") !== FALSE ||
      strpos($query_string,"view=auto") !== FALSE ||
      strpos($request_uri,"*checkout*") !== FALSE ||
      strpos($query_string,"annotate=") !== FALSE) {

    if ($type == 'svn') {
      $browse_column = 'svn_browse';
      $table = 'group_svn_full_history';
    } else if ($type == 'cvs') {
      $browse_column = 'cvs_browse';
      $table = 'group_cvs_full_history';
    } 

    $user_id = user_getid();
    $year   = strftime("%Y");
    $mon    = strftime("%m");
    $day    = strftime("%d");
    $db_day = $year.$mon.$day;

    $sql = "SELECT $browse_column FROM $table WHERE group_id = $group_id AND user_id = $user_id AND day = '$db_day'";
    $res = db_query($sql);
    if (db_numrows($res) > 0) {
        db_query("UPDATE $table SET $browse_column=$browse_column+1 WHERE group_id = $group_id AND user_id = $user_id AND day = '$db_day'");
    } else {
        db_query("INSERT INTO $table (group_id,user_id,day,$browse_column) VALUES ($group_id,$user_id,'$db_day',1)");
    }
  }
}

?>
