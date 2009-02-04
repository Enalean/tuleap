<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// 
require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('common/frs/FRSFileFactory.class.php');
require_once('www/file/file_utils.php');

if (user_isloggedin()) {
  list(,$group_id, $file_id) = explode('/', $_SERVER['PATH_INFO']);

  // Must have a group_id and file_id otherwise
  // we cannot do much
  $vGroupId = new Valid_groupId();
  $vGroupId->required();
  $vFileId  = new Valid_UInt();
  $vFileId->required();
  if (!$vFileId->validate($file_id) || !$vGroupId->validate($group_id)) {
    exit_missing_param();
  }

  // Now make an innerjoin on the 4 tables to be sure
  // that the file_id we have belongs to the given group_id

  $frsff = new FRSFileFactory();
  $file =& $frsff->getFRSFileFromDb($file_id, $group_id);

  if (! $file || $file->isError()) {
    exit_error($Language->getText('file_download','incorrect_release_id'), $Language->getText('file_download','report_error',$GLOBALS['sys_name']));
  }

  // Check permissions for downloading the file, and check that the file has the active status 
  if (! $file->userCanDownload() || ! $file->isActive()) {
      exit_error($Language->getText('file_download','access_denied'), 
                $Language->getText('file_download','access_not_authorized',session_make_url("/project/memberlist.php?group_id=$group_id")));
  } 

  if (! $file->fileExists()) {
      exit_error($Language->getText('global','error'), $Language->getText('file_download','file_not_available'));
  }

  // Log the download in the Log system
  $file->logDownload(user_getid());


  // Start download
  if (! $file->download()) {
      exit_error($Language->getText('global','error'), $Language->getText('file_download','file_not_available'));
  }

} else {
  /*
    Not logged in
  */
  exit_not_logged_in();
}
?>
