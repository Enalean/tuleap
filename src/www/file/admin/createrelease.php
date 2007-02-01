<?php


//
//SourceForge: Breaking Down the Barriers to Open Source Development
//Copyright 1999-2000 (c) The SourceForge Crew
//http://sourceforge.net
//
//$Id: qrs.php 4555 2006-12-21 11:11:49 +0000 (Thu, 21 Dec 2006) ahardyau $

require_once ('pre.php');
require_once ('www/file/file_utils.php');
require_once ('www/file/admin/frsValidator.class.php');
require_once ('common/include/SimpleSanitizer.class.php');
require_once ('common/include/Feedback.class.php');
require_once ('common/mail/Mail.class.php');
require_once ('www/forum/forum_utils.php');
require_once ('common/frs/FRSPackageFactory.class.php');
require_once ('common/frs/FRSReleaseFactory.class.php');
require_once ('common/frs/FRSFileFactory.class.php');
require_once ('common/frs/FileModuleMonitorFactory.class.php');
require_once ('www/project/admin/permissions.php');
require_once ('common/include/HTTPRequest.class.php');
$Language->loadLanguageMsg('file/file');
$Language->loadLanguageMsg('news/news');

/*
 Quick file release system , Darrell Brogdon, SourceForge, Aug, 2000
 
 With much code horked from editreleases.php
 */

if (!user_ismember($group_id, 'R2')) {
    exit_permission_denied();
}
$GLOBALS['HTML']->includeJavascriptFile("/scripts/prototype/prototype.js");
$GLOBALS['HTML']->includeJavascriptFile("/scripts/scriptaculous/scriptaculous.js");
$GLOBALS['HTML']->includeJavascriptFile("../scripts/frs.js");

$frspf = new FRSPackageFactory();
$frsrf = new FRSReleaseFactory();
$frsff = new FRSFileFactory();

$request = & HTTPRequest :: instance();
$submit = $request->get('create');

