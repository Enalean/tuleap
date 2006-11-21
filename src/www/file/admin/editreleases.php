<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('www/project/admin/permissions.php');    
require_once('www/file/file_utils.php');
require_once('common/mail/Mail.class');
require_once('www/forum/forum_utils.php');
$Language->loadLanguageMsg('file/file');


/*

File release system rewrite, Tim Perdue, SourceForge, Aug, 2000


	Sorry this is a large, complex page but this is a very complex process


	If you pass just the group_id, you will be given a list of releases
	with the option to edit those releases or create a new release


	If you pass the group_id plus the package_id, you will get the list of 
		releases with just the releases of that package shown

	If you pass in the release_id, you are essentially "editing" that release
		You are presented with three boxes:
		1. edit/add the change/release notes
			You can either upload them, or paste them in
		2. select from the files you've uploaded
			This is an improvement because you can select
			a bunch of files at once and attach them all to the 
			same release and same change notes
		3. edit the files in the release
			delete/change files in this release


*/



/*

Physically delete a file from the download server and database

First, make sure the file is theirs
Second, delete it from the db
Third, delete it from the download server

return 0 if file not deleted, 1 otherwise
*/
function delete_file ($group_id,$file_id) {
  GLOBAL $ftp_incoming_dir;

  $res1=db_query("SELECT frs_file.filename FROM frs_package,frs_release,frs_file ".
		 "WHERE frs_package.group_id='$group_id' ".
		 "AND frs_release.release_id=frs_file.release_id ".
		 "AND frs_release.package_id=frs_package.package_id ".
		 "AND frs_file.file_id='$file_id'");
  if (!$res1 || db_numrows($res1) < 1) {
    //release not found for this project
    return 0;
  } else {
    /*
       delete the file from the database
    */
    db_query("DELETE FROM frs_file WHERE file_id='$file_id'");
    //append the filename and project name to a temp file for the root perl job to grab
    $time = time();
    exec ("/bin/echo \"". db_result($res1,0,'filename') ."::". group_getunixname($group_id) ."::$time\" >> $ftp_incoming_dir/.delete_files");

    return 1;
  }
}



/*

Physically delete a release from the download server and database

First, make sure the release is theirs
Second, delete all its files from the db
Third, delete the release itself from the deb
Fourth, put it into the delete_files to be removed from the download server

return 0 if release not deleted, 1 otherwise
*/
function delete_release ($group_id,$release_id) {
  GLOBAL $ftp_incoming_dir;
  
  $res1=db_query("SELECT frs_release.name FROM frs_package,frs_release ".
		 "WHERE frs_package.group_id='$group_id' ".
		 "AND frs_release.release_id='$release_id' ".
		 "AND frs_release.package_id=frs_package.package_id");
  if (!$res1 || db_numrows($res1) < 1) {
    //release not found for this project
    return 0;
  } else {
    //delete all corresponding files from the database
    $res=db_query("SELECT file_id,filename FROM frs_file WHERE release_id='$release_id'");
    $rows=db_numrows($res);
    for ($i=0; $i<$rows; $i++) {
      delete_file($group_id, db_result($res,$i,'file_id'));
      $filename = db_result($res,$i,'filename');  
    }
    
    //delete the release from the database
    db_query("DELETE FROM frs_release WHERE release_id='$release_id'");
    
    //append the releasename and project name to a temp file for the root perl job to grab
    if ($filename) {
      //find the last occurrence of / in the filename to get the parentdir name
      $pos = strrpos($filename, "/");
      if (!$pos) {
	// not found...
      } else {
	$parentdir = substr($filename, 0, $pos);
	$time = time();
	exec ("/bin/echo \"$parentdir::". group_getunixname($group_id) ."::$time\" >> $ftp_incoming_dir/.delete_files");
      } 
    }
    
    return 1;
  }
}


if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

