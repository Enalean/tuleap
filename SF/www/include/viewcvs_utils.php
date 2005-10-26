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
function viewcvs_utils_getfile($script_name) {
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
 * and footer around the viewcvs output
 */
function viewcvs_utils_display_header() {
  $request_uri = getStringFromServer('REQUEST_URI');
  $query_string = getStringFromServer('QUERY_STRING');

  if (strpos($request_uri,"*checkout*") === false && 
      strpos($query_string,"view=graphimg") === false &&
      strpos($request_uri,"*docroot*") === false &&
      strpos($request_uri,"diff_format=u") === false &&
      strpos($request_uri,"diff_format=c") === false &&
      strpos($request_uri,"diff_format=s") === false) {
    return true;
  } else {
    return false;
  }
}  

/**
 * call the viewcvs.cgi and echo the parsed output
 */
function viewcvs_utils_passcommand() {
global $DOCUMENT_ROOT;

  $parse = viewcvs_utils_display_header();
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
           'SF_LOCAL_INC_PREFIX="'.getStringFromServer('SF_LOCAL_INC_PREFIX').'" '. 
           $DOCUMENT_ROOT.'/../../cgi-bin/viewcvs.cgi 2>&1';

  ob_start();
  passthru($command);

  $content = ob_get_contents();
  ob_end_clean();


  if ($parse) {
    //parse the html doc that we get from viewcvs.
    //remove the http header part as well as the html header and
    //html body tags
    $begin_body = strpos($content,"<body");

    if ($begin_body === false) {
      $begin_body = strpos($content,"<BODY");
      $begin_doc = strpos($content,">",$begin_body)+1;
    } else {
      $begin_doc = strpos($content,">",$begin_body)+1;
    }
    $length = strpos($content, "</body></html>") - $begin_doc;

    // little 'ruse' because viewcvs html is not really proper
    // accept everything between the </head> tag and <body ..>
    // tag
    $end_head = strpos($content, "</head>");
    if ($end_head === false) {
      $end_head = strpos($content,"</HEAD>") + strlen("</HEAD>");
    } else {
      $end_head += strlen("</HEAD>");
    }

    echo substr($content,$end_head+1,$begin_body-$end_head-1).substr($content,$begin_doc,$length);

  } else {

    $separator = "\n\t\r\0\x0B";
    $tok = strtok($content,$separator);
    $content_type = "";
    while ($tok) {
	
      if ($content_type != "") {
 	echo substr($content, strpos($content,$tok));
	break;
      } else if (strpos($tok,"Content-Type:") !== false) {
	$content_type = trim(substr($tok,strlen("Content-Type:")));
	header("Content-Type: $content_type");
      }
      $tok = strtok($separator);
    }

  }
}


function viewcvs_utils_track_browsing($group_id, $type) {
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
