<?php
/**
 * Copyright (c) Enalean, 2016-2019. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('common/frs/FRSFileFactory.class.php');
require_once('www/file/file_utils.php');

list(,$group_id, $file_id) = explode('/', $request->getFromServer('PATH_INFO'));

  // Must have a group_id and file_id otherwise
  // we cannot do much
  $vGroupId = new Valid_GroupId();
  $vGroupId->required();
  $vFileId  = new Valid_UInt();
  $vFileId->required();
  if (!$vFileId->validate($file_id) || !$vGroupId->validate($group_id)) {
    exit_missing_param();
  }

  // Now make an innerjoin on the 4 tables to be sure
  // that the file_id we have belongs to the given group_id

  $frsff = new FRSFileFactory();
  $file  = $frsff->getFRSFileFromDb($file_id, $group_id);

  if (! $file) {
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
  $file->download();
