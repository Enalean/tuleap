<?php
#
# Copyright (c) STMicroelectronics, 2005. All Rights Reserved.

 # Originally written by Jean-Philippe Giola, 2005
 #
 # This file is a part of codendi.
 #
 # codendi is free software; you can redistribute it and/or modify
 # it under the terms of the GNU General Public License as published by
 # the Free Software Foundation; either version 2 of the License, or
 # (at your option) any later version.
 #
 # codendi is distributed in the hope that it will be useful,
 # but WITHOUT ANY WARRANTY; without even the implied warranty of
 # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 # GNU General Public License for more details.
 #
 # You should have received a copy of the GNU General Public License
 # along with codendi; if not, write to the Free Software
 # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 #
 # $Id$
 #

require_once('pre.php');
require_once('www/mail/mail_utils.php');
require_once('common/plugin/PluginManager.class.php');
require_once(dirname(__FILE__).'/../include/ForumML_FileStorage.class.php');

$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('forumml');

if ($p && $plugin_manager->isPluginAvailable($p) && $p->isAllowed()) {
	
	$request =& HTTPRequest::instance();
	
	$vList = new Valid_UInt('list');
	$vList->required();
	// Checks 'list' parameter
	if (! $request->valid($vList)) {
		exit_error($GLOBALS["Language"]->getText('global','error'),$GLOBALS["Language"]->getText('plugin_forumml','specify_list'));
	} else {
		$list_id = $request->get('list');
		if (!user_isloggedin() || (!mail_is_list_public($list_id) && !user_ismember($request->get('group_id')))) {
			exit_error($GLOBALS["Language"]->getText('global','error'),$GLOBALS["Language"]->getText('include_exit','no_perm'));
		}		
		if (!mail_is_list_active($list_id)) {
			exit_error($GLOBALS["Language"]->getText('global','error'),$GLOBALS["Language"]->getText('plugin_forumml','wrong_list'));
		}
	}
	
	$vFname = new Valid_String('filename');
	$vFname->required();
	$vDate = new Valid_String('date');
	$vDate->required(); 
	if ($request->valid($vDate) && $request->valid($vFname)) {
		
		$list_name = mail_get_listname_from_list_id($list_id);
		$date  = $request->get('date');
		$filename  = $request->get('filename');

		// Retrieve the uploaded file type
		switch(strtoupper(strrchr($filename,".")))
		{
			case ".GZ":
				$type = "application/x-gzip";
				break;
			case ".TGZ":
				$type = "application/x-gzip";
				break;
			case ".ZIP":
				$type = "application/zip";
				break;
			case ".PDF":
				$type = "application/pdf";
				break;
			case ".PNG":
				$type = "image/png";
				break;
			case ".GIF":
				$type = "image/gif";
				break;
			case ".JPG":
				$type = "image/jpeg";
				break;
			case ".TXT":
				$type = "text/plain";
				break;
			case ".HTM":
				$type = "text/html";
				break;
			case ".HTML":
				$type = "text/html";
				break;
			default:
				$type = "application/octet-stream";
				break;
		}

		$info =& $p->getPluginInfo();
		$forumml_dir = $info->getPropertyValueForName('forumml_dir');
		$fstore =& new ForumML_FileStorage($forumml_dir);
		$file_path = $fstore->_getPath($filename,$list_name,$date,"store"); 		

		header("Content-disposition: filename=$filename");
		header("Content-Type: $type");
		header("Content-Transfer-Encoding: $type\n");
		header("Content-Length: ".filesize($file_path));
		header("Pragma: no-cache");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
		header("Expires: 0");
		readfile($file_path);
	} else {
		exit_error($GLOBALS["Language"]->getText('global','error'),$GLOBALS["Language"]->getText('plugin_forumml','missing_param'));	
	}

} else {
	header('Location: '.get_server_url());
}

?>
