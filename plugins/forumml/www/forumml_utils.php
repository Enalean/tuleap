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

define('FORUMML_MESSAGE_ID', 1);
define('FORUMML_DATE', 2);
define('FORUMML_FROM', 3);
define('FORUMML_SUBJECT', 4);
define('FORUMML_CONTENT_TYPE', 12);
define('FORUMML_CC', 34);

// Get the list of attached files ($tab_attach) to a message (id_message)
function has_attachment($id_message) {

	$k = 0;
	$tab_attach = array();
    $sql = sprintf('SELECT file_name FROM plugin_forumml_attachment'.
    				' WHERE id_message = %d',
    				db_ei($id_message)); 
	$res = db_query($sql); 
    if (db_numrows($res) > 0) {
		while($row = db_fetch_array($res)) {
	  		$tab_attach[$k] = $row['file_name'];
	  		$k++;
		}
    } 
    return $tab_attach;
}

// Get 'Cc' header
function has_cc($id_message) {
	
	$cc_addr = "";
	$sql = sprintf('SELECT value'.
					' FROM plugin_forumml_messageheader'.
					' WHERE id_message = %d'.
					' AND id_header = %s',
					db_ei($id_message),FORUMML_CC);
	$res = db_query($sql);
	if (db_numrows($res) > 0) {
        // Replace '<' by '&lt;' and '>' by '&gt;'. Otherwise the email adress won't be displayed 
        // because it will be considered as an xhtml tag.
        $cc_addr = preg_replace('/\</', '&lt;', db_result($res,0,'value'));
        $cc_addr = preg_replace('/\>/', '&gt;', $cc_addr);
	}
	return $cc_addr; 
	
} 

// Get message headers 
function get_message_headers($id_message) {
	
	$sql = sprintf('SELECT value'.
					' FROM plugin_forumml_messageheader'.
					' WHERE id_message = %d'.
					' AND id_header < 5'.
					' ORDER BY id_header',
					db_ei($id_message));
	$res = db_query($sql);				
	return $res;				
}

// Get message content type
function get_message_ctype($id_message) {
	
	$sql = sprintf('SELECT value'.
					' FROM plugin_forumml_messageheader'.
					' WHERE id_message = %d'.
					' AND id_header = %s',
					db_ei($id_message),FORUMML_CONTENT_TYPE);
	$res = db_query($sql);
	return db_result($res,0,'value');				
}

// Get id of messages inside a thread, including the 'root message' (return array)
function get_thread_children($id_root) {
	
	$all_children = array($id_root);
	$sql = sprintf('SELECT id_message'.
					' FROM plugin_forumml_message'.
					' WHERE id_parent = %d',
					db_ei($id_root));
	$res = db_query($sql);
	if (db_numrows($res) > 0) {
		$num_child = db_numrows($res);
		while ($num_child > 0) {
			$idx = count($all_children);
			$child = array();
			while ($rows = db_fetch_array($res)) {
				$child[$idx] = $rows['id_message'];
				$idx++;
			}
			// merge the 2 arrays
			$all_children = array_merge($all_children,$child);
			// get children of next level
			$list_child = implode(",",$child);
			$qry = sprintf('SELECT id_message'.
							' FROM plugin_forumml_message'.
							' WHERE id_parent IN (%s)',
							db_es($list_child));
			$res = db_query($qry);
			$num_child = db_numrows($res);				
		}
	}
	
	return $all_children;
}