if ($submit) {

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

    //get all inputs from $request
    $release = $request->get('release');
    $js = $request->get('js');
    $ftp_file = $request->get('ftp_file') ? $request->get('ftp_file') : array();
    $file_processor = $request->get('file_processor');
    $file_type = $request->get('file_type');
    $ftp_file_processor = $request->get('ftp_file_processor');
    $ftp_file_type = $request->get('ftp_file_type');
    $release_news_subject = $request->get('release_news_subject');
    $release_news_details = $request->get('release_news_details');
    $private_news = $request->get('private_news');
    $ugroups = $request->get('ugroups');
    $release_submit_news = (int) $request->get('release_submit_news');
    $preformatted = (int) $request->get('preformatted');
    $notification = $request->get('notification');

    $validator = new frsValidator();

    if ($validator->isValidForCreation($release, $group_id)) {

        //uplaod release_notes and change_log if needed
        $data_uploaded = false;
        if ($uploaded_change_log) {
            $code = addslashes(fread(fopen($uploaded_change_log, 'r'), filesize($uploaded_change_log)));
            if ((strlen($code) > 0) && (strlen($code) < $sys_max_size_upload)) {
                //size is fine
                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editreleases', 'data_uploaded'));
                $data_uploaded = true;
                $release['change_log'] = $code;
            } else {
                //too big or small
                $GLOBALS['Response']->addFeedback('warning', $Language->getText('file_admin_editreleases', 'length_err', $sys_max_size_upload));
            }
        }
        if ($uploaded_release_notes) {
            $code = addslashes(fread(fopen($uploaded_release_notes, 'r'), filesize($uploaded_release_notes)));
            if ((strlen($code) > 0) && (strlen($code) < $sys_max_size_upload)) {
                //size is fine
                if (!$data_uploaded) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'data_uploaded'));
                }
                $release['release_notes'] = $code;
            } else {
                //too big or small
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('file_admin_editreleases', 'length_err', $sys_max_size_upload));
            }
        }

        //parse the date 
        $date_list = split("-", $release['date'], 3);
        $unix_release_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);

        //now we create the release
        $array = array (
            'release_date' => $unix_release_time,
            'name' => $release['name'],
            'preformatted' => $preformatted,
            'status_id' => $release['status_id'],
            'package_id' => $release['package_id'],
            'notes' => $release['release_notes'],
            'changes' => $release['change_log']
        );
        $res = $frsrf->create($array);
        if (!$res) {
            $GLOBALS['Response']->addFeedback('error', $Language > getText('file_admin_editreleases', 'add_rel_fail'));
            //insert failed - go back to definition screen
        } else {
            //release added - now show the detail page for this new release
            $release_id = $res;
            $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editreleases', 'rel_added'));

            //set the release permissions
            list ($return_code, $feedbacks) = permission_process_selection_form($group_id, 'RELEASE_READ', $release_id, $ugroups);
            if (!$return_code)
                $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editpackages', 'perm_update_err') . ': <p>' . $feedbacks);

            //submit news if requested
            if ($release_id && user_ismember($group_id, 'A') && $release_submit_news) {
                $new_id = forum_create_forum($GLOBALS['sys_news_group'], $release_news_subject, 1, 0);
                $sql = sprintf('INSERT INTO news_bytes' .
                '(group_id,submitted_by,is_approved,date,forum_id,summary,details)' .
                'VALUES (%d, %d, %d, %d, %d, "%s", "%s")', $group_id, user_getid(), 0, time(), $new_id, htmlspecialchars($release_news_subject), htmlspecialchars($release_news_details));
                $result = db_query($sql);

                if (!$result) {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('news_submit', 'insert_err'));
                } else {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('news_submit', 'news_added'));
                    // set permissions on this piece of news
                    if ($private_news) {
                        news_insert_permissions($new_id, $group_id);
                    }
                }
            }

            //send notification
            if ($notification) {
                /*
                    Send a release notification email
                */
                $fmmf = new FileModuleMonitorFactory();
                $result = $fmmf->whoIsMonitoringPackageById($group_id, $release['package_id']);

                if ($result && count($result) > 0) {
                    //send the email
                    $array_emails = array ();
                    foreach ($result as $res) {
                        $array_emails[] = $res['email'];
                        $package_name = $res['name'];
                    }
                    $list = implode($array_emails, ', ');
                    $subject = $GLOBALS['sys_name'] . ' ' . $Language->getText('file_admin_editreleases', 'file_rel_notice') . ' ' . $Language->getText('file_admin_editreleases', 'file_rel_notice_project', group_getunixname($group_id));
                    $package_id = $release['package_id'];
                    list ($host, $port) = explode(':', $GLOBALS['sys_default_domain']);
                    $body = $Language->getText('file_admin_editreleases', 'download_explain_modified_package', $package_name) . " " . $Language->getText('file_admin_editreleases', 'download_explain', array (
                    "<" . get_server_url() . "/file/showfiles.php?group_id=$group_id&release_id=$release_id> ", $GLOBALS['sys_name'])) .
                    "\n<" . get_server_url() . "/file/filemodule_monitor.php?filemodule_id=$package_id> ";

                    $mail = & new Mail();
                    $mail->setFrom($GLOBALS['sys_noreply']);
                    $mail->setBcc($list);
                    $mail->setSubject($subject);
                    $mail->setBody($body);
                    if ($mail->send()) {
                        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editreleases', 'email_sent', count($result)));
                    } else { //ERROR
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array (
                            $GLOBALS['sys_email_admin']
                        )));
                    }
                }
            }

            $group_unix_name = group_getunixname($group_id);
            $project_files_dir = $ftp_frs_dir_prefix . '/' . $group_unix_name;

            //files processing
            $http_files_list = array ();
            $processor_type_list = array ();
            $file_type_list = array ();

            $http_files_processor_type_list = array ();
            $ftp_files_processor_type_list = array ();
            if (isset ($js) && $js == 'no_js') {
                //if javascript is not allowed, there is maximum one file to upload						
                if ($ftp_file[0] != -1) {
                    $ftp_files_processor_type_list[] = array (
                        'name' => $ftp_file[0],
                        'processor' => $file_processor,
                        'type' => $file_type
                    );

                } else
                    if (trim($_FILES['file']['name'][0]) != '') {
                        $http_files_processor_type_list[] = array (
                            'name' => $_FILES['file']['name'][0],
                            'tmp_name' => $_FILES['file']['tmp_name'][0],
                            'processor' => $file_processor,
                            'type' => $file_type
                        );
                    }
            } else {
                //get http files with the associated processor type and file type in allowed javascript case
                $nb_files = isset($_FILES['file']) ? count($_FILES['file']['name']) : 0;
                for ($i = 0; $i < $nb_files; $i++) {
                    if (trim($_FILES['file']['name'][$i]) != '') {
                        $http_files_processor_type_list[] = array (
                            'name' => $_FILES['file']['name'][$i],
                            'tmp_name' => $_FILES['file']['tmp_name'][$i],
                            'processor' => $file_processor[$i],
                            'type' => $file_type[$i]
                        );
                    }
                }
                //remove hidden ftp_file input (if the user let the select boxe on --choose file)
                $tmp_file_list = array ();
                $index = 0;
                foreach ($ftp_file as $file) {
                    if (trim($file) != '') {
                        $ftp_files_processor_type_list[] = array (
                            'name' => $file,
                            'processor' => $ftp_file_processor[$index],
                            'type' => $ftp_file_type[$index]
                        );
                        $index++;
                    }
                }
            }

            if (count($http_files_processor_type_list) > 0 || count($ftp_files_processor_type_list) > 0) {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_admin_editreleases', 'add_files'));
                //see if this release belongs to this project
                $res1 = & $frsrf->getFRSReleaseFromDb($release_id, $group_id);
                if (!$res1 || count($res1) < 1) {
                    //release not found for this project
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'rel_not_yours'));
                } else {
                    $now = time();

                    //iterate and add the http files to the frs_file table
                    foreach ($http_files_processor_type_list as $file) {

                        //see if filename is legal before adding it
                        $filename = $file['name'];
                        if (!util_is_valid_filename($filename)) {
                            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'illegal_file_name') . ": $filename");
                        } else {
                            if (is_uploaded_file($file['tmp_name'])) {
                                $uploaddir = $GLOBALS['ftp_incoming_dir'];
                                $uploadfile = $uploaddir . "/" . basename($filename);
                                if (!move_uploaded_file($file['tmp_name'], $uploadfile)) {
                                    $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'not_add_file') . ": " . basename($filename));
                                } else {
                                    // get the package id and compute the upload directory
                                    $pres = & $frsrf->getFRSReleaseFromDb($release_id, $group_id, $package_id);

                                    if (!$pres || count($pres) < 1) {
                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'p_rel_not_yours'));
                                    }
                                    //see if they already have a file by this name
                                    $res1 = $frsff->isFileBaseNameExists($filename, $release_id, $group_id);
                                    if (!$res1) {

                                        /*
                                        	move the file to the project's fileserver directory
                                        */
                                        clearstatcache();
                                        if (is_file($ftp_incoming_dir . '/' . $filename) && file_exists($ftp_incoming_dir . '/' . $filename)) {
                                            //move the file to a its project page using a setuid program
                                            $exec_res = $frsff->moveFileForge($group_id, $filename, $frsff->getUploadSubDirectory($release_id));
                                            if ($exec_res[0]) {
                                                echo '<h3>' . $exec_res[0], $exec_res[1] . '</H3><P>';
                                            }
                                            //add the file to the database
                                            $array = array (
                                                'filename' => $frsff->getUploadSubDirectory($release_id
                                            ) . '/' . $filename, 'release_id' => $release_id, 'file_size' => filesize($project_files_dir . '/' . $frsff->getUploadSubDirectory($release_id) . '/' . $filename), 'processor_id' => $file['processor'] == 100 ? 0 : $file['processor'], 'type_id' => $file['type'] == 100 ? 0 : $file['type']);
                                            $res = & $frsff->create($array);

                                            if (!$res) {
                                                $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'not_add_file') . ": $filename ");
                                                echo db_error();
                                            }
                                        } else {
                                            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'filename_invalid') . ": $filename");
                                        }
                                    } else {
                                        echo 'in feedback';
                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'filename_exists') . ": $filename");
                                    }
                                }
                            }
                        }
                    }

                    //iterate and add the ftp files to the frs_file table

                    //use to fill the form after submission 
                    foreach ($ftp_files_processor_type_list as $file) {
                        $filename = $file['name'];
                        //see if filename is legal before adding it
                        if (!util_is_valid_filename($filename)) {
                            $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'illegal_file_name') . ": $filename");
                        } else {
                            // get the package id and compute the upload directory
                            $pres = & $frsrf->getFRSReleaseFromDb($release_id, $group_id, $release['package_id']);

                            if (!$pres || count($pres) < 1) {
                                $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'p_rel_not_yours'));
                            }
                            //see if they already have a file by this name
                            $res1 = $frsff->isFileBaseNameExists($filename, $release_id, $group_id);
                            if (!$res1) {

                                /*
                                	move the file to the project's fileserver directory
                                */
                                clearstatcache();
                                if (is_file($ftp_incoming_dir . '/' . $filename) && file_exists($ftp_incoming_dir . '/' . $filename)) {
                                    //move the file to a its project page using a setuid program
                                    $exec_res = $frsff->moveFileForge($group_id, $filename, $frsff->getUploadSubDirectory($release_id));
                                    if ($exec_res[0]) {
                                        echo '<h3>' . $exec_res[0], $exec_res[1] . '</H3><P>';
                                    }
                                    //add the file to the database
                                    $array = array (
                                        'filename' => $frsff->getUploadSubDirectory($release_id
                                    ) . '/' . $filename, 'release_id' => $release_id, 'file_size' => filesize($project_files_dir . '/' . $frsff->getUploadSubDirectory($release_id) . '/' . $filename), 'processor_id' => $file['processor'] == 100 ? 0 : $file['processor'], 'type_id' => $file['type'] == 100 ? 0 : $file['type']);
                                    $res = & $frsff->create($array);

                                    if (!$res) {
                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'not_add_file') . ": $filename ");
                                        echo db_error();
                                    }
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'filename_invalid') . ": $filename");
                                }
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $Language->getText('file_admin_editreleases', 'filename_exists') . ": $filename");
                            }
                        }
                    }
                }
            }
            //redirect to update release page
            $GLOBALS['Response']->redirect('editpackages.php?group_id=' . $group_id );
        }

    }
}