if (isset($submit)) {
	/*

		make updates to the database

	*/
	if ($func=='add_release' && $release_name && $package_id) {

		/*

			Create a new release of this package

			First, make sure the package is theirs
			Second, add the release of the package
			Third, get the new release_id and make it available below

		*/

		if (!$release_name || !$package_id) {
			$feedback .= ' '.$Language->getText('file_admin_editreleases','create_p_before_rel').' ';
		} else {
			//create a new release of this package

			//see if this package belongs to this project
			$res1=db_query("SELECT * FROM frs_package WHERE package_id='$package_id' AND group_id='$group_id'");
			if (!$res1 || db_numrows($res1) < 1) {
				$feedback .= ' | '.$Language->getText('file_admin_editreleases','p_not_exists').' ';
				echo db_error();
			} else {
			  //check if release name exists already
			  $testres=db_query("SELECT * FROM frs_release WHERE package_id='$package_id' and name='$release_name'");
			  if (!$testres || db_numrows($testres) < 1) {
			    //package_id was fine - now insert the release
			    $res=db_query("INSERT INTO frs_release (package_id,name,status_id,release_date,released_by) ".
					  "VALUES ('$package_id','$release_name','1','". time() ."','". user_getid() ."')");
			    if (!$res) {
			      $feedback .= ' | '.$Language->getText('file_admin_editreleases','add_rel_fail').' ';
			      echo db_error();
			      //insert failed - go back to definition screen
			    } else {
			      //release added - now show the detail page for this new release
			      $release_id=db_insertid($res);
			      $feedback .= ' '.$Language->getText('file_admin_editreleases','rel_added').' ';
			    }
			  } else {
			     $feedback .= ' '.$Language->getText('file_admin_editreleases','rel_name_exists').' ';
			  }
			}
		}

	} else if ($func=='update_release' && $release_id) {
		/*

			updating frs_release

			They could be uploading the change_log or release_notes or it may be pasted in

			They could also change the package_id, so we need to see 
				again if it's a legit package_id for this project

		*/
		$feedback .= ' '.$Language->getText('file_admin_editreleases','updating_rel').' ';
		if ($upload_instead) {
		  if ($uploaded_data) {
			$code = addslashes(fread( fopen($uploaded_data, 'r'), filesize($uploaded_data)));
		  }
			if ((strlen($code) > 0) && (strlen($code) < $sys_max_size_upload)) {
				//size is fine
				$feedback .= ' | '.$Language->getText('file_admin_editreleases','data_uploaded').' ';
			} else {
				//too big or small
			  $feedback .= ' | '.$Language->getText('file_admin_editreleases','length_err',$sys_max_size_upload).' ';
				$code='';
			}
			if ($upload_instead == 1) {
				//uploaded change log
				$changes=$code;
			} else if ($upload_instead == 2) {
				//uploaded release notes
				$notes=$code;
			} else {
				$feedback .= ' | '.$Language->getText('file_admin_editreleases','invalid_flag_err').' ';
			}
		}


		if (!$release_name || !$package_id || !$status_id) {
			$feedback .= ' '.$Language->getText('file_admin_editreleases','create_p_before_rel_status').' ';
		} else {
			//see if this release belongs to this project
			$res1=db_query("SELECT frs_package.package_id FROM frs_package,frs_release ".
					"WHERE frs_package.package_id='$package_id' ".
					"AND frs_package.group_id='$group_id' ".
					"AND frs_release.release_id='$release_id' ".
					"AND frs_release.package_id=frs_package.package_id");
			if ($new_package_id != $package_id) {
				//changing to a different package for this release
				$res2=db_query("SELECT * FROM frs_package WHERE package_id='$new_package_id' AND group_id='$group_id'");
				if (!$res2 || db_numrows($res2) < 1) {
					//new package_id isn't theirs
					exit_error($Language->getText('global','error'),$Language->getText('file_admin_editreleases','p_not_yours'));
				}
			}
			if (!$res1 || db_numrows($res1) < 1) {
				$feedback .= ' | '.$Language->getText('file_admin_editreleases','p_rel_not_yours').' ';
				echo db_error();
				unset($editrelease);
			} else {
				//release was there's and they have the right to update it

// LJ Why? It is very conveninet sometimes to hide a
// without having to delete all attached files
// Beside we have already modified editpackages.php
// so that you can hide a package if all attached
// released are hidden.
// 				if ($status_id != 1) {
					//if hiding a package, refuse if it has files under it
//					$res=db_query("SELECT * FROM frs_file WHERE release_id='$release_id'");
//					if (db_numrows($res) > 0) {
//						$feedback .= ' | '.$Language->getText('file_admin_editreleases','cannot_del_rel').' ';
//						$status_id=1;
//					}
// LJ				}

				//now update the file entry
				if (!ereg("[0-9]{4}-[0-9]{2}-[0-9]{2}",$release_date)) {
					$feedback .= ' | '.$Language->getText('file_admin_editreleases','data_not_parsed').' ';
				} else { //is valid date... parse it

				  // make sure that we don't change the date by error because of timezone reasons.
				  // eg: release created in India (GMT +5:30) at 2004-06-03. 
				  // MLS in Los Angeles (GMT -8) changes the release notes
				  // the release_date that we showed MLS is 2004-06-02. 
				  // with mktime(0,0,0,2,6,2004); we will change the unix time in the database
				  // and the people in India will discover that their release has been created on 2004-06-02
				  	$res2=db_query("SELECT release_date FROM frs_release ".
						       "WHERE frs_release.release_id='$release_id' ");
					if (format_date('Y-m-d',db_result($res2,0,'release_date')) == $release_date) {
					  // the date didn't change => don't update it
					  $res=db_query("UPDATE frs_release SET name='$release_name',preformatted='$preformatted', ".
						"status_id='$status_id',package_id='$new_package_id',notes='$notes',changes='$changes' ".
						"WHERE release_id='$release_id'");
					} else {
					
					  $date_list = split("-",$release_date,3);
					  $unix_release_time = mktime(0,0,0,$date_list[1],$date_list[2],$date_list[0]);

					  $res=db_query("UPDATE frs_release SET release_date='$unix_release_time',name='$release_name',preformatted='$preformatted', ".
							"status_id='$status_id',package_id='$new_package_id',notes='$notes',changes='$changes' ".
							"WHERE release_id='$release_id'");
					}

					if (!$res) {
						$feedback .= ' | '.$Language->getText('file_admin_editreleases','rel_update_failed').' ';
						echo db_error();
					} else {
						$feedback .= ' | '.$Language->getText('file_admin_editreleases','rel_updated').' ';
					}
				}
			}
		}

	} else if ($func=='update_file' && $file_id) {
		/*

			Update a file in this release - you can move files between 
				package releases if you want

			First, make sure this file is theirs
			Second, if they're moving it to another release, make sure that release is theirs
			Third, verify the date is parseable
			Fourth, update the file's info

		*/

		//see if this file is part of this release/project/package
		$res1=db_query("SELECT frs_package.package_id FROM frs_package,frs_release,frs_file ".
			"WHERE frs_package.group_id='$group_id' ".
			"AND frs_release.release_id=frs_file.release_id ".
			"AND frs_release.package_id=frs_package.package_id ".
			"AND frs_file.file_id='$file_id'");
		if (!$res1 || db_numrows($res1) < 1) {
			//release not found for this project
			$feedback .= ' | '.$Language->getText('file_admin_editreleases','f_not_yours').' ';
			echo db_error();
		} else {
			//file found and it is for this release/project/package
			if ($new_release_id != $release_id) {
				//changing to a different release for this file
				//see if the new release is valid for this project
				$res2=db_query("SELECT frs_package.package_id FROM frs_package,frs_release ".
				"WHERE frs_package.group_id='$group_id' ".
				"AND frs_release.release_id='$new_release_id' ".
				"AND frs_release.package_id=frs_package.package_id");

				if (!$res2 || db_numrows($res2) < 1) {
					//release not found for this project
					exit_error($Language->getText('global','error'),$Language->getText('file_admin_editreleases','rel_not_yours'));
				}
			}
			//now update the file entry
			if (!ereg("[0-9]{4}-[0-9]{2}-[0-9]{2}",$release_time)) {
				$feedback .= ' | '.$Language->getText('file_admin_editreleases','data_not_parsed').' ';
			} else { //is valid date... parse it
			  // make sure that we don't change the date by error because of timezone reasons.
			  // eg: file created in India (GMT +5:30) at 2004-06-03. 
			  // MLS in Los Angeles (GMT -8) changes the processor type
			  // the release_time that we showed MLS is 2004-06-02. 
			  // with mktime(0,0,0,2,6,2004); we will change the unix time in the database
			  // and the people in India will discover that their release has been created on 2004-06-02
			  $res2=db_query("SELECT release_time FROM frs_file ".
						       "WHERE file_id='$file_id' ");
			  if (format_date('Y-m-d',db_result($res2,0,'release_time')) == $release_time) {
			    // the date didn't change => don't update it
			    $res=db_query("UPDATE frs_file SET release_id='$new_release_id',type_id='$type_id',processor_id='$processor_id' ".
					  "WHERE file_id='$file_id'");
			  } else {
			    $date_list = split("-",$release_time,3);
			    $unix_release_time = mktime(0,0,0,$date_list[1],$date_list[2],$date_list[0]);

			    $res=db_query("UPDATE frs_file SET release_id='$new_release_id',release_time='$unix_release_time',type_id='$type_id',processor_id='$processor_id' ".
					"WHERE file_id='$file_id'");
			  }
			  $feedback .= ' '.$Language->getText('file_admin_editreleases','file_updated').' ';
			}
		}

	} else if ($func=='add_files' && $file_list && !$refresh) {
		/*

			Add a file to this release


			First, make sure this release belongs to this group

			iterate the following for each file:

			Second see if the filename is legal
			Third see if they already have a file by the same name
			Fourth if file actually exists, physically move the file on garbage to the new location
			Fifth insert it into the database


		*/
		$group_unix_name=group_getunixname($group_id);
		$project_files_dir=$ftp_frs_dir_prefix.'/'.$group_unix_name;

		$count=count($file_list);
		if ($count > 0) {
			$feedback .= ' '.$Language->getText('file_admin_editreleases','add_files').' ';
			//see if this release belongs to this project
			$res1=db_query("SELECT frs_package.package_id FROM frs_package,frs_release ".
				"WHERE frs_package.group_id='$group_id' ".
				"AND frs_release.release_id='$release_id' ".
				"AND frs_release.package_id=frs_package.package_id");
			if (!$res1 || db_numrows($res1) < 1) {
				//release not found for this project
				$feedback .= ' | '.$Language->getText('file_admin_editreleases','rel_not_yours').' ';
			} else {
				$now=time();
				//iterate and add the files to the frs_file table
				for ($i=0; $i<$count; $i++) {
					//see if filename is legal before adding it
					if (!util_is_valid_filename ($file_list[$i])) {
						$feedback .= ' | '.$Language->getText('file_admin_editreleases','illegal_file_name').": $file_list[$i] ";
					} else {
					  // get the package id and compute the upload directory
					  $pres = db_query("SELECT frs_package.package_id FROM frs_package,frs_release ".
							   "WHERE frs_package.group_id='$group_id' ".
							   "AND frs_release.release_id='$release_id' ".
							   "AND frs_release.package_id=frs_package.package_id ");
						  
					  if (!$pres || db_numrows($pres) < 1) { 
					    $feedback .= ' | '.$Language->getText('file_admin_editreleases','p_rel_not_yours').' ';
					    echo db_error();
					  } else {
					    $package_id = db_result($pres, 0, 'package_id');
					    $upload_subdir = 'p'.$package_id.'_r'.$release_id;
					  }

					  //see if they already have a file by this name

					  $res1=db_query("SELECT frs_package.package_id FROM frs_package,frs_release,frs_file ".
							"WHERE frs_package.group_id='$group_id' ".
							"AND frs_release.release_id=frs_file.release_id ".
							"AND frs_release.package_id=frs_package.package_id ".
							"AND frs_file.filename='$upload_subdir/$file_list[$i]'");
						if (!$res1 || db_numrows($res1) < 1) {

							/*
								move the file to the project's fileserver directory
							*/
							clearstatcache();
							if (is_file($ftp_incoming_dir.'/'.$file_list[$i]) && file_exists($ftp_incoming_dir.'/'.$file_list[$i])) {
							  //move the file to a its project page using a setuid program
							  exec ("/bin/date > /tmp/".$group_unix_name."$group_id",$exec_res);
							  exec ($GLOBALS['codex_bin_prefix'] . "/fileforge /tmp/".$group_unix_name."$group_id ".$group_unix_name, $exec_res); 
							  exec ($GLOBALS['codex_bin_prefix'] . "/fileforge ".$file_list[$i]." ".$group_unix_name."/".$upload_subdir,$exec_res);
							  if ($exec_res[0]) {
							    echo '<h3>'.$exec_res[0],$exec_res[1].'</H3><P>';
							  }
							  //add the file to the database
							  $res=db_query("INSERT INTO frs_file ".
									"(release_time,filename,release_id,file_size,post_date) ".
									"VALUES ('$now','$upload_subdir/$file_list[$i]','$release_id','". filesize("$project_files_dir/$upload_subdir/$file_list[$i]") ."','$now') ");
							  if (!$res) {
							    $feedback .= ' | '.$Language->getText('file_admin_editreleases','not_add_file').": $file_list[$i] ";
							    echo db_error();
								}
							} else {
							  $feedback .= ' | '.$Language->getText('file_admin_editreleases','filename_invalid').": $file_list[$i] ";
							}
						} else {
							$feedback .= ' | '.$Language->getText('file_admin_editreleases','filename_exists').": $file_list[$i] ";
						}
					}
				}
			}
		} else {
			//do nothing
			$feedback .= ' '.$Language->getText('file_admin_editreleases','no_files_selected').' ';
		}
	} else if ($func=='delete_file' && $file_id && $im_sure) {
		/*

			Physically delete a file from the download server and database

			First, make sure the file is theirs
			Second, delete it from the db
			Third, delete it from the download server


		*/
	        $res = delete_file($group_id, $file_id);
	        if ($res == 0) {
		  $feedback .= ' '.$Language->getText('file_admin_editreleases','f_not_yours').' ';
		} else {
		  $feedback .= ' '.$Language->getText('file_admin_editreleases','file_deleted').' ';
		}
		
	} else if ($func=='send_notice' && $package_id && $im_sure) {
		/*
			Send a release notification email
		*/
		$sql="SELECT user.email,frs_package.name ".
			"FROM user,filemodule_monitor,frs_package ".
			"WHERE user.user_id=filemodule_monitor.user_id ".
			"AND filemodule_monitor.filemodule_id=frs_package.package_id ".
			"AND filemodule_monitor.filemodule_id='$package_id' ".
                        "AND frs_package.group_id='$group_id' ".
                        "AND ( user.status='A' OR user.status='R' )";

		
		$result=db_query($sql);
		echo db_error();
		if ($result && db_numrows($result) > 0) {
			//send the email
			$array_emails=result_column_to_array($result);
			$list=implode($array_emails,', ');
		
			$subject=$GLOBALS['sys_name'].' '.$Language->getText('file_admin_editreleases','file_rel_notice').' '.$Language->getText('file_admin_editreleases','file_rel_notice_project', group_getunixname($group_id));
		
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
			$body = $Language->getText('file_admin_editreleases','download_explain_modified_package', db_result($result,0,'name'))." ".$Language->getText('file_admin_editreleases','download_explain',array("<".get_server_url()."/file/showfiles.php?group_id=$group_id&release_id=$release_id> ",$GLOBALS['sys_name'])).
				"\n<".get_server_url()."/file/filemodule_monitor.php?filemodule_id=$package_id> ";
			
			$mail =& new Mail();
            $mail->setFrom($GLOBALS['sys_noreply']);
            $mail->setBcc($list);
            $mail->setSubject($subject);
            $mail->setBody($body);
            if ($mail->send()) {
                $feedback .= ' '.$Language->getText('file_admin_editreleases','email_sent',db_numrows($result)).' ';
            } else {//ERROR
                $feedback .= ' '.$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']));
            }
		} 
	} else if ($func=='submit_file_news' && $release_id && $im_sure && user_ismember($group_id,'A')) {
	    //submit  the news  
	    $new_id=forum_create_forum($GLOBALS['sys_news_group'],$summary,1,0);
            $sql = sprintf('INSERT INTO news_bytes'.
	        '(group_id,submitted_by,is_approved,date,forum_id,summary,details)'.
		'VALUES (%d, %d, %d, %d, %d, "%s", "%s")',
		$group_id, user_getid(), 0, time(), $new_id, htmlspecialchars($summary), htmlspecialchars($details));
            $result=db_query($sql);
               
	    if (!$result) {
                $feedback .= ' '.$Language->getText('news_submit','insert_err').' ';
            } else {
                $feedback .= ' '.$Language->getText('news_submit','news_added').' ';
		// set permissions on this piece of news
		if ($private_news) {
		  news_insert_permissions($new_id,$group_id);
		}
            }		
	} else  if ($func=='update_permissions') {
            list ($return_code, $feedback) = permission_process_selection_form($_POST['group_id'], $_POST['permission_type'], $_POST['object_id'], $_POST['ugroups']);
            if (!$return_code) exit_error($Language->getText('global','error'),$Language->getText('file_admin_editpackages','perm_update_err').': <p>'.$feedback);
        }
}
if (isset($_POST['reset'])) {
    // Must reset access rights to defaults
    if (permission_clear_all($group_id, $_POST['permission_type'], $_POST['object_id'])) {
        $feedback=$Language->getText('file_admin_editpackages','perm_reset');
    } else {
        $feedback=$Language->getText('file_admin_editpackages','perm_reset_err');
    }
}


