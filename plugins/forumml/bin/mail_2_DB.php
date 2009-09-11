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

/* This script allows the transfer of an mbox-formatted mail to ForumML database.
 *  First argument: mailing-list name
 *  Second argument: type of transfer, depending on the input
 * 		'1': transfer from '/var/run/forumml/mail_tmp_xyz' temporary file (1-message mbox file)
 * 		'2': transfer from whole list archive (real mbox file)
 *  Third argument: temporary file name (used when 2nd arg = 1)
*/

function insert_text_html($insert, $forumml_storage, $list, $date, $struct, $headers) {

	if (isset($struct->parts[0]->body)) {
		$text = $struct->parts[0]->body;
	} else {
		$text = "";
	}
	// store the text part
	$insert->insertMessage($headers,$text);
	// Attach the html part					
	$html = $struct->parts[1]->body;
	$ftype = $struct->parts[1]->headers["content-type"];
	// include message-id in the html file name (to guaranty unicity in case many html attachments are done in the same thread, 
	// of the same mailing-list, at the same date)
	$fname = "message_".substr($headers["message-id"], 1, strpos($headers["message-id"], '@') - 1).".html";
	$bname = basename($fname);
	$encod = strtolower($struct->parts[1]->headers["content-transfer-encoding"]);
	$fsize = $forumml_storage->store($bname,$html,$list,$date,$encod);
	$insert->insertAttachment($insert->id_message, $bname,$ftype,$fsize);
	return $insert->id_message;
	
}

function insert_mail_parts($message_id, $insert, $forumml_storage, $list, $date, $parts, $headers, $type) {

	$num_parts = count($parts);
	$idx = 1;
	while ($idx < $num_parts) {
		if (isset($parts[$idx]->body) && trim($parts[$idx]->body) != "") {
			$body = $parts[$idx]->body;
			$filetype = $parts[$idx]->headers["content-type"];
			if ($type == 0 || ($type == 1 && (strpos($headers["content-type"],"multipart/mixed") !== false || strpos($headers["content-type"],"multipart/related") !== false))) {
				if (! isset($parts[$idx]->d_parameters["filename"])) {
					// special case where a content is attached, without filename
					$pos = strpos($filetype,"name=");
					if ($pos === false) {
						// set filename to 'attachment_<k>'
						$filename = "attachment_".$idx;
					} else {
	 					// get filename from 'name' section
						$filename = substr(substr($filetype,$pos),6,-1);			
		 			}
		 		} else {
	 				$filename = $parts[$idx]->d_parameters["filename"];
	 			}
	 		} else if ($type == 1 && (strpos($headers["content-type"],"multipart/alternative") !== false)) {
				// Part of the mail is in text/plain, the other is in text/html 
				$filename = "message_".$idx."_".substr($headers["message-id"], 1, strpos($headers["message-id"], '@') - 1).".html";	 		
			}	
			$basename = basename($filename);
			$encoding = strtolower($parts[$idx]->headers["content-transfer-encoding"]);

			// store attachment in /var/lib/codendi/forumml/<listname>/<Y_M_D>
			$size = $forumml_storage->store($basename,$body,$list,$date,$encoding);

			// insert attachment in the DB
			$insert->insertAttachment($message_id, $basename,$filetype,$size);	 																				
		}
		$idx++;
	}

}

// set illimited 'max_execution_time' , when processing whole list archive
if ($argv[2] == 2) {
	ini_set('max_execution_time',0); 
}

require_once('pre.php');
require_once('Mail/mbox.php');
require_once('Mail/mimeDecode.php');
require_once(dirname(__FILE__).'/../include/ForumMLInsert.class.php');
require_once(dirname(__FILE__).'/../include/ForumML_FileStorage.class.php');
require_once('common/plugin/PluginManager.class.php');
require_once('www/mail/mail_utils.php');
require_once('utils.php');

$list = $argv[1];
// get list id and group id from list name
$sql = sprintf('SELECT group_id, group_list_id'.
				' FROM mail_group_list'.
				' WHERE list_name = "%s"',
				db_escape_string($list));
$res = db_query($sql);
if (db_numrows($res) > 0) {
	$id_list = db_result($res,0,'group_list_id');
	$gr_id = db_result($res,0,'group_id');
} else {
	$stderr = fopen('php://stderr', 'w');
	fwrite($stderr, "Invalid mailing-list $list \n");
	fclose($stderr);
	exit;
}