//if (isset ($release_id) && $release_id) {
//	header('Location: frsMockup.php?group_id=' . $group_id . '&&release_id=' . $release_id);
//} else {
file_utils_admin_header(array (
    'title' => $Language->getText('file_admin_editreleases',
    'release_new_file_version'
), 'help' => 'QuickFileRelease.html'));

$sql = "SELECT * FROM frs_processor ORDER BY processor_id";
$result = db_query($sql);
$processor_id = util_result_column_to_array($result, 0);
$processor_name = util_result_column_to_array($result, 1);
$sql = "SELECT * FROM frs_filetype ORDER BY type_id";
$result1 = db_query($sql);
$type_id = util_result_column_to_array($result1, 0);
$type_name = util_result_column_to_array($result1, 1);
echo '<script type="text/javascript">';
echo "var processor_id = ['" . implode("', '", $processor_id) . "'];";
echo "var processor_name = ['" . implode("', '", $processor_name) . "'];";
echo "var type_id = ['" . implode("', '", $type_id) . "'];";
echo "var type_name = ['" . implode("', '", $type_name) . "'];";
echo "var group_id = " . $group_id . ";";
echo "var relname = '" . $Language->getText('file_admin_editreleases', 'relname') . "';";
echo "var choose = '" . $Language->getText('file_file_utils', 'must_choose_one') . "';";
echo "var browse = '" . $Language->getText('file_admin_editreleases', 'browse') . "';";
echo "var local_file = '" . $Language->getText('file_admin_editreleases', 'local_file') . "';";
echo "var scp_ftp_files = '" . $Language->getText('file_admin_editreleases', 'scp_ftp_files') . "';";
echo "var upload_text = '" . $Language->getText('file_admin_editreleases', 'upload') . "';";
echo "var add_file_text = '" . $Language->getText('file_admin_editreleases', 'add_file') . "';";
echo "var add_change_log_text = '" . $Language->getText('file_admin_editreleases', 'add_change_log') . "';";
echo "var view_change_text = '" . $Language->getText('file_admin_editreleases', 'view_change') . "';";
echo "var default_permissions_text = '" . $Language->getText('file_admin_editreleases', 'default_permissions') . "';";
echo "var release_mode = 'creation'";