?><?php

if (isset($release_id) && (!isset($func) || $func != 'delete_release')) {

  
/*


	Show a specific release so it can be edited

	There are three differents parts of this, as described above


*/

	$sql="SELECT frs_release.release_date,frs_release.package_id,frs_release.name AS release_name,frs_release.status_id,".
		"frs_release.notes,frs_release.changes,frs_release.preformatted, ".
		"frs_package.name AS package_name ".
		"FROM frs_release,frs_package ".
		"WHERE frs_release.release_id='$release_id' ".
		"AND frs_package.package_id=frs_release.package_id ".
		"AND frs_package.group_id='$group_id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		//this result wasn't found
		echo db_error();
		exit_error($Language->getText('global','error'),$Language->getText('file_admin_editreleases','rel_id_not_found'));
	}

        file_utils_admin_header(array('title'=>$Language->getText('file_admin_editreleases','release_new_file_version'),
				   'help' => 'FileReleaseDelivery.html#ReleaseConfigurationandValidation'));


	echo '<TABLE BORDER="0" WIDTH="100%" class="small">
		<TR><TD>
		<H2>'.$Language->getText('file_admin_editreleases','step_x',1).'</H2>
		<P>
		'.$Language->getText('file_admin_editreleases','edit_change_notes').'
		<P>';
	/*

		Show the release notes info and release status

	*/

	//get the package_id for use below
	$package_id=db_result($result,0,'package_id');
	$release_name=db_result($result,0,'release_name');
	$url=get_server_url()."/file/showfiles.php?group_id=".$group_id;	

	echo '<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
        <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="';
    echo $sys_max_size_upload;
    echo '">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="update_release">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">
		<INPUT TYPE="HIDDEN" NAME="package_id" VALUE="'. db_result($result,0,'package_id') .'">

		<H3>'.$Language->getText('file_admin_editreleases','edit_release').':'. htmlspecialchars(db_result($result,0,'release_name')) .' '.$Language->getText('file_admin_editreleases','of_p').': '. db_result($result,0,'package_name') .'</H3>
		<P>
		<B>'.$Language->getText('file_admin_editreleases','release_date').':</B><BR>
		<INPUT TYPE="TEXT" NAME="release_date" VALUE="'. format_date('Y-m-d',db_result($result,0,'release_date')) .'" SIZE="10" MAXLENGTH="10">
		<P>
		<B>'.$Language->getText('file_admin_editreleases','release_name').':</B><BR>
		<INPUT TYPE="TEXT" NAME="release_name" VALUE="'. db_result($result,0,'release_name') .'" SIZE="20" MAXLENGTH="25">
		<P>
		<B>'.$Language->getText('global','status').':</B><BR>
		'. frs_show_status_popup ('status_id',db_result($result,0,'status_id')) .'
		<P>
		<B>'.$Language->getText('file_admin_editreleases','of_P').':</B><BR>
		'. frs_show_package_popup ($group_id,'new_package_id',db_result($result,0,'package_id')) .'
		<P>
		'.$Language->getText('file_admin_editreleases','upload_or_paste').'
		<BR>
		<INPUT TYPE="RADIO" NAME="upload_instead" VALUE="0" CHECKED> <B>'.$Language->getText('file_admin_editreleases','paste').'</B><BR>
		<INPUT TYPE="RADIO" NAME="upload_instead" VALUE="1"> <B>'.$Language->getText('file_admin_editreleases','upload_change').'</B><BR>
		<INPUT TYPE="RADIO" NAME="upload_instead" VALUE="2"> <B>'.$Language->getText('file_admin_editreleases','upload_notes').'</B><BR>
		<P>
		<input type="file" name="uploaded_data"  size="30">
        <br><span class="smaller"><i>'.$Language->getText('file_admin_editreleases','max_file_size',formatByteToMb($sys_max_size_upload)).'</i></span>
		<P>
		<B>'.$Language->getText('file_admin_editreleases','release_notes').':</B><BR>
		<TEXTAREA NAME="notes" ROWS="10" COLS="60" WRAP="SOFT">'. htmlspecialchars(db_result($result,0,'notes')) .'</TEXTAREA>
		<P>
		<B>'.$Language->getText('file_admin_editreleases','change_log').':</B><BR>
		<TEXTAREA NAME="changes" ROWS="10" COLS="60" WRAP="SOFT">'. htmlspecialchars(db_result($result,0,'changes')) .'</TEXTAREA>
		<P>
		<INPUT TYPE="CHECKBOX" NAME="preformatted" VALUE="1" '.((db_result($result,0,'preformatted'))?'CHECKED':'').'> '.$Language->getText('file_admin_editreleases','preserve_preformatted').'
		<P>
		<INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('file_admin_editreleases','submit_refresh').'">
		</FORM>';

