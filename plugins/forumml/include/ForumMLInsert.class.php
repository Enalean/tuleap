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


// ForumML Database Query Class
class ForumMLInsert
	{
    var $id_message;
    var $mail;
    var $id_list;
	
    // Class Constructor
	function ForumMLInsert($list_id) {
		// set id_list
		$this->id_list = $list_id;
	}
    
    // Insert values into forumml_messageheader table
    function insertMessageHeader($id_header,$value) {
        
    	$qry = sprintf('INSERT INTO plugin_forumml_messageheader'.
    					' (id_message, id_header, value)'.
    					' VALUES (%d,%d,"%s")',
    					db_ei($this->id_message),
    					db_ei($id_header),
    					db_es(utf8_encode($value)));
    	db_query($qry);
    }

    // Insert values into forumml_attachment table 
    function insertAttachment($id_message, $filename,$filetype,$filesize) {
        
    	$qry = sprintf('INSERT INTO plugin_forumml_attachment'.
    					' (id_attachment, id_message, file_name, file_type, file_size)'.
    					' VALUES (%d, %d,"%s","%s",%d)',
    					"",
    					db_ei($id_message),
    					db_es(utf8_encode($filename)),
    					db_es(utf8_encode($filetype)),
    					db_ei($filesize));
    	db_query($qry);
    }

    // Insert values into forumml_header table
    function insertHeader($header) {
        
    	// Search if the header is already in the table
        $qry = sprintf('SELECT id_header'.
        				' FROM plugin_forumml_header'.
        				' WHERE name = "%s"',
        				db_es($header));
    	$result = db_query($qry);
        
        // If not, insert it
        if (db_result($result,0,'id_header') == "") {
            $sql = sprintf('INSERT INTO plugin_forumml_header'.
            				' (id_header, name)'.
            				' VALUES (%d, "%s")',
            				"",db_es(utf8_encode($header)));
        	$res = db_query($sql);
            return (db_insertid($res));
        } else {
            return (db_result($result,0,'id_header'));
        }
    }

    // Insert values into forumml_message table
    function insertMessage($structure,$body) {
        
    	$this->mail = $structure;
        
        if (isset($structure["in-reply-to"])) {
        	// special case: 'in-reply-to' header may contain "Message from ... " 
        	if (preg_match('/^Message from.*$/',$structure["in-reply-to"])) {
        		$arr = explode(" ",$structure["in-reply-to"]);
        		$reply_to = $arr[count($structure["in-reply-to"]) - 1];
        	} else {
        		$reply_to = $structure["in-reply-to"];
        	}	
        } else {
        	if (isset($structure["references"])) {
        		// special case: 'in-reply-to' header is not set, but 'references' - which contain list of parent messages ids - is set
        		$ref_arr = explode(" ",$structure["references"]);
        		$reply_to = $ref_arr[count($structure["references"]) - 1];
        	} else {
        		$reply_to = "";
        	}	
        }
        $id_parent = 0;
        // If the current message is an answer
        if ($reply_to != "") {
        	// Search the id of the message for which the current message is in reply to
        	$qry = sprintf('SELECT id_message'.
        					' FROM plugin_forumml_messageheader'.
        					' WHERE id_header = "1"'.
        					' AND value = "%s"',
        					db_es($reply_to));
        	$result = db_query($qry);        	
        	$id_parent = db_result($result,0,'id_message');
        }

        $sql = sprintf('INSERT INTO plugin_forumml_message'.
        				' (id_message, id_list, id_parent, body)'.
        				' VALUES (%d, %d, %d, "%s")',
        				"",
        				db_ei($this->id_list),
        				db_ei($id_parent),
        				db_es(utf8_encode($body)));
        $res = db_query($sql);
        $this->id_message = db_insertid($res);

        // All headers of the current mail are stored in the forumml_messageheader table
        $k=0;
        foreach ($structure as $header => $value_header) {
            $k++;
            if ($k != 1) {
                if ($header != "received") {
                    $id_header = $this->insertHeader($header);
                    if (is_array($value_header)) {
						$value_header = implode(",",$value_header);
                    }
                    $this->insertMessageHeader($id_header,$value_header);
                }
            }
        }
    }
}

?>