echo '</script>';
$dirhandle = @ opendir($ftp_incoming_dir);
//set variables for news template 
$url = get_server_url() . "/file/showfiles.php?group_id=" . $group_id;
$relname = $Language->getText('file_admin_editreleases', 'relname');
?>

<FORM id="frs_form" NAME="frsMockup" ENCTYPE="multipart/form-data" METHOD="POST" ACTION="<?php echo $PHP_SELF."?group_id=".$group_id; ?>">
	<INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="<? echo $sys_max_size_upload; ?>">
	<TABLE BORDER="0" width="100%">
	<TR><TD><FIELDSET><LEGEND><?php echo $Language->getText('file_admin_editreleases','fieldset_properties'); ?></LEGEND>
	<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">
		<TR>
			<TD>
				<B><?php echo $Language->getText('file_admin_editpackages','p_name'); ?>:</B>
			</TD>
			<TD>
				<?php


$res = & $frspf->getFRSPackagesFromDb($group_id);
//$sql = "SELECT * FROM frs_package WHERE group_id='$group_id'";
//$res = db_query($sql);
$rows = count($res);
if (!$res || $rows < 1) {
    echo '<p class="highlight">' . $Language->getText('file_admin_qrs', 'no_p_available') . '</p>';
} else {
    echo '<SELECT NAME="release[package_id]" id="package_id">';
    for ($i = 0; $i < $rows; $i++) {
        echo '<OPTION VALUE="' . $res[$i]->getPackageID() . '"';
        if($res[$i]->getPackageID()==$package_id) echo ' selected';
        echo '>' . $res[$i]->getName() . '</OPTION>';
    }
    echo '</SELECT>';
}
?>
				&nbsp;&nbsp;(<a href="editpackages.php?group_id=<?php echo $group_id; ?>"><?php echo $Language->getText('file_admin_qrs','create_new_p'); ?>)</a>.
			</TD><td></td>
			<TD>
				<B><?php echo $Language->getText('file_admin_editreleases','release_name'); ?>: <span class="highlight"><strong>*</strong></span></B>
			</TD>
			<TD>
				<INPUT TYPE="TEXT" id="release_name" name="release[name]" onBlur="update_news()">
			</TD>
		</TR>
		<TR>
			<TD>
				<B><?php echo $Language->getText('file_admin_editreleases','release_date'); ?>:</B>
			</TD>
			<TD>
				<INPUT TYPE="TEXT" id="release_date" NAME="release[date]" VALUE="<?php echo date('Y-m-d')?>" SIZE="10" MAXLENGTH="10">
			</TD><td></td>
			<TD>
				<B><?php echo $Language->getText('global','status'); ?>:</B>
			</TD>
			<TD>
				<?php


