<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
require ('pre.php');


if (user_isloggedin()) {

  // Must have a group_id and file_id otherwise
  // we cannot do much
  if (!$file_id || !$group_id) {
    exit_missing_param();
  }


  // Now make an innerjoin on the 4 tables to be sure
  // that the file_id we have belongs to the given group_id

  $sql = "SELECT frs_file.filename AS filename,"
    ."frs_file.file_id AS file_id, "
    ."frs_package.group_id AS group_id "
    ."FROM frs_file,frs_release,frs_package "
    ."WHERE frs_package.group_id=$group_id AND "
    ."frs_release.package_id=frs_package.package_id AND "
    ."frs_file.release_id=frs_release.release_id AND "
    ."frs_file.file_id=$file_id";
  $res_file = db_query( $sql );
  $num_files = db_numrows( $res_file );

  // there must be only just one release - Not 0
  // Not more than one. Just one.
  if ( !$res_file || $num_files != 1 ) {
    exit_error('Incorrect File Release ID or Group ID', 'Please report the error to the '.$GLOBALS['sys_name'].' Administrator using the <i>Contact Us</i> link in the main menu');
  }
  $file_release = db_fetch_array( $res_file );

  //Build the URL to download the file
  $group_unix_name=group_getunixname($group_id);
  $url = 'http://'.$sys_download_host.'/'.$group_unix_name.'/'.$file_release['filename'];
  
  //Insert a new entry in the file release download log table
  $sql = "INSERT INTO filedownload_log(user_id,filerelease_id,time) "
        ."VALUES ('".user_getid()."','".$file_release['file_id']."','".time()."')";

  $res_insert = db_query( $sql );

  // Now Redirect the browser to actually download the file
  // Make sure this URL is not cached anywhere otherwise download
  // logs would be wrong
  header("Pragma: no-cache");
  header("Cache-Control: no-cache, must-revalidate");
  header("Content-type: text/html");
  header("Location: $url");
  print ("Thanks for using ".$GLOBALS['sys_name']."\n");

} else {
  /*
    Not logged in
  */
  exit_not_logged_in();
}