/*


	Show other files in the upload directory
	So they can be attached to this release


*/


	echo '</TD></TR>
		<TR><TD>
		<HR NOSHADE><a name="step2"></a>
		<H2>'.$Language->getText('file_admin_editreleases','step_x',2).'</H2>
		<P>
		<H3>'.$Language->getText('file_admin_editreleases','attach_files').'</H3>';
		
	include($Language->getContent('file/editrelease_attach_file'));
	
	echo '<FORM ACTION="'.$PHP_SELF.'#step2" METHOD="POST" enctype="multipart/form-data">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="add_files">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">';

	$dirhandle = @opendir($ftp_incoming_dir);

	//iterate and show the files in the upload directory
	while ($file = @readdir($dirhandle)) {
		if ((!ereg('^\.',$file[0])) && is_file($ftp_incoming_dir.'/'.$file)) {
	       //file doesn't start with a .
			$atleastone = 1;
			print '
				<INPUT TYPE="CHECKBOX" NAME="file_list[]" value="'.$file.'">&nbsp;'.$file.'<BR>';
		}
	}


	if (!isset($atleastone)) {
	    print '<h3>'.$Language->getText('file_admin_editreleases','no_available_files').'</H3>
		     <P>
		     '.$Language->getText('file_admin_editreleases','upload_files');
	    echo '<P>
	                 <INPUT TYPE="SUBMIT" NAME="refresh" VALUE="'.$Language->getText('file_admin_editreleases','refresh_file_list').'">';
	} else {
	    print '<P>
	                   <INPUT TYPE="SUBMIT" NAME="refresh" VALUE="'.$Language->getText('file_admin_editreleases','refresh_file_list').'">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	                  <INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('file_admin_editreleases','attach_marked_files').'">';
	}
	echo '</FORM>';