print frs_show_status_popup($name = 'release[status_id]') . "<br>";
?>
			</TD>
		</TR></TABLE></FIELDSET>
	</TD></TR>
	<TR><TD><FIELDSET><LEGEND><?php echo $Language->getText('file_admin_editreleases','fieldset_uploaded_files'); ?></LEGEND>
		<?php


$titles = array ();
$titles[] = '';
$titles[] = $Language->getText('file_admin_editreleases', 'filename');
$titles[] = $Language->getText('file_admin_editreleases', 'processor');
$titles[] = $Language->getText('file_admin_editreleases', 'file_type');

echo html_build_list_table_top($titles, false, false, false, 'files');
?>
    	<tbody id="files_body">

					<tr id="row_0">
						<td></td>
						<td>
							<input type="hidden" name="js" value="no_js"/>
							<select name="ftp_file[]" id="ftp_file_0">
								<option value="-1"><?php echo $Language->getText('file_file_utils','must_choose_one'); ?></option>
								<?php


//iterate and show the files in the upload directory
$file_list = $frsff->getUploadedFileNames();
foreach ($file_list as $incoming_file) {
    echo '<option value="' . $incoming_file . '">' . $incoming_file . '</option>';
}
echo '<script type="text/javascript">';
echo "var available_ftp_files = ['" . implode("', '", $file_list) . "'];";
echo '</script>';

?>
							</select>

							<span id="or">or</span>
							<input type="file" name="file[]" id="file_0" />
						</td>
						<td>
							<?php print frs_show_processor_popup($name = 'file_processor'); ?>
						</td>
						<td>
							<?php print frs_show_filetype_popup($name = 'file_type'); ?>
						</td>
					</tr>
				</tbody>
			</table>
    		<?php


echo '<div id=\'files_help\'><span class="smaller"><i>';
global $Language;
include ($Language->getContent('file/qrs_attach_file'));
echo '</i></span></div>';
?>
		</FIELDSET>
		</TD></TR>
		<TR><TD><FIELDSET><LEGEND><?php echo $Language->getText('file_admin_editreleases','fieldset_notes'); ?></LEGEND>
		<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2" WIDTH="100%">
		<TR>
			<TD VALIGN="TOP" width="10%">
				<span id="release_notes"><B><?php echo $Language->getText('file_admin_editreleases','release_notes'); ?>:  </B></span>
			</TD>
		</TR>
		<TR id="upload_release_notes">
			<TD>
				<input type="file" name="uploaded_release_notes"  size="30">
			</TD>
		</TR>
		<TR>
			<TD width="100%">
				<TEXTAREA NAME="release[release_notes]" rows="7" cols="70"></TEXTAREA>
			</TD>
		</TR>
		<TR id="change_log_title">
			<TD VALIGN="TOP" width="10%">
				<span id="change_log"><B><?php echo $Language->getText('file_admin_editreleases','change_log'); ?>:  </B></span>
			</TD>
		</TR>
		<TR id="upload_change_log">
			<TD>
				<input type="file" name="uploaded_change_log"  size="30">
			</TD>
		</TR>
		<TR id="change_log_area">
			<TD width="40%">
				<TEXTAREA NAME="release[change_log]" ROWS="7" COLS="70"></TEXTAREA>
			</TD>
		</TR>
		<TR>
			<TD>
				<?php


