<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

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


session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($submit) {
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
			$feedback .= ' Must create a package before you create a release. You must also include a release name. ';
		} else {
			//create a new release of this package

			//see if this package belongs to this project
			$res1=db_query("SELECT * FROM frs_package WHERE package_id='$package_id' AND group_id='$group_id'");
			if (!$res1 || db_numrows($res1) < 1) {
				$feedback .= ' | Package Doesn\'t Exist Or Isn\'t Yours ';
				echo db_error();
			} else {
				//package_id was fine - now insert the release
				$res=db_query("INSERT INTO frs_release (package_id,name,status_id,release_date,released_by) ".
					"VALUES ('$package_id','$release_name','1','". time() ."','". user_getid() ."')");
				if (!$res) {
					$feedback .= ' | Adding Release Failed ';
					echo db_error();
					//insert failed - go back to definition screen
				} else {
					//release added - now show the detail page for this new release
					$release_id=db_insertid($res);
					$feedback .= ' Added Release ';
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
		$feedback .= ' Updating Release ';
		if ($upload_instead) {
			$code = addslashes(fread( fopen($uploaded_data, 'r'), filesize($uploaded_data)));
			if ((strlen($code) > 0) && (strlen($code) < $sys_max_size_upload)) {
				//size is fine
				$feedback .= ' | Data Uploaded ';
			} else {
				//too big or small
				$feedback .= ' | ERROR - uploaded data must be non null and < '.$sys_max_size_upload.' in length ';
				$code='';
			}
			if ($upload_instead == 1) {
				//uploaded change log
				$changes=$code;
			} else if ($upload_instead == 2) {
				//uploaded release notes
				$notes=$code;
			} else {
				$feedback .= ' | ERROR invalid upload flag ';
			}
		}


		if (!$release_name || !$package_id || !$status_id) {
			$feedback .= ' Must create a package before you create a release. You must also include a release name and status. ';
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
					exit_error('ERROR','Trying to change to a package that isn\'t yours');
				}
			}
			if (!$res1 || db_numrows($res1) < 1) {
				$feedback .= ' | Package Release Doesn\'t Exist Or Isn\'t Yours ';
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
//						$feedback .= ' | Sorry - you cannot delete a release that still contains files ';
//						$status_id=1;
//					}
// LJ				}

				//now update the file entry
				if (!ereg("[0-9]{4}-[0-9]{2}-[0-9]{2}",$release_date)) {
					$feedback .= ' | Sorry - Date entry could not be parsed. It must be in YYYY-MM-DD format. ';
				} else { //is valid date... parse it

					$date_list = split("-",$release_date,3);
					$unix_release_time = mktime(0,0,0,$date_list[1],$date_list[2],$date_list[0]);

					$res=db_query("UPDATE frs_release SET release_date='$unix_release_time',name='$release_name',preformatted='$preformatted', ".
						"status_id='$status_id',package_id='$new_package_id',notes='$notes',changes='$changes' ".
						"WHERE release_id='$release_id'");
					if (!$res) {
						$feedback .= ' | Updating Release Failed ';
						echo db_error();
					} else {
						$feedback .= ' | Updated Release ';
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
			$feedback .= " | Not Your File Or File Doesn't Exist ";
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
					exit_error('ERROR','Not Your Release Or Release Doesn\'t Exist');
				}
			}
			//now update the file entry
			if (!ereg("[0-9]{4}-[0-9]{2}-[0-9]{2}",$release_time)) {
				$feedback .= ' | Sorry - Date entry could not be parsed. It must be in YYYY-MM-DD format. ';
			} else { //is valid date... parse it
				$date_list = split("-",$release_time,3);
				$unix_release_time = mktime(0,0,0,$date_list[1],$date_list[2],$date_list[0]);

				$res=db_query("UPDATE frs_file SET release_id='$new_release_id',release_time='$unix_release_time',type_id='$type_id',processor_id='$processor_id' ".
					"WHERE file_id='$file_id'");
				$feedback .= ' File Updated ';
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
		$project_files_dir=$FTPFILES_DIR.'/'.$group_unix_name;

		$count=count($file_list);
		if ($count > 0) {
			$feedback .= ' Adding File(s) ';
			//see if this release belongs to this project
			$res1=db_query("SELECT frs_package.package_id FROM frs_package,frs_release ".
				"WHERE frs_package.group_id='$group_id' ".
				"AND frs_release.release_id='$release_id' ".
				"AND frs_release.package_id=frs_package.package_id");
			if (!$res1 || db_numrows($res1) < 1) {
				//release not found for this project
				$feedback .= " | Not Your Release Or Release Doesn't Exist ";
			} else {
				$now=time();
				//iterate and add the files to the frs_file table
				for ($i=0; $i<$count; $i++) {
					//see if filename is legal before adding it
					if (!util_is_valid_filename ($file_list[$i])) {
						$feedback .= " | Illegal FileName: $file_list[$i] ";
					} else {
					  // get the package id and compute the upload directory
					  $pres = db_query("SELECT frs_package.package_id FROM frs_package,frs_release ".
							   "WHERE frs_package.group_id='$group_id' ".
							   "AND frs_release.release_id='$release_id' ".
							   "AND frs_release.package_id=frs_package.package_id ");
						  
					  if (!$pres || db_numrows($pres) < 1) { 
					    $feedback .= ' | Package/Release Doesn\'t Exist Or Isn\'t Yours ';
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
							if (is_file($FTPINCOMING_DIR.'/'.$file_list[$i]) && file_exists($FTPINCOMING_DIR.'/'.$file_list[$i])) {
							  //move the file to a its project page using a setuid program
							  exec ("/bin/date > /tmp/".$group_unix_name."$group_id",$exec_res);
							  exec ("/usr/local/bin/fileforge /tmp/".$group_unix_name."$group_id ".$group_unix_name, $exec_res); 
							  exec ("/usr/local/bin/fileforge ".$file_list[$i]." ".$group_unix_name."/".$upload_subdir,$exec_res);
							  if ($exec_res[0]) {
							    echo '<h3>'.$exec_res[0],$exec_res[1].'</H3><P>';
							  }
							  //add the file to the database
							  $res=db_query("INSERT INTO frs_file ".
									"(release_time,filename,release_id,file_size,post_date) ".
									"VALUES ('$now','$upload_subdir/$file_list[$i]','$release_id','". filesize("$project_files_dir/$upload_subdir/$file_list[$i]") ."','$now') ");
							  if (!$res) {
							    $feedback .= " | Couldn't Add FileName: $file_list[$i] ";
							    echo db_error();
								}
							} else {
							  $feedback .= " | FileName Invalid Or Does Not Exist: $file_list[$i] ";
							}
						} else {
							$feedback .= " | FileName Already Exists For This Project: $file_list[$i] ";
						}
					}
				}
			}
		} else {
			//do nothing
			$feedback .= ' No Files Selected ';
		}
	} else if ($func=='delete_file' && $file_id && $im_sure) {
		/*

			Physically delete a file from the download server and database

			First, make sure the file is theirs
			Second, delete it from the db
			Third, delete it from the download server


		*/
		$res1=db_query("SELECT frs_file.filename FROM frs_package,frs_release,frs_file ".
		"WHERE frs_package.group_id='$group_id' ".
		"AND frs_release.release_id=frs_file.release_id ".
		"AND frs_release.package_id=frs_package.package_id ".
		"AND frs_file.file_id='$file_id'");
		if (!$res1 || db_numrows($res1) < 1) {
			//release not found for this project
			$feedback .= " Not Your File Or File Doesn't Exist ";
		} else {
			/*
				delete the file from the database
			*/
			db_query("DELETE FROM frs_file WHERE file_id='$file_id'");
			//append the filename and project name to a temp file for the root perl job to grab
			exec ("/bin/echo \"". db_result($res1,0,'filename') ."::". group_getunixname($group_id) ."::xxx\" >> $FTPINCOMING_DIR/.delete_files");
			$feedback .= " File Deleted ";
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
			"AND frs_package.group_id='$group_id'";
		
		$result=db_query($sql);
		echo db_error();
		if ($result && db_numrows($result) > 0) {
			//send the email
			$array_emails=result_column_to_array($result);
			$list=implode($array_emails,', ');
		
			$subject=$GLOBALS['sys_name'].' File Release Notice';
		
			$body = "To: noreply@$GLOBALS[HTTP_HOST]".
				"\nBCC: $list".
				"\nSubject: $subject".
				"\n\nA new version of ". db_result($result,0,'name')." has been released. ".
				"\nYou can download it at: ".
				"\n\n<http://".$GLOBALS['sys_default_domain']."/project/showfiles.php?group_id=$group_id&release_id=$release_id> ".
				"\n\nYou requested to be notified when new versions of this file ".
				"\nwere released. If you don't wish to be notified in the ".
				"\nfuture, please login to ".$GLOBALS['sys_name']." and click this link: ".
				"\n<http://".$GLOBALS['sys_default_domain']."/project/filemodule_monitor.php?filemodule_id=$package_id> ";
			
			exec ("/bin/echo \"$body\" | /usr/sbin/sendmail -fnoreply@$GLOBALS[HTTP_HOST] -t");
			$feedback .= ' email sent - '. db_numrows($result) .' users tracking ';
		}
	}
}

?><?php

if ($release_id) {

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
		exit_error('ERROR','That release ID was not found in the database');
	}

	project_admin_header(array('title'=>'Release New File Version',
				   'group'=>$group_id,
				   'help' => 'FileReleaseDelivery.html#ReleaseConfigurationandValidation'));

	echo '<TABLE BORDER="0" WIDTH="100%" class="small">
		<TR><TD>
		<H2>Step 1</H2>
		<P>
		Edit the change notes for this release of this package. These notes will apply to all files attached to this release.
		<P>';
	/*

		Show the release notes info and release status

	*/

	//get the package_id for use below
	$package_id=db_result($result,0,'package_id');

	echo '<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
        <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="';
    echo $sys_max_size_upload;
    echo '">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="update_release">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">
		<INPUT TYPE="HIDDEN" NAME="package_id" VALUE="'. db_result($result,0,'package_id') .'">

		<H3>Edit Release:'. htmlspecialchars(db_result($result,0,'release_name')) .' of Package: '. db_result($result,0,'package_name') .'</H3>
		<P>
		<B>Release Date:</B><BR>
		<INPUT TYPE="TEXT" NAME="release_date" VALUE="'. format_date('Y-m-d',db_result($result,0,'release_date')) .'" SIZE="10" MAXLENGTH="10">
		<P>
		<B>Release Name:</B><BR>
		<INPUT TYPE="TEXT" NAME="release_name" VALUE="'. db_result($result,0,'release_name') .'" SIZE="20" MAXLENGTH="25">
		<P>
		<B>Status:</B><BR>
		'. frs_show_status_popup ('status_id',db_result($result,0,'status_id')) .'
		<P>
		<B>Of Package:</B><BR>
		'. frs_show_package_popup ($group_id,'new_package_id',db_result($result,0,'package_id')) .'
		<P>
		You can either upload the release notes and change log individually, or paste them in together below.
		<BR>
		<INPUT TYPE="RADIO" NAME="upload_instead" VALUE="0" CHECKED> <B>Paste The Notes In</B><BR>
		<INPUT TYPE="RADIO" NAME="upload_instead" VALUE="1"> <B>Upload Change Log</B><BR>
		<INPUT TYPE="RADIO" NAME="upload_instead" VALUE="2"> <B>Upload Release Notes</B><BR>
		<P>
		<input type="file" name="uploaded_data"  size="30">
        <br><span class="smaller"><i>(The maximum upload file size is ';
    echo formatByteToMb($sys_max_size_upload);
    echo ' Mb)</i></span>
		<P>
		<B>Release Notes:</B><BR>
		<TEXTAREA NAME="notes" ROWS="10" COLS="60" WRAP="SOFT">'. htmlspecialchars(db_result($result,0,'notes')) .'</TEXTAREA>
		<P>
		<B>Change Log:</B><BR>
		<TEXTAREA NAME="changes" ROWS="10" COLS="60" WRAP="SOFT">'. htmlspecialchars(db_result($result,0,'changes')) .'</TEXTAREA>
		<P>
		<INPUT TYPE="CHECKBOX" NAME="preformatted" VALUE="1" '.((db_result($result,0,'preformatted'))?'CHECKED':'').'> Preserve my pre-formatted text.
		<P>
		<INPUT TYPE="SUBMIT" NAME="submit" VALUE="Submit/Refresh">
		</FORM>';

/*


	Show other files in the upload directory
	So they can be attached to this release


*/


	echo '</TD></TR>
		<TR><TD>
		<HR NOSHADE>
		<H2>Step 2</H2>
		<P>
		<H3>Attach Files To This Release</H3>
		
		<P>
		To attach your files to this release you must first upload them to the '.$GLOBALS['sys_name'].' server. To do so
                            use FTP  to ';
echo "<b>$sys_download_host</b> (login ftp / password is your email address)";
echo ' and put your files in the <B>/incoming</B> directory. When you 
		are done uploading, hit the "Refresh File List" button right below to see the uploaded release files.
		<P>
Then check the boxes next to the files belonging to your new project release and click on the "Attach Marked Files" button below. 

<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="add_files">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">';

	$dirhandle = opendir($FTPINCOMING_DIR);

	//iterate and show the files in the upload directory
	while ($file = readdir($dirhandle)) {
		if ((!ereg('^\.',$file[0])) && is_file($FTPINCOMING_DIR.'/'.$file)) {
	       //file doesn't start with a .
			$atleastone = 1;
			print '
				<INPUT TYPE="CHECKBOX" NAME="file_list[]" value="'.$file.'">&nbsp;'.$file.'<BR>';
		}
	}


	if (!$atleastone) {
	    print '<h3>No available files</H3>
		     <P>
		     Please upload files as explained above, then hit <B>Refresh File List</B>.';
	    echo '<P>
	                 <INPUT TYPE="SUBMIT" NAME="refresh" VALUE="Refresh File List">';
	} else {
	    print '<P><B>Choose ONLY YOUR files.</B> The /incoming directory 
                                is a space shared by all projects. If you choose someone else\'s files, 
		    they will not be able to access them and they will be rightfully upset.';
	    print '<P>
	                   <INPUT TYPE="SUBMIT" NAME="refresh" VALUE="Refresh File List">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	                  <INPUT TYPE="SUBMIT" NAME="submit" VALUE="Attach Marked Files">';
	}
	echo '</FORM>';
?><?php

/*


	Show files already attached to this release


*/



	echo '</TD></TR>
		<TR><TD>
		<HR NOSHADE>
		<H2>Step 3</H2>
		<P>
		<H3>Edit Files in this Release:</H3>
		<P>
		You <B>must</B> update each of these files with the correct information or 
		they will not appear on your download summary page.
		<P>';

	$sql="SELECT * FROM frs_file WHERE release_id='$release_id'";
	$res=db_query($sql);
	$rows=db_numrows($res);
	if (!$res || $rows < 1) {
		echo '<H4>No Files attached to this Release</H4>
			<P>
			You can attach files using Step 2 above';
	} else {
		$title_arr=array();
		$title_arr[]='Filename<BR>Release';
		$title_arr[]='Processor<BR>Release Date';
		$title_arr[]='File Type<BR>Update';

		echo html_build_list_table_top ($title_arr);

		/*

			iterate and show the files in this release

		*/

		for ($i=0; $i<$rows; $i++) {
		  $fname = db_result($res,$i,'filename');
		  $list = split('/', $fname);
		  $fname = $list[sizeof($list) - 1];

		  echo '
			<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
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
				<TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="submit" VALUE="Update/Refresh"></TD>
			</TR></FORM>
			<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">
			<INPUT TYPE="HIDDEN" NAME="func" VALUE="delete_file">
			<INPUT TYPE="HIDDEN" NAME="file_id" VALUE="'. db_result($res,$i,'file_id') .'">
			<TR class="'. util_get_alt_row_color($i) .'">
				<TD><FONT SIZE="-1">&nbsp;</TD>
				<TD><FONT SIZE="-1">&nbsp;</TD>
				<TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="submit" VALUE="Delete File"> <INPUT TYPE="checkbox" NAME="im_sure" VALUE="1"> I\'m Sure </TD>
			</TR></FORM>';
		}
		echo '</TABLE>';
	}
/*

	Send out file release notice

*/
	$count=db_result(db_query("SELECT count(*) from filemodule_monitor WHERE filemodule_id='$package_id'"),0,0);
	if ($count>0) {
	echo '</TD></TR>
		<TR><TD>
		<HR NOSHADE>
		<H2>Step 4</H2>
		<P>
		<H3>Email File Release Notice:</H3>
		<P>
		'. $count .' user(s) are monitoring your package. You should send a notice of your file release.
		<P>
		<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="release_id" VALUE="'.$release_id.'">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="send_notice">
		<INPUT TYPE="HIDDEN" NAME="package_id" VALUE="'. $package_id .'">
		<INPUT TYPE="SUBMIT" NAME="submit" VALUE="Send Notice"> <INPUT TYPE="checkbox" NAME="im_sure" VALUE="1"> I\'m Sure
		</FORM>';
	}
	echo '</TD></TR></TABLE>';

} else {
	/*

		Show existing releases and a form to create a new release

	*/
	project_admin_header(array('title'=>'Release New File Version',
				   'group'=>$group_id,
				   'help' => 'FileReleaseDelivery.html#ReleaseCreation'));

	echo '<H3>Define a New Release of a Package</H3>
	<P>
	A release of a package can contain multiple files. Release names can be either version numbers (3.22.1, 3.23-beta1&hellip;) or names.

	<h4>Your Releases:</H4>';

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
		echo '<h4>You Have No Releases '.(($package_id)?'Of This Package ':'').'Defined</h4>';
		echo db_error();
	} else {
		/*

			Show a list of releases
			For this project or package

		*/
		$title_arr=array();
		$title_arr[]='Release Name';
		$title_arr[]='Package Name';
		$title_arr[]='Status';

		echo html_build_list_table_top ($title_arr);

		for ($i=0; $i<$rows; $i++) {
			echo '
			<TR class="'. util_get_alt_row_color($i) .'">
				<TD><FONT SIZE="-1">'. db_result($res,$i,'release_name') 
					.' <A HREF="editreleases.php?release_id='. 
					db_result($res,$i,'release_id') .'&group_id='. 
					$group_id .'">[Edit This Release]</A></TD>
				<TD><FONT SIZE="-1">'. 
					db_result($res,$i,'package_name') 
					.' <A HREF="editpackages.php?group_id='.
					$group_id.'">[Edit This Package]</TD>
				<TD><FONT SIZE="-1">'. db_result($res,$i,'status_name') .'</TD>
			</TR></FORM>';
		}
		echo '</TABLE>';
	}

	/*

		Form to create a new release

		When they hit submit, they are shown the detail page for that new release

	*/

	echo '<P>
	<h3>New Release Name:</h3>
	<P>
	<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="add_release">
	<INPUT TYPE="TEXT" NAME="release_name" VALUE="" SIZE="20" MAXLENGTH="25">

	&nbsp;&nbsp;&nbsp;belongs to Package:
	'. frs_show_package_popup ($group_id,'package_id',$package_id) .'
	<P>
	<INPUT TYPE="SUBMIT" NAME="submit" VALUE="Create This Release">
	</FORM>';

}

project_admin_footer(array());

?>