$mbox   =& new Mail_Mbox();
$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('forumml');
$info =& $p->getPluginInfo();

if ($p && $plugin_manager->isPluginAvailable($p) && $plugin_manager->isPluginAllowedForProject($p, $gr_id)) {
	if ($argv[2] == 2) {
		// get list archive		
		$forumml_arch = $info->getPropertyValueForName('forumml_arch');
		$mbox_file = $forumml_arch."/private/".$list.".mbox/".$list.".mbox";
		// check if mbox file exists
		if (! util_file_exists($mbox_file)) {
			$stderr = fopen('php://stderr', 'w');
			fwrite($stderr, "Invalid mbox file $mbox_file \n");
			fclose($stderr);
			exit;	
		}
	} else {
		// get 3rd argument
		$temp_file = $argv[3];
		// get temp file parent dir
		$forumml_tmp = $info->getPropertyValueForName('forumml_tmp');
		$mbox_file = $forumml_tmp."/".$temp_file;
	}

	// Open the mail that has been temporary stored
	$mid=$mbox->open($mbox_file);
	if (PEAR::isError($mid)) {
		print $mid->getMessage();
	} else {
		$num_msg = $mbox->size($mid);
		for ($i = 0; $i < $num_msg; $i++) {
			$thisMessage = $mbox->get($mid,$i);
			if (PEAR::isError($thisMessage)) {
				print $thisMessage->getMessage();		
			} else {
				$insert =& new ForumMLInsert($id_list);
				$args['include_bodies'] = TRUE;
				$args['decode_bodies'] = TRUE;
				$args['decode_headers'] = TRUE;
				$args['crlf'] = "\r\n";
				// Using the class MIME, decoding the mail
				$decoder = new Mail_mimeDecode($thisMessage, "\r\n");
				$structure = $decoder->decode($args);		
				if (isset($structure->body)) {
					$bod = $structure->body;
				} else {
					$bod = "";
				}
				// Did this mail have an attachment ?
				if (isset($structure->headers["content-type"])) {			
					if (strpos($structure->headers["content-type"],"multipart/") !== false) {
						$date = date("Y_m_d",strtotime($structure->headers["date"]));					
						// get forumml data dir (where attachments are stored)
						$forumml_dir = $info->getPropertyValueForName('forumml_dir');
						$forumml_storage =& new ForumML_FileStorage($forumml_dir);				
						if (strpos($structure->parts[0]->headers["content-type"],"multipart/alternative") !== false) {
							// the first part of the mail is multipart/alternative
							$alt_array = $structure->parts[0];					
							$id_mess = insert_text_html($insert, $forumml_storage, $list, $date, $alt_array, $structure->headers);
						} else if (strpos($structure->parts[0]->headers["content-type"],"multipart/related") !== false) {
							// the first part of the mail is multipart/related
							$related = $structure->parts[0];
							if (strpos($related->parts[0]->headers["content-type"],"multipart/alternative") !== false) {
								$alternative = $related->parts[0];
								$id_mess = insert_text_html($insert, $forumml_storage, $list, $date, $alternative, $structure->headers);
								// Now attach the other parts
								insert_mail_parts($id_mess, $insert, $forumml_storage, $list, $date, $related->parts, $structure->headers, 0);
							}						
						} else {
							// the first part of the mail is simple text
	 						if (isset($structure->parts[0]->body)) {
	 							$bdy = $structure->parts[0]->body;
	 						} else {
	 							$bdy = "";
			 				}
							$insert->insertMessage($structure->headers,$bdy);
							$id_mess = $insert->id_message;
						}
						insert_mail_parts($id_mess, $insert, $forumml_storage, $list, $date, $structure->parts, $structure->headers, 1);
					} else {
						// No attachment
						$insert->insertMessage($structure->headers,$bod);
					} 
				} else {
					// No content-type ?
					$insert->insertMessage($structure->headers,$bod);
				}		
			}
		}
	}
}

// delete temporary file
if ($argv[2] == 1) {
	if (util_file_exists($mbox_file)) {
		unlink($mbox_file);
	}
}

?>