echo '<INPUT TYPE="CHECKBOX" NAME="preformatted" VALUE="1"> ' .
$Language->getText('file_admin_editreleases', 'preserve_preformatted');
?>
			</TD>
		</TR>
		</TABLE></FIELDSET>
		</TD></TR>
		<TR>
			<TD>
				<FIELDSET><LEGEND><?php echo $Language->getText('file_admin_editreleases','fieldset_permissions'); ?></LEGEND>
					<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">

						<TR id="permissions">
							<TD>
								<DIV id="permissions_list">
									<?php permission_display_selection_frs("PACKAGE_READ", $package_id, $group_id); ?>
								</DIV>
							</TD>
						</TR>
					</TABLE>
				</FIELDSET>
			</TD>
		</TR> 
		<?php


if (user_ismember($group_id, 'A')) {
    echo '
        												<TR><TD><FIELDSET><LEGEND>' . $Language->getText('file_admin_editreleases', 'fieldset_news') . '</LEGEND>
        													<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">
        														<TR>
        															<TD VALIGN="TOP">
        																<B> ' . $Language->getText('file_admin_editreleases', 'submit_news') . ' :</B>
        															</TD>
        															<TD>
        																<INPUT ID="submit_news" TYPE="CHECKBOX" NAME="release_submit_news" VALUE="1">
        																
        															</TD>	
        														</TR>
        														<TR id="tr_subject">
        															<TD VALIGN="TOP" ALIGN="RIGHT">
        																<B> ' . $Language->getText('file_admin_editreleases', 'subject') . ' :</B>
        															</TD>
        															<TD>
        																<INPUT TYPE="TEXT" ID="release_news_subject" NAME="release_news_subject" VALUE=" ' . $Language->getText('file_admin_editreleases', 'file_news_subject', $relname) . '" SIZE="40" MAXLENGTH="60">
        															</TD>
        														</TR>	
        														<TR id="tr_details">
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
        														<TR id="tr_public">
        															<TD ROWSPAN=2 VALIGN="TOP" ALIGN="RIGHT">
        																<B> ' . $Language->getText('news_submit', 'news_privacy') . ' :</B>
        															</TD>
        															<TD>
        																<INPUT TYPE="RADIO" ID="publicnews" NAME="private_news" VALUE="0" CHECKED> ' . $Language->getText('news_submit', 'public_news') . '
        															</TD>
        														</TR > 
        														<TR id="tr_private">
        															<TD>
        																<INPUT TYPE="RADIO" ID="privatenews" NAME="private_news" VALUE="1">' . $Language->getText('news_submit', 'private_news') . '
        															</TD>
        														</TR></DIV>
        													</TABLE></FIELDSET>
        												</TD></TR>';
}

    echo '<TR><TD><FIELDSET><LEGEND>' . $Language->getText('file_admin_editreleases', 'fieldset_notification') . '</LEGEND>';
    echo '<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">';
    echo '<TR><TD><B>' . $Language->getText('file_admin_editreleases', 'mail_file_rel_notice') . '</B><INPUT TYPE="CHECKBOX" NAME="notification" VALUE="1">';
    echo '</TD></TR>';
    echo '</TABLE></FIELDSET></TD></TR>';

?>
		
		<TR>
			<TD ALIGN="CENTER">
				
				<INPUT TYPE="HIDDEN" NAME="create" VALUE="bla">
				<INPUT TYPE="SUBMIT" ID="create_release"  VALUE="<?php echo $Language->getText('file_admin_qrs','release_file'); ?>" >
			</TD>
		</TR>
	</TABLE>
</FORM>



<?php


//}

file_utils_footer(array ());
//}
?>