// Display search results
function show_search_results($p,$result,$group_id,$list_id) {
	
	echo "<table width='100%'>
			<tr>
				<th class=forumml>".
					$GLOBALS['Language']->getText('plugin_forumml','thread')."
				</th>
				<th class=forumml>".
					$GLOBALS['Language']->getText('plugin_forumml','submitted_on')."
				</th>
				<th class=forumml>".
					$GLOBALS['Language']->getText('plugin_forumml','author')."
				</th>
			</tr>";
					
	$idx = 0;
	// Build a table full of search results
	while ($rows = db_fetch_array($result)) {
		$idx++;
		if ($idx % 2 == 0) {
			$class="boxitemalt";
		} else {
			$class="boxitem";
		}
	
		$sql1 = sprintf('SELECT value, body'.
						' FROM plugin_forumml_message m, plugin_forumml_messageheader mh'.
						' WHERE m.id_message = %d'.
						' AND mh.id_message = %d'.
						' AND m.id_list = %d'.
						' AND mh.id_header = %s',
						db_ei($rows['id_message']),
						db_ei($rows['id_message']),
						db_ei($list_id),
						FORUMML_SUBJECT);
		$res1 = db_query($sql1);
		$subject = mb_decode_mimeheader(db_result($res1,0,'value'));	
     	$sql2 = sprintf('SELECT value FROM plugin_forumml_messageheader'.
						' WHERE id_message = %d'.
        				' LIMIT 1,2',
        				db_ei($rows['id_message']));
	    $res2 = db_query($sql2);
    	$k = 1;
        while ($rows2 = db_fetch_array($res2)) {
        	$header[$k] = $rows2['value'];
        	$k++;
        }
	    $from = mb_decode_mimeheader($header[2]);

        // Replace '<' by '&lt;' and '>' by '&gt;'. Otherwise the email adress won't be displayed 
        // because it will be considered as an xhtml tag.
        $from = preg_replace('/\</', '&lt;', $from);
        $from = preg_replace('/\>/', '&gt;', $from);

		$date = date("Y-m-d H:i",strtotime($header[1]));
		// purify message subject (CODENDI_PURIFIER_FORUMML level)
		$hp =& ForumML_HTMLPurifier::instance();
		$subject = $hp->purify($subject,CODENDI_PURIFIER_FORUMML);
		
		// display the resulting threads in rows 
		printf ("<tr class='".$class."'>
					<td class='subject'>
						&nbsp;<img src='".$p->getThemePath()."/images/ic/comment.png'/>
    					<a href='message.php?group_id=".$group_id."&topic=".$rows['id_message']."&list=".$list_id."'><b>%s</b></a>						
					</td>
					<td>						
         				<font class='info'>%s</font>
					</td>
					<td>
						<font class='info'>%s</font>
					</td>
				</tr>",$subject,$date,$from);
	}										
	echo "</table>";				
	
}

// List all threads or all messages inside a thread (depending on $thread parameter)
function show_thread($p,$list_id,$thread,$result,$offset) {
	
	$chunks = 30;
	$request =& HTTPRequest::instance();


	if (isset($thread) && $thread <> 0) {
		// a specfic thread to display
		$start = 0;
		$end = db_numrows($result) - 1;
		$colspan = "colspan=2";
		$view = "flat";
	    echo "<table class='border' width='100%'>";
	} else {
		// all threads to be displayed
		$start = $offset;
		$end = min($start + $chunks - 1, db_numrows($result) - 1);
		$colspan = "";
		$view = "threaded";
		$item = $GLOBALS['Language']->getText('plugin_forumml','thread');
		
		if (isset($offset) && $offset != 0) {
			$begin = "<a href=\"/plugins/forumml/message.php?group_id=".$request->get('group_id')."&list=".$list_id."\"><img src='".$p->getThemePath()."/images/ic/resultset_first.png' title='".$GLOBALS['Language']->getText('plugin_forumml','begin')."'/></a>";
			$previous = "<a href=\"/plugins/forumml/message.php?group_id=".$request->get('group_id')."&list=".$list_id."&offset=".($offset - $chunks)."\"><img src='".$p->getThemePath()."/images/ic/resultset_previous.png' 
                  title='".$GLOBALS['Language']->getText('plugin_forumml','previous', $chunks)."'/></a>";
		} else {
			$begin = "<img src='".$p->getThemePath()."/images/ic/resultset_first_disabled.png'/>";
			$previous = "<img src='".$p->getThemePath()."/images/ic/resultset_previous_disabled.png' 
                              title='".$GLOBALS['Language']->getText('plugin_forumml','previous', $chunks)."'/>"; 
		}	 
		
		if ($end != db_numrows($result) - 1) {
			$next = "<a href=\"/plugins/forumml/message.php?group_id=".$request->get('group_id')."&list=".$list_id."&offset=".($offset + $chunks)."\"><img src='".$p->getThemePath()."/images/ic/resultset_next.png' title='".$GLOBALS['Language']->getText('plugin_forumml','next', $chunks)."'/></a>";
			$finish = "<a href=\"/plugins/forumml/message.php?group_id=".$request->get('group_id')."&list=".$list_id."&offset=".($chunks * (int) ((db_numrows($result) - 1) / $chunks))."\"><img src='".$p->getThemePath()."/images/ic/resultset_last.png' title='".$GLOBALS['Language']->getText('plugin_forumml','end')."'/></a>";
		} else {
			$next = "<img src='".$p->getThemePath()."/images/ic/resultset_next_disabled.png' title='".$chunks."'/>"; 
			$finish = "<img src='".$p->getThemePath()."/images/ic/resultset_last_disabled.png'/>";
		}
		
		// display page-splitting information, at the top of threads table
		echo "<table width='100%'>
				<tr>
					<td align='left' width='10%'>".
						$begin
					."</td>
					<td align='left' width='15%'>".
						$previous
					."</td>
					<td align='center' width='55%'>".
						$GLOBALS['Language']->getText('plugin_forumml','threads')." ".($start + 1)." - ".($end + 1)." <b>(".db_numrows($result).")</b>
					</td>
					<td align='right' width='10%'>
						$next
					</td>
					<td align='right' width='10%'>
						$finish
					</td>														
				</tr>	
			</table>";

	
	    echo "<table class='border' width='100%' border='0'>
            <tr>
                <th class='forumml' ".$colspan." width='60%'>".$item."</th>
                <th class='forumml' width='15%'>".$GLOBALS['Language']->getText('plugin_forumml','submitted_on')."</th>
                <th class='forumml' width='25%'>".$GLOBALS['Language']->getText('plugin_forumml','author')."</th>
            </tr>";
	}
	if (db_numrows($result) > 0) {	
		
		$hp =& ForumML_HTMLPurifier::instance();
		$i = 0;
		for ($idx = $start; $idx <= $end; $idx++) {
			$id_message = db_result($result,$idx,'id_message');				
			$body = db_result($result,$idx,'body');
			$i++;
			if ($i % 2 == 0) {
				$class="boxitemalt";
                $headerclass="headerlabelalt";
        	} else {
        		$class="boxitem";
                $headerclass="headerlabel";
        	}
        	
        	// Get thread headers
        	$hres = get_message_headers($id_message);
        	$date = date("Y-m-d H:i",strtotime(db_result($hres,1,'value')));
        	$from = mb_decode_mimeheader(db_result($hres,2,'value'));
        	$subject = mb_decode_mimeheader(db_result($hres,3,'value'));

            // Replace '<' by '&lt;' and '>' by '&gt;'. Otherwise the email adress won't be displayed 
            // because it will be considered as an xhtml tag.
            $from = preg_replace('/\</', '&lt;', $from);
            $from = preg_replace('/\>/', '&gt;', $from);

        	// Purify message subject: CODENDI_PURIFIER_FORUMML
        	$subject = $hp->purify($subject,CODENDI_PURIFIER_CONVERT_HTML,$request->get('group_id'));

        	if (!isset($thread) || $thread == 0) {
        		// Gets the number of messages inside a thread
				$child_array = get_thread_children($id_message);
				$count = count($child_array);
				$id_parent = $id_message;
        	} else {
        		$count = 0;
        		$id_parent = $thread;
        	}

        	// Display thread message body 
			show_message($p,$hp,$id_message,$id_parent,$view,$class,$headerclass,$count,$body,$subject,$date,$from);
			
			// If you click on 'Reply', load reply form
			$vMess = new Valid_UInt('id_mess');
			$vMess->required();
			if ($request->valid($vMess) && $request->get('id_mess') == $id_message) {			
            	$vReply = new Valid_WhiteList('reply',array(0,1));
            	$vReply->required();            	
            	if ($request->valid($vReply) && $request->get('reply') == 1) {
            		reply($hp,$class,$subject,$id_message,$id_parent,$body,$from);            		
            	}
			}
		}
		echo '</table>';
		
		if ((!isset($thread)) || (isset($thread) && $thread == 0)) {
			// display page-splitting information, at the bottom of threads table
			echo "<table width='100%'>
					<tr>
						<td align='left' width='10%'>".
							$begin
						."</td>
						<td align='left' width='15%'>".
							$previous
						."</td>
						<td align='center' width='55%'>".
							$GLOBALS['Language']->getText('plugin_forumml','threads')." ".($start + 1)." - ".($end + 1)." <b>(".db_numrows($result).")</b>
						</td>
						<td align='right' width='10%'>
							$next
						</td>
						<td align='right' width='10%'>
							$finish
						</td>														
					</tr>	
				</table>";
		}		
	}
}


// Display a message
function show_message($p,$hp,$id_message,$id_parent,$view,$class,$headerclass,$count,$body,$subject,$date,$from) {
	
	$request =& HTTPRequest::instance();
	if ($view == "flat") {
		// specific thread       	
		print " <tr class='".$class."'><a name='".$id_message."'></a>
                <td width='10%'><b><font class='".$headerclass."'>".$GLOBALS['Language']->getText('plugin_forumml','show_message_from')." </font></b></td>
                <td width='90%'><font class='".$headerclass."'>".$from."</font></td>
                </tr>
                <tr class='".$class."'>
                    <td><b><font class='".$headerclass."'>".$GLOBALS['Language']->getText('plugin_forumml','show_message_cc')." </font></b></td>";

        // get CC
    	$cc = has_cc($id_message);
    	if ($cc <> NULL) {
  			if (trim($cc) <> "") {
			    print "<td><font class='".$headerclass."'>".$cc."</font></td>";
            }						
    	}
        else {
            print "<td></td>";
        }

        print " </tr>
                <tr class='".$class."'>
                    <td><b><font class='".$headerclass."'>".$GLOBALS['Language']->getText('plugin_forumml','show_message_date')." </font></b></td>
                    <td><font class='".$headerclass."'>".$date."</font></td>
                </tr>
                <tr class='".$class."'>
                    <td><b><font class='".$headerclass."'>".$GLOBALS['Language']->getText('plugin_forumml','show_message_subject')." </font></b></td>
                    <td><b><font class='".$headerclass."'>".$subject."</font><b></td>
                </tr>";
    	
    	// get attached files
    	$attach = has_attachment($id_message);
    	if ($attach <> NULL) {
        	$tmp_date = date("Y_m_d",strtotime($date));
        	foreach($attach as $key => $filename) {
            	if (preg_match('/.html$/i',$filename)) {
            		$flink = $GLOBALS['Language']->getText('plugin_forumml','msg_html_format');
            	} else {
            		$flink = $filename;
            	}
            	print "<tr class='".$class."'>
						<td class='".$headerclass."' colspan='2'>
							<img src='".$p->getThemePath()."/images/ic/attach.png'/>  <a href='upload.php?group_id=".$request->get('group_id')."&list=".$request->get('list')."&date=".$tmp_date."&filename=".urlencode($filename)."'>".$flink."</a>
						</td>
					</tr>";
        	}
    	}
	
		print "<tr class='".$class."'>
				<td colspan='2'><pre width='100%'>";
		$body = str_replace("\r\n","\n", $body);
    	$tab_body = '';
    	$i = 0;
    	$insideBlockquote = false;
    	$maxi = strlen($body);
    	while($i < $maxi) {
        	if($body{$i} == "\n" && $i < $maxi - 1) {
        		if($body{$i+1} == ">") {
                	if(!$insideBlockquote) {
                    	$tab_body .= '<blockquote class="grep">'.$body{$i++};
                    	$insideBlockquote = true;
                    	continue;
                	}
            	} else {
                	if($insideBlockquote) {
                    	$tab_body .= $body{$i++}.'</blockquote>';
                    	$insideBlockquote = false;
                    	continue;
                	}
            	}
        	}
        	$tab_body .= $body{$i++};
    	}
    	
    	// Purify message body, according to the content-type
		if (strpos(get_message_ctype($id_message),"text/html") === false) {
			// CODENDI_PURIFIER_FORUMML level : no basic html markups, no forms, no javascript, 
			// Allowed: url + automagic links + <blockquote>
			$tab_body = $hp->purify($tab_body,CODENDI_PURIFIER_FORUMML,$request->get('group_id'));				
		} else {
			// Use CODENDI_PURIFIER_FULL for html mails
			$tab_body = $hp->purify($tab_body,CODENDI_PURIFIER_FULL,$request->get('group_id'));
		}    
		
		print($tab_body);
    	print "</pre></td></tr>
                <tr class='".$class."'>
                    <td colspan='2' align='center'>
                        <a href='message.php?group_id=".$request->get('group_id')."&topic=".$id_parent."&id_mess=".$id_message."&reply=1&list=".$request->get('list')."#".$id_message."'>
                            <img src='".$p->getThemePath()."/images/ic/comment_add.png'/>
                            ".$GLOBALS['Language']->getText('plugin_forumml','reply')."
                        </a>
                        <br><br>
                    </td>
                </tr>";

	} else if ($view == "threaded") {
		// all threads
		print "<tr class='".$class."'><a name='".$id_message."'></a>
                    <td class='subject'>";
        if ($count > 1) {
            print "<img src='".$p->getThemePath()."/images/ic/comments.png'/>";
        } 
        else {
            print "<img src='".$p->getThemePath()."/images/ic/comment.png'/>";
        }
        print "<a href='message.php?group_id=".$request->get('group_id')."&topic=".$id_message."&list=".$request->get('list')."'>
							<b>".$subject."</b>
						</a> <b><i>(".$count.")</i></b>						 
			    </td>
                <td class='info'>".$date."</td>
                <td class='info'>".$from."</td>
            </tr>";	
	}
}

// Display the post form under the current post
function reply($hp,$class,$subject,$in_reply_to,$id_parent,$body,$author) {
  	
    $request =& HTTPRequest::instance();
    $body = $hp->purify($body,CODENDI_PURIFIER_CONVERT_HTML);
    $tab_tmp = explode("\n",$body);
    $tab_tmp = array_pad($tab_tmp,-count($tab_tmp)-1,"$author wrote :");

    echo '<script type="text/javascript" src="scripts/cc_attach_js.php"></script>';
    echo "<tr class='".$class."'>
			<td colspan='4'>
			<form id='".$in_reply_to."' action='?group_id=".$request->get('group_id')."&list=".$request->get('list')."&topic=".$id_parent."' name='replyform' method='post' enctype='multipart/form-data'>
			<table class='reply'>";
    if (substr(strtolower($subject),0,3) != "re:") {
        $subject = "Re: ".$subject;
    }
    echo "<tr>
            <td class='subject' valign='top'><b>".$GLOBALS['Language']->getText('plugin_forumml','subject').":&nbsp;</b></td>
            <td class='subject'>".$subject."</td>
            </tr>
        <tr>
            <td></td>";
        echo   '<td><a href="javascript:;" onclick="addHeader(\'\',\'\',1);">['.$GLOBALS["Language"]->getText('plugin_forumml','add_cc').']</a>
                - <a href="javascript:;" onclick="addHeader(\'\',\'\',2);">['.$GLOBALS["Language"]->getText('plugin_forumml','attach_file').']</a>
                <input type="hidden" value="0" id="header_val" />
                <div id="mail_header"></div>
            </td>
        </tr>';
    echo "<tr>
            <td class='subject' valign='top'>
                <b>".$GLOBALS['Language']->getText('plugin_forumml','message')."&nbsp;</b>
            </td>
            <td>
                <textarea name='message' rows='15' cols='100'>";

    foreach($tab_tmp as $k => $line) {
        $line = trim(addslashes($line));
        if ($k == 0) {
            print($line."\n");
        } else {
            print(">".$line."\n");
        }
    }

    echo        "</textarea>
        </td>
        </tr>
        <tr>
			<td class='reply'>
			</td>
			<td>
				<input type='submit' name='send_reply' value='".$GLOBALS['Language']->getText('global','btn_submit')."'/>
				<input type='reset' value='".$GLOBALS['Language']->getText('plugin_forumml','erase')."'/>
			</td>
		</tr>
        </table>
		<input type='hidden' name='reply_to' value='".$in_reply_to."'/>
		<input type='hidden' name='subject' value='".$subject."'/>
		<input type='hidden' name='list' value='".$request->get('list')."'/>
		<input type='hidden' name='group_id' value='".$request->get('group_id')."'/>
        </form>";
    echo "</td></tr>
    		<tr class='".$class."'><th colspan='4'></th></tr>";
}

// Build Mail headers, and send the mail
function process_mail($plug,$reply=false) {

	$request =& HTTPRequest::instance();
	$hp =& ForumML_HTMLPurifier::instance();
	
	// Instantiate a new Mail class
	$mail =& new Mail();
	
	// Build mail headers
	$to = mail_get_listname_from_list_id($request->get('list'))."@".$GLOBALS['sys_lists_host'];
	$mail->setTo($to);
	
	$from = user_getrealname(user_getid())." <".user_getemail(user_getid()).">";
	$mail->setFrom($from);
	
	$vMsg = new Valid_Text('message');
	if ($request->valid($vMsg)) {
		$message = $request->get('message');
	}

	$subject = $request->get('subject');
	$mail->setSubject($subject);
	
	if ($reply) {
		// set In-Reply-To header
		$hres = get_message_headers($request->get('reply_to'));
		$reply_to = db_result($hres,0,'value');			
		$mail->addAdditionalHeader("In-Reply-To",$reply_to);
	} 
	$continue = true;
	
	if ($request->validArray(new Valid_Email('ccs')) && $request->exist('ccs')) {
		$cc_array = array();
		$idx = 0;
		foreach ($request->get('ccs') as $cc) {
			if (trim($cc) != "") {
				$cc_array[$idx] = $hp->purify($cc,CODENDI_PURIFIER_FULL);
				$idx++;
			}
		}
		// Checks sanity of CC List
		$err = '';
		if (!util_validateCCList($cc_array,$err)) {
	 		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_forumml','invalid_mail',$err));
	 		$continue = false;
	 	} else {
	 		// add list of cc users to mail mime
	 		if (count($cc_array) > 0) {
	 			$cc_list = util_normalize_emails(implode(',',$cc_array));	 			
				$mail->setCc($cc_list,true);
	 		}
	 	}
	}

	if ($continue) {
		// Process attachments
		if (isset($_FILES["files"])) {
			$info =& $plug->getPluginInfo();
			$forumml_dir = $info->getPropertyValueForName('forumml_dir');
			if (!is_dir("$forumml_dir/upload/")) {
				mkdir("$forumml_dir/upload",0755);
			}			
			$forumml_storage =& new ForumML_FileStorage($forumml_dir);			
			$attach_array = $_FILES["files"];
			$files_array = $forumml_storage->upload($attach_array);
		}
		
		// Define boundaries as specified in RFC:
		// http://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
		$boundary      = '----=_NextPart';
		$boundaryStart = '--'.$boundary;
		$boundaryEnd   = '--'.$boundary.'--';

		// Attachments headers
		if (isset($files_array) && count($files_array["name"]) > 0) {			
			$attachment = "";
			$text = "This is a multi-part message in MIME format.\n";
			$text = "$boundaryStart\n";
			$text .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
			$text .= "Content-Transfer-Encoding: 8bit\n\n";
			$text .= $message;
			$text .= "\n\n";
			for($i=0; $i < count($files_array["name"]); $i++) {
				$attachment .= "$boundaryStart\n";
				$attachment .= "Content-Type:".$files_array["type"][$i]."; name=".$files_array["name"][$i]."\n";
				$attachment .= "Content-Transfer-Encoding: base64\n";
				$attachment .= "Content-Disposition: attachment; filename=".$files_array["name"][$i]."\n\n";
				$fp = fopen($files_array["path"][$i], "rb");
				$buff = fread($fp, filesize($files_array["path"][$i]));
				fclose($fp);
				$attachment .= chunk_split(base64_encode($buff));				
				$forumml_storage->delete($files_array["path"][$i]);
			}
			$attachment .= "\n$boundaryEnd\n";
			$body = $text.$attachment;
			// force MimeType to multipart/mixed as default (when instantiating new Mail object) is text/plain
			$mail->setMimeType('multipart/mixed; boundary="'.$boundary.'"');
			$mail->addAdditionalHeader("MIME-Version","1.0");
		} else {
			$body = $message;
		}

		$mail->setBody($body);
		
		if ($mail->send()) {
			$GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_forumml','mail_succeed'));
		} else {
			$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_forumml','mail_fail'));
			$continue = false;
		}
	}
	return $continue;
	
}
?>