?><?php

/*


	Show files already attached to this release


*/



	echo '</TD></TR>
		<TR><TD>
		<HR NOSHADE><a name="step3"></a>
		<H2>'.$Language->getText('file_admin_editreleases','step_x',3).'</H2>
		<P>
		<H3>'.$Language->getText('file_admin_editreleases','edit_files').':</H3>
		<P>
		'.$Language->getText('file_admin_editreleases','update_each').'
		<P>';

	$sql="SELECT * FROM frs_file WHERE release_id='$release_id'";
	$res=db_query($sql);
	$rows=db_numrows($res);
	if (!$res || $rows < 1) {
		echo '<H4>'.$Language->getText('file_admin_editreleases','no_files_attached').'</H4>
			<P>
			'.$Language->getText('file_admin_editreleases','attach_files_in_step2');
	} else {
		$title_arr=array();
		$title_arr[]=$Language->getText('file_admin_editreleases','filename').'<BR>'.$Language->getText('file_admin_editreleasepermissions','release');
		$title_arr[]=$Language->getText('file_admin_editreleases','processor').'<BR>'.$Language->getText('file_admin_editreleases','release_date');
		$title_arr[]=$Language->getText('file_admin_editreleases','file_type').'<BR>'.$Language->getText('file_admin_editpackages','update');

		echo html_build_list_table_top ($title_arr);

		/*

			iterate and show the files in this release

		*/

		for ($i=0; $i<$rows; $i++) {
		  $fname = db_result($res,$i,'filename');
		  $list = split('/', $fname);
		  $fname = $list[sizeof($list) - 1];

		  echo '
			<FORM ACTION="'. $PHP_SELF .'#step3" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">
			<INPUT TYPE="HIDDEN" NAME="func" VALUE="update_file">
			<INPUT TYPE="HIDDEN" NAME="file_id" VALUE="'. db_result($res,$i,'file_id') .'">
			<TR class="'. util_get_alt_row_color($i) .'">
				<TD NOWRAP><FONT SIZE="-1">'. $fname .'</TD>
				<TD><FONT SIZE="-1">'. frs_show_processor_popup ('processor_id', db_result($res,$i,'processor_id')) .'</TD>
				<TD><FONT SIZE="-1">'. frs_show_filetype_popup ('type_id', db_result($res,$i,'type_id')) .'</TD>
			</TR>
			<TR class="'. util_get_alt_row_color($i) .'">
				<TD><FONT SIZE="-1">'. 
					frs_show_release_popup ($group_id, $name='new_release_id',db_result($res,$i,'release_id')) .'</TD>
				<TD><FONT SIZE="-1"><INPUT TYPE="TEXT" NAME="release_time" VALUE="'. format_date('Y-m-d',db_result($res,$i,'release_time')) .'" SIZE="10" MAXLENGTH="10"></TD>
				<TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('file_admin_editreleases','update_refresh').'"></TD>
			</TR></FORM>
			<FORM ACTION="'. $PHP_SELF .'#step3" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">
			<INPUT TYPE="HIDDEN" NAME="func" VALUE="delete_file">
			<INPUT TYPE="HIDDEN" NAME="file_id" VALUE="'. db_result($res,$i,'file_id') .'">
			<TR class="'. util_get_alt_row_color($i) .'">
				<TD><FONT SIZE="-1">&nbsp;</TD>
				<TD><FONT SIZE="-1">&nbsp;</TD>
				<TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('file_admin_editreleases','delete_file').'"> <INPUT TYPE="checkbox" NAME="im_sure" VALUE="1"> '.$Language->getText('file_admin_editreleases','im_sure').' </TD>
			</TR></FORM>';
		}
		echo '</TABLE>';
		
		/*
			Create automatic news
		 */
			
		if (user_ismember($group_id,'A')) {
		    echo '
			</TD></TR><TR><TD>
			<HR><NOSHADE>
			<H2>'.$Language->getText('file_admin_editreleases','step_x',4).'</H2>
			<P>
			<H3>'.$Language->getText('file_admin_editreleases','create_auto_news').':</H3>
			<P>
			'.$Language->getText('file_admin_editreleases','rel_news').'
			<P>
			<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">
			<INPUT TYPE="HIDDEN" NAME="func" VALUE="submit_file_news">						
			<P>
			<B>'.$Language->getText('file_admin_editreleases','subject').':</B><BR>
			<INPUT TYPE="TEXT" NAME="summary" VALUE="'.$Language->getText('file_admin_editreleases','file_news_subject',$release_name).'" SIZE="53" MAXLENGTH="60">
			<P>
			<B>'.$Language->getText('file_admin_editreleases','details').':</B><BR>
			<TEXTAREA NAME="details" ROWS="10" COLS="60" WRAP="SOFT">'.$Language->getText('file_admin_editreleases','file_news_details',array($release_name,$url)).'</TEXTAREA>
			<P>
			<TABLE BORDER=0>
			<TR><TD ROWSPAN=2 VALIGN="top"><B>'.$Language->getText('news_submit','news_privacy').':</B></TD>
			<TD><INPUT TYPE="RADIO" NAME="private_news" VALUE="0" CHECKED>'. $Language->getText('news_submit','public_news').'</TD></TR> 
			<TR><TD><INPUT TYPE="RADIO" NAME="private_news" VALUE="1">'. $Language->getText('news_submit','private_news').'</TD></TR> 
			</TABLE><P>
			<INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('file_admin_editreleases','submit_news').'">  <INPUT TYPE="checkbox" NAME="im_sure" VALUE="1"> '.$Language->getText('file_admin_editreleases','im_sure').'
			</FORM>';						
		}	
	}
	
