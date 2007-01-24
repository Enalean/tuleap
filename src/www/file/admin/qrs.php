<?php

//
//SourceForge: Breaking Down the Barriers to Open Source Development
//Copyright 1999-2000 (c) The SourceForge Crew
//http://sourceforge.net
//
//$Id$

require_once ('pre.php');
require_once ('www/file/file_utils.php');
require_once ('common/include/SimpleSanitizer.class.php');
require_once ('common/mail/Mail.class.php');
require_once ('www/forum/forum_utils.php');
require_once ('common/frs/FRSPackageFactory.class.php');
require_once ('common/frs/FRSReleaseFactory.class.php');
require_once ('common/frs/FRSFileFactory.class.php');
$Language->loadLanguageMsg('file/file');
$Language->loadLanguageMsg('news/news');

/*
 Quick file release system , Darrell Brogdon, SourceForge, Aug, 2000
 
 With much code horked from editreleases.php
 */

if (!user_ismember($group_id, 'R2')) {
	exit_permission_denied();
}
file_utils_admin_header(array (
	'title' => $Language->getText('file_admin_editreleases',
	'release_new_file_version'
), 'help' => 'QuickFileRelease.html'));
$frspf = new FRSPackageFactory();
$frsrf = new FRSReleaseFactory();
$frsff = new FRSFileFactory();
if (isset ($submit)) {
	//Sanitize some fields
	$strings_to_sanitize = array (
		'release_name',
		'release_notes',
		'release_changes'
	);
	$sanitizer = new SimpleSanitizer();
	foreach ($strings_to_sanitize as $str) {
		if (isset ($_REQUEST[$str])) {
			$_REQUEST[$str] = $sanitizer->sanitize($_REQUEST[$str]);
			$$str = $_REQUEST[$str];
		}
	}

	if (!isset ($release_name) || trim($release_name) == '') {
		$feedback .= ' ' . $Language->getText('file_admin_qrs', 'define_rel_name') . ' ';
		echo db_error();
		file_utils_footer(array ());
		exit;
	}

	if (!isset ($file_name)) {
		$feedback .= ' ' . $Language->getText('file_admin_editreleases', 'no_files_selected') . ' ';
		file_utils_footer(array ());
		exit;
	}

	// Check to see if the user uploaded a file instead of selecting an existing one.
	// If so then move it to the 'incoming' dir where we proceed as usual.
	if ($file_name == "qrs_newfile") {
		if (!isset ($_FILES['userfile']['name']) || trim($_FILES['userfile']['name']) == '') {
			$feedback .= ' ' . $Language->getText('file_admin_editreleases', 'no_files_selected') . ' ';
			file_utils_footer(array ());
			exit;
		}
		$file_name = $_FILES['userfile']['name'];

		if (!util_is_valid_filename($file_name)) {
			$feedback .= ' ' . $Language->getText('file_admin_editreleases', 'illegal_file_name') . ": $file_name ";
			file_utils_footer(array ());
			exit;
		}
	}

	//create a new release of this package

	//see if this package belongs to this project
	$res1 = & $frspf->getFRSPackageFromDb($package_id, $group_id);
	if (!$res1 || count($res1) < 1) {
		$feedback .= ' | ' . $Language->getText('file_admin_editreleases', 'p_not_exists') . ' ';
		echo db_error();
		file_utils_footer(array ());
		exit;
	}

	if ($processor_id == 100) {
		$feedback .= ' ' . $Language->getText('file_admin_qrs', 'choose_processor_type') . ' ';
		file_utils_footer(array ());
		exit;
	}

	if ($type_id == 100) {
		$feedback .= ' ' . $Language->getText('file_admin_qrs', 'choose_file_type') . ' ';
		file_utils_footer(array ());
		exit;
	}

	//package_id was fine - now insert the release
	// $package_name = db_result($res1, 0, 'name');
	$package_name = $res1->getName();
	//package_id was fine - now update/insert the release if admin rights on package/release
	// get release_id for release name in this package
	//$rel_res = db_query("SELECT release_id from frs_release where frs_release.package_id='$package_id' and frs_release.name='$release_name'");
	$rel_res = $frsrf->getReleaseIdByName($release_name, $package_id);
	//echo "query=SELECT release_id from frs_release where frs_release.package_id='$package_id' and frs_release.name='$release_name'<br>";
	if (!$rel_res || count($rel_res) < 1) {
		$array = array (
			'package_id' => $package_id,
			'name' => $release_name,
			'release_notes' => $release_notes,
			'release_changes' => $release_changes,
			'status_id' => '1'
		);
		$res = $frsrf->create($array);
		/*$res=db_query("INSERT INTO frs_release (package_id,name,notes,changes,status_id,release_date,released_by) ".
		 "VALUES ('$package_id','$release_name','$release_notes','$release_changes','$status_id','". time() ."','". user_getid() ."')");*/
		if (!$res) {
			$feedback .= ' | ' . $Language->getText('file_admin_editreleases', 'add_rel_fail') . ' ';
			echo db_error();
			//insert failed - go back to definition screen
		} else {
			//release added - now show the detail page for this new release
			$release_id = $res;
			$feedback .= ' ' . $Language->getText('file_admin_editreleases', 'rel_added') . ' <BR>';
		}
	} else {
		$release_id = $rel_res;
		// update found release with $release_name','$release_notes','$release_changes','$status_id'
		$array = array ();
		$array['status_id'] = $status_id;
		if ($release_name != "") {
			$array['name'] = $release_name;
		}
		if ($release_notes != "") {
			$array['notes'] = $release_notes;
		}
		if ($release_changes != "") {
			$array['changes'] = $release_changes;
		}
		$array['release_id'] = $release_id;
		$resupdate = $frsrf->update($array);
		//$resupdate = db_query("UPDATE frs_release SET $fields_str WHERE release_id='$release_id'");
	}

	/*
	 Add a file to this release
	 
	 First, make sure this release belongs to this group
	 
	 iterate the following for each file:
	 
	 Second see if the filename is legal
	 Third see if they already have a file by the same name
	 Fourth if file actually exists, physically move the file on garbage to the new location
	 Fifth insert it into the database
	 */
	$group_unix_name = group_getunixname($group_id);
	$project_files_dir = $ftp_frs_dir_prefix . '/' . $group_unix_name;

	if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
		$uploaddir = $GLOBALS['ftp_incoming_dir'];
		$uploadfile = $uploaddir . "/" . basename($_FILES['userfile']['name']);
		if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			$feedback .= ' | ' . $Language->getText('file_admin_editreleases', 'add_rel_fail') . ": " . basename($_FILES['userfile']['name']);
			file_utils_footer(array ());
		}
	}

	$feedback .= ' ' . $Language->getText('file_admin_qrs', 'adding_file') . ' ';
	//see if this release belongs to this project
	$res1 = & $frsrf->getFRSReleaseFromDb($release_id, $group_id);
	if (!$res1 || count($res1) < 1) {
		//release not found for this project
		$feedback .= ' | ' . $Language->getText('file_admin_editreleases', 'rel_not_yours') . ' ';
		file_utils_footer(array ());
		exit;
	}

	$now = time();

	//see if they already have a file by this name

	$upload_subdir = 'p' . $package_id . '_r' . $release_id;
	$res1 = $frsff->isFileNameExist($upload_subdir . '/' . $file_name, $group_id);

	if ($res1 && count($res1) > 0) {
		$feedback .= ' | ' . $Language->getText('file_admin_editreleases', 'filename_exists') . ": $file_name ";
		file_utils_footer(array ());
		exit;
	}

	/*
	 move the file to the project's fileserver directory
	 */
	clearstatcache();
	if (is_file($ftp_incoming_dir . '/' . $file_name) && file_exists($ftp_incoming_dir . '/' . $file_name)) {
		//move the file to a its project page using a setuid program

		// force project subdir creation
		exec("/bin/date > /tmp/" . $group_unix_name . "$group_id", $exec_res);
		exec($GLOBALS['codex_bin_prefix'] . "/fileforge /tmp/" . $group_unix_name . "$group_id " . $group_unix_name, $exec_res);
		exec($GLOBALS['codex_bin_prefix'] . "/fileforge $file_name " . $group_unix_name . "/" . $upload_subdir, $exec_res);
		if ($exec_res[0]) {
			echo '<h3>' . $exec_res[0], $exec_res[1] . '</H3><P>';
		}
		//add the file to the database
		$array = array (
			'filename' => $upload_subdir . '/' . $file_name,
			'release_id' => $release_id,
			'file_size' => filesize("$project_files_dir/$upload_subdir/$file_name"
		), 'type_id' => $type_id, 'processor_id' => $processor_id);
		$res =& $frsff->create($array);
		if (!$res) {
			$feedback .= ' | ' . $Language->getText('file_admin_editreleases', 'not_add_file') . ": $file_name ";
			file_utils_footer(array ());
			exit;
		}
		$feedback .= ' | ' . $Language->getText('file_admin_qrs', 'added_success', $file_name);

		// Now send notifications to users monitoring the package, provided
		// that the package is active (not hidden)
		if (!frs_package_is_active($status_id)) {
			$feedback .= '| ' . $Language->getText('file_admin_qrs', 'no_email_sent') . ' ';
			file_utils_footer(array ());
			exit;
		}

		$fmmf = new FileModuleMonitorFactory();
		$result = $fmmf->whoIsMonitoringPackageById($group_id, $package_id);

		if ($result && count($result) > 0) {
			//send the email
			$array_emails = array();
			foreach ($result as $res){
					$array_emails[]=$res['email'];
					$package_name = $res['name'];
			}
			$list = implode($array_emails, ', ');

			$subject = $GLOBALS['sys_name'] . ' ' . $Language->getText('file_admin_editreleases', 'file_rel_notice') . ' ' . $Language->getText('file_admin_editreleases', 'file_rel_notice_project', $group_unix_name);
			list ($host, $port) = explode(':', $GLOBALS['sys_default_domain']);
			$mail = & new Mail();
			$mail->setFrom($GLOBALS['sys_noreply']);
			$mail->setBcc($list);
			$mail->setSubject($subject);
			$mail->setBody($Language->getText('file_admin_editreleases', 'download_explain_modified_file', array (
				$package_name, $file_name)) . " " . $Language->getText('file_admin_editreleases', 'download_explain', array (
			"<" . get_server_url() . "/file/showfiles.php?group_id=$group_id&release_id=$release_id> ", $GLOBALS['sys_name'])) . ": " .
			"\n<" . get_server_url() . "/file/filemodule_monitor.php?filemodule_id=$package_id> ");
			if ($mail->send()) {
				$feedback .= '| ' . $Language->getText('file_admin_qrs', 'email_sent', count($result)) . ' ';
			} else { //ERROR
				$feedback .= '| ' . $GLOBALS['Language']->getText('global', 'mail_failed', array (
					$GLOBALS['sys_email_admin']
				));
			}
		}

		//Now submit news if news option is checked
		if ($_POST['release_submit_news'] == "on" && user_ismember($group_id, 'A')) {
			$new_id = forum_create_forum($GLOBALS['sys_news_group'], $release_news_subject, 1, 0);
			$req = sprintf('INSERT INTO news_bytes' .
			'(group_id,submitted_by,is_approved,date,forum_id,summary,details)' .
			'VALUES (%d, %d, %d, %d, %d, "%s", "%s")', $group_id, user_getid(), 0, time(), $new_id, htmlspecialchars($release_news_subject), htmlspecialchars($release_news_details));
			$result = db_query($req);

			if (!$result) {
				$feedback .= ' ' . $Language->getText('news_submit', 'insert_err') . ' ';
			} else {
				$feedback .= ' ' . $Language->getText('news_submit', 'news_added') . ' ';
				// set permissions on this piece of news
				if ($private_news) {
					news_insert_permissions($new_id, $group_id);
				}
			}
		}

	} else {
		$feedback .= ' | ' . $Language->getText('file_admin_editreleases', 'filename_invalid') . ": $file_name ";
	}

} else {
?>

<script language="javascript">
<!--
function show_textarea() {

if (navigator.userAgent.indexOf('MSIE')<0) {
var subject = document.qrs.release_news_subject;
var details = document.qrs.release_news_details;
var submit = document.qrs.release_submit_news;
var npublic = document.qrs.private_news[0];
var nprivate = document.qrs.private_news[1];
} else {
//MS IE is used
var subject = document.getElementById("release_news_subject");
var details = document.getElementById("release_news_details");
var submit = document.getElementById("release_submit_news");
var npublic = document.getElementById("publicnews");
var nprivate = document.getElementById("privatenews");
}

if (submit.checked) {
//show news submission form
subject.disabled=false;
details.disabled=false;
npublic.disabled=false;
nprivate.disabled=false;
} else {
//hide news submission form
subject.disabled=true;
details.disabled=true;	    
npublic.disabled=true;
nprivate.disabled=true;
}
}

function replace(expr,a,b) {
var i=0
while (i!=-1) {
i=expr.indexOf(a,i);
if (i>=0) {
expr=expr.substring(0,i)+b+expr.substring(i+a.length);	    
i+=b.length;
}
}
return expr
}

function update_news() {

if (navigator.userAgent.indexOf('MSIE')<0) {  
var rel_name = document.qrs.release_name;
var subject = document.qrs.release_news_subject;      
var details = document.qrs.release_news_details;
} else {
var rel_name =  document.getElementById("release_name");
var subject = document.getElementById("release_news_subject");
var details = document.getElementById("release_news_details");
}

var a ="<?php echo $Language->getText('file_admin_editreleases','relname'); ?>";
var b = rel_name.value;
var expr1 = subject.value;
var expr2 = details.value;

new_subject = replace(expr1,a,b);
new_details = replace(expr2,a,b);
subject.value = new_subject;
details.value = new_details;
}
//-->
</script>

<FORM NAME="qrs" ENCTYPE="multipart/form-data" METHOD="POST" ACTION="<?php echo $PHP_SELF; ?>">
<INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="<? echo $sys_max_size_upload; ?>">
<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">
<TR>
<TD>
<B><?php echo $Language->getText('file_admin_editpackages','p_name'); ?>:</B>
</TD>
<TD>
<?php
	$res =& $frspf->getFRSPackagesFromDb($group_id);
	//$sql = "SELECT * FROM frs_package WHERE group_id='$group_id'";
	//$res = db_query($sql);
	$rows = count($res);
	if (!$res || $rows < 1) {
		echo '<p class="highlight">' . $Language->getText('file_admin_qrs', 'no_p_available') . '</p>';
	} else {
		echo '<SELECT NAME="package_id">';
		for ($i = 0; $i < $rows; $i++) {
			echo '<OPTION VALUE="' . $res[$i]->getPackageID(). '">' . $res[$i]->getName() . '</OPTION>';
		}
		echo '</SELECT>';
	}
?>
&nbsp;&nbsp;(<a href="editpackages.php?group_id=<?php echo $group_id; ?>"><?php echo $Language->getText('file_admin_qrs','create_new_p'); ?>)</a>.
</TD>
</TR>
<TR>
<TD>
<B><?php echo $Language->getText('file_admin_editreleases','release_name'); ?>: <span class="highlight"><strong>*</strong></span></B>
</TD>
<TD>
<INPUT TYPE="TEXT" name="release_name" onBlur="update_news()">
</TD>
</TR>
<TR>
<TD>
<B><?php echo $Language->getText('file_admin_editreleases','release_date'); ?>:</B>
</TD>
<TD>
<INPUT TYPE="TEXT" NAME="release_date" VALUE="<?php echo date('Y-m-d'); ?>" SIZE="10" MAXLENGTH="10">
</TD>
</TR>
<TR>
<TD>
<B><?php echo $Language->getText('global','status'); ?>:</B>
</TD>
<TD>
<?php print frs_show_status_popup ($name='status_id') . "<br>"; ?>
</TD>
</TR>
<TR>
<TD>
<B><?php echo $Language->getText('file_admin_qrs','file_name'); ?>:</B>
</TD>
<TD>
<?php

	$dirhandle = @ opendir($ftp_incoming_dir);
	//set variables for news template 
	$url = get_server_url() . "/file/showfiles.php?group_id=" . $group_id;
	$relname = $Language->getText('file_admin_editreleases', 'relname');

	echo '<SELECT NAME="file_name">\n';
	echo '	<OPTION VALUE="qrs_newfile">' . $Language->getText('file_admin_qrs', 'select_file') . '</OPTION>';
	//iterate and show the files in the upload directory
	while ($file = @ readdir($dirhandle)) {
		if ((!ereg('^\.', $file[0])) && is_file($ftp_incoming_dir . '/' . $file)) {
			$atleastone = 1;
			print '<OPTION value="' . $file . '">' . $file . '</OPTION>';
		}
	}
	echo '</SELECT> ' . $Language->getText('file_admin_qrs', 'upload_file') . ': <input type="file" name="userfile"  size="30">
	<br><span class="smaller"><i>' . $Language->getText('file_admin_editreleases', 'max_file_size', formatByteToMb($sys_max_size_upload)) . '</i></span>';
	if (!isset ($atleastone)) {
		print '<h3>' . $Language->getText('file_admin_editreleases', 'no_available_files') . '</H3>
		<P>';
		global $Language;
		include ($Language->getContent('file/qrs_attach_file'));

	}
	echo '<P>
	<INPUT TYPE="SUBMIT" NAME="refresh" VALUE="' . $Language->getText('file_admin_editreleases', 'refresh_file_list') . '">';
?>

</TD>
</TR>
<TR>
<TD>
<B><?php echo $Language->getText('file_admin_editreleases','file_type'); ?></B>
</TD>
<TD>
<?php

	print frs_show_filetype_popup($name = 'type_id') . "<br>";
?>
</TD>
</TR>
<TR>
<TD>
<B><?php echo $Language->getText('file_admin_qrs','processor_type'); ?>:</B>
</TD>
<TD>
<?php

	print frs_show_processor_popup($name = 'processor_id');
?>		
</TD>
</TR>
<TR>
<TD VALIGN="TOP">
<B><?php echo $Language->getText('file_admin_editreleases','release_notes'); ?>:</B>
</TD>
<TD>
<TEXTAREA NAME="release_notes" ROWS="7" COLS="50"></TEXTAREA>
</TD>
</TR>
<TR>
<TD VALIGN="TOP">
<B><?php echo $Language->getText('file_admin_editreleases','change_log'); ?>:</B>
</TD>
<TD>
<TEXTAREA NAME="release_changes" ROWS="7" COLS="50"></TEXTAREA>
</TD>
</TR>
<?php

	if (user_ismember($group_id, 'A')) {
		echo '
		<TR>
		<TD VALIGN="TOP">
		<B> ' . $Language->getText('file_admin_editreleases', 'submit_news') . ' :</B>
		</TD>
		<TD>
		<INPUT TYPE="CHECKBOX" NAME="release_submit_news" onclick="show_textarea()">
		</TD>	
		</TR>
		<TR>
		<TD VALIGN="TOP" ALIGN="RIGHT">
		<B> ' . $Language->getText('file_admin_editreleases', 'subject') . ' :</B>
		</TD>
		<TD>
		<INPUT TYPE="TEXT" ID="release_news_subject" NAME="release_news_subject" VALUE=" ' . $Language->getText('file_admin_editreleases', 'file_news_subject', $relname) . '" SIZE="40" MAXLENGTH="60">
		</TD>
		</TR>	
		<TR>
		<TD VALIGN="TOP" ALIGN="RIGHT">
		<B> ' . $Language->getText('file_admin_editreleases', 'details') . ' :</B>
		</TD>
		<TD>
		<TEXTAREA ID="release_news_details" NAME="release_news_details" ROWS="7" COLS="50">' . $Language->getText('file_admin_editreleases', 'file_news_details', array (
			$relname,
			$url
		)) . ' </TEXTAREA>
		</TD>
		</TR>
		<TR>
		<TD ROWSPAN=2 VALIGN="TOP" ALIGN="RIGHT">
		<B> ' . $Language->getText('news_submit', 'news_privacy') . ' :</B>
		</TD>
		<TD>
		<INPUT TYPE="RADIO" ID="publicnews" NAME="private_news" VALUE="0" CHECKED> ' . $Language->getText('news_submit', 'public_news') . '
		</TD>
		</TR> 
		<TR>
		<TD>
		<INPUT TYPE="RADIO" ID="privatenews" NAME="private_news" VALUE="1">' . $Language->getText('news_submit', 'private_news') . '
		</TD>
		</TR>';
	}
?>

<TR>
<TD COLSPAN="2" ALIGN="CENTER">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="SUBMIT" NAME="submit" VALUE="<?php echo $Language->getText('file_admin_qrs','release_file'); ?>">
</TD>
</TR>
</TABLE>
</FORM>

<script language="javascript">
// show or hide the "submit news" form if needed on page loading
show_textarea();
</script>

<?php

}

file_utils_footer(array ());
?>