/*

	Send out file release notice

*/
	$count=db_result(db_query("SELECT count(*) from filemodule_monitor WHERE filemodule_id='$package_id'"),0,0);
	(user_ismember($group_id,'A')) ? $num=5 : $num=4;
	if ($count>0) {
	echo '</TD></TR>
		<TR><TD>
		<HR NOSHADE>
		<H2>'.$Language->getText('file_admin_editreleases','step_x',$num).'</H2>
		<P>
		<H3>'.$Language->getText('file_admin_editreleases','mail_file_rel_notice').':</H3>
		<P>
		'.$Language->getText('file_admin_editreleases','users_monitor',$count).'
		<P>
		<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="send_notice">
		<INPUT TYPE="HIDDEN" NAME="package_id" VALUE="'. $package_id .'">
		<INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('file_admin_editreleases','send_notice').'"> <INPUT TYPE="checkbox" NAME="im_sure" VALUE="1"> '.$Language->getText('file_admin_editreleases','im_sure').'
		</FORM>';		
	}
	echo '</TD></TR></TABLE>';
} else {


    if (isset($func) && ($func == "delete_release") && $group_id) {
    /*
         Delete a release with all the files included
         Delete the corresponding row from the database
         Delete the corresponding directory from the server
    */
    $res = delete_release($group_id, $release_id);
    if ($res == 0) {
      $feedback .= ' '.$Language->getText('file_admin_editreleases','rel_not_yours').' ';
    } else {
      $feedback .= ' '.$Language->getText('file_admin_editreleases','rel_del').' ';
    }
  } 
	/*

		Show existing releases and a form to create a new release

	*/
  file_utils_admin_header(array('title'=>$Language->getText('file_admin_editreleases','release_new_file_version'),
				   'help' => 'FileReleaseDelivery.html#ReleaseCreation'));

	echo '<H3>'.$Language->getText('file_admin_editreleases','define_new_release').'</H3>
	<P>
	'.$Language->getText('file_admin_editreleases','contain_multiple_files').'

	<h4>'.$Language->getText('file_admin_editreleases','your_release').':</H4>';

	/*

		Show a list of existing releases
		for this project so they can
		be edited in detail

	*/

	if ($package_id) {
		//narrow the list to just this package's releases
		$pkg_str = "AND frs_package.package_id='$package_id'";
	}

	$res=db_query("SELECT frs_release.release_id,frs_package.name AS package_name,".
		"frs_package.package_id,frs_release.name AS release_name,frs_release.status_id,frs_status.name AS status_name ".
		"FROM frs_release,frs_package,frs_status ".
		"WHERE frs_package.group_id='$group_id' ".
		"AND frs_release.package_id=frs_package.package_id ".
		" $pkg_str ".
		"AND frs_status.status_id=frs_release.status_id");

	$rows=db_numrows($res);
	if (!$res || $rows < 1) {
	  echo '<h4>'.$Language->getText('file_admin_editreleases','no_releases_defined',(($package_id)?$Language->getText('file_admin_editreleases','of_this_package').' ':'')).'</h4>';
		echo db_error();
	} else {
		/*

			Show a list of releases
			For this project or package

		*/
		$title_arr=array();
		$title_arr[]=$Language->getText('file_admin_editreleases','release_name');
		$title_arr[]=$Language->getText('file_admin_editpackages','p_name');
		$title_arr[]=$Language->getText('global','status');
		$title_arr[]=$Language->getText('file_admin_editpackages','perms');
		$title_arr[]=$Language->getText('file_admin_editreleases','delete');

		echo html_build_list_table_top ($title_arr);

		for ($i=0; $i<$rows; $i++) {
		  echo '<TR class="'. util_get_alt_row_color($i) .'">'.
		    '<TD><FONT SIZE="-1"><A HREF="editreleases.php?release_id='. 
		    db_result($res,$i,'release_id') .'&group_id='. $group_id .'" title="'.$Language->getText('file_admin_editreleases','edit_this_release').'">'.
		    db_result($res,$i,'release_name') .'</A></TD>'.
		    '<TD><FONT SIZE="-1"><A HREF="editpackages.php?group_id='.
		    $group_id.'" title="'.$Language->getText('file_admin_editreleases','edit_this_p').'">'. 
		    db_result($res,$i,'package_name') 
		    .' </TD>'.
                      '<TD><FONT SIZE="-1">'. $Language->getText('file_admin_editpackages',db_result($res,$i,'status_name')) .'</TD>
                      <TD  align="center" NOWRAP><FONT SIZE="-1"><A HREF="editreleasepermissions.php?release_id='. 
				db_result($res,$i,'release_id') .'&group_id='. $group_id.'&package_id='.$package_id .'">['; 
                  if (permission_exist('RELEASE_READ',db_result($res,$i,'release_id'))) {
                      echo $Language->getText('file_admin_editpackages','edit');
                  } else echo $Language->getText('file_admin_editpackages','define');
                  echo ' '.$Language->getText('file_admin_editpackages','perms').']</A></TD>'.
		    '<TD align="center"><FONT SIZE="-1">'. 
		    '<a href="/file/admin/editreleases.php?func=delete_release&group_id='. $group_id .'&release_id='.db_result($res,$i,'release_id').'&package_id='.$package_id.'">'.
		    '<img src="'.util_get_image_theme("ic/trash.png").'" border="0" onClick="return confirm(\''.$Language->getText('file_admin_editreleases','warn').'\')"></a>'.'</TD>'.
		    '</TR>       ';
		}
		echo '</TABLE>';
	}

	/*

		Form to create a new release

		When they hit submit, they are shown the detail page for that new release

	*/

	echo '<P>
	<h3>'.$Language->getText('file_admin_editreleases','new_release_name').':</h3>
	<P>
	<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="add_release">
	<INPUT TYPE="TEXT" NAME="release_name" VALUE="" SIZE="20" MAXLENGTH="25">

	&nbsp;&nbsp;&nbsp;'.$Language->getText('file_admin_editreleases','belongs_to_p').':
	'. frs_show_package_popup ($group_id,'package_id',$package_id) .'
	<P>
	<INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('file_admin_editreleases','create_this_release').'">
	</FORM>';

}

file_utils_footer(array());




?>
