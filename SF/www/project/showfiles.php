<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');

// LJ Now only for registered users on CodeX
if (user_isloggedin()) {

    $sql = "SELECT * FROM frs_package WHERE group_id='$group_id' AND status_id='1'";
    $res_package = db_query( $sql );
    $num_packages = db_numrows( $res_package );

    if ( $num_packages < 1) {
	exit_error("No File Packages","There are no file packages defined for this project.");
    }

    site_project_header(array('title'=>'Project Filelist','group'=>$group_id,'toptab'=>'downloads'));

    echo '<h3>Package Releases '. help_button('FileReleaseJargon.html').'</h3>';
    echo '<p>Select Release to see Release Notes and Change Log, &nbsp;';
    echo 'Select File to request file download.</p>';
    
    if (session_issecure()) {
	$url = "https://".$GLOBALS['sys_https_host'];
    } else {
	$url = "http://".$GLOBALS['sys_default_domain'];
    }
?>
<SCRIPT language="JavaScript">
<!--
function showConfirmDownload(group_id,file_id,filename) {
    url = "<?php echo $url; ?>/project/confirm_download.php?group_id=" + group_id + "&file_id=" + file_id + "&filename=" + filename;
    wConfirm = window.open(url,"confirm","width=450,height=360,resizable=1,scrollbars=1");
    wConfirm.focus();
}

function download(group_id,file_id,filename) {
    url = "<?php echo $url; ?>/project/download.php/" + group_id + "/" + file_id +"/"+filename;
    wConfirm.close();
    self.location = url;
    
}
-->
</SCRIPT>
<?
$title_arr = array();
$title_arr[] = 'Package';
$title_arr[] = 'Release<BR>&amp; Notes';
$title_arr[] = 'Filename';
$title_arr[] = 'Size';
$title_arr[] = 'D/L';
$title_arr[] = 'Arch.';
$title_arr[] = 'Type';
$title_arr[] = 'Date';

   // get unix group name for path
$group_unix_name=group_getunixname($group_id);

   // print the header row
echo html_build_list_table_top($title_arr) . "\n";

$proj_stats['packages'] = $num_packages;

   // Iterate and show the packages
for ( $p = 0; $p < $num_packages; $p++ ) {

	print '<TR><TD><B>'.db_result($res_package,$p,'name').'</B></TD><TD COLSPAN="7">&nbsp;</TD></TR>'."\n";

	// get the releases of the package
	// Order by release_date and release_id in case two releases
	// are published the same day
	$sql	= "SELECT * FROM frs_release WHERE package_id='". db_result($res_package,$p,'package_id') . "' "
		. "AND status_id=1 ORDER BY release_date DESC, release_id DESC";
	$res_release = db_query( $sql );
	$num_releases = db_numrows( $res_release );

	$proj_stats['releases'] += $num_releases;

	if ( !$res_release || $num_releases < 1 ) {
		print '<TR><TD>&nbsp;</TD><TD><B>No Releases</B></TD><TD COLSPAN="6">&nbsp;</TD></TR>'."\n";
	} else {
		   // iterate and show the releases of the package
		for ( $r = 0; $r < $num_releases; $r++ ) {
			$package_release = db_fetch_array( $res_release );

			   // Highlight the release if one was chosen
			if ( $release_id == $package_release['release_id'] ) {
				$bgcolor = 'boxitemalt';
			} else {
				$bgcolor = 'boxitem';
			}

			print "\t" . '<TR class="'. $bgcolor .'"><TD>&nbsp;</TD><TD><B>'
				. '<A HREF="shownotes.php?release_id='.$package_release['release_id'].'">'
				. $package_release['name'] .'</A></B></TD><TD COLSPAN="5">&nbsp;</TD><TD>'
				. format_date( $sys_datefmt, $package_release['release_date'] ) .'</TD></TR>'."\n";

			   // get the files in this release....
			$sql = "SELECT frs_file.file_id AS file_id,"
				. "frs_file.filename AS filename,"
				. "frs_file.file_size AS file_size,"
				. "frs_file.release_time AS release_time,"
				. "frs_filetype.name AS type,"
				. "frs_processor.name AS processor,"
				. "frs_dlstats_filetotal_agg.downloads AS downloads "
				. "FROM frs_filetype,frs_processor,"
				. "frs_file LEFT JOIN frs_dlstats_filetotal_agg ON frs_dlstats_filetotal_agg.file_id=frs_file.file_id "
				. "WHERE release_id='". $package_release['release_id'] ."' "
				. "AND frs_filetype.type_id=frs_file.type_id "
				. "AND frs_processor.processor_id=frs_file.processor_id ";
			$res_file = db_query( $sql );
			$num_files = db_numrows( $res_file );

			$proj_stats['files'] += $num_files;

			if ( !$res_file || $num_files < 1 ) {
				print '<TR><TD COLSPAN=2>&nbsp;</TD><TD><B>No Files</B></TD><TD COLSPAN="5">&nbsp;</TD></TR>'."\n";
			} else {
				   // now iterate and show the files in this release....
				for ( $f = 0; $f < $num_files; $f++ ) {
					$file_release = db_fetch_array( $res_file );
					$filename = $file_release['filename'];$list = split('/', $filename);
					$fname = $list[sizeof($list) - 1];
					print "\t\t" . '<TR class="' . $bgcolor .'">'
						. '<TD COLSPAN=2>&nbsp;</TD>'
						. '<TD><B><A HREF="javascript:showConfirmDownload('.$group_id.','.$file_release['file_id'].',\''.$file_release['filename'].'\')">'

						. $fname .'</A></B></TD>'
						. '<TD>'. $file_release['file_size'] .'</TD>'
						. '<TD>'. ($file_release['downloads'] ? $file_release['downloads'] : '0') .'</TD>'
						. '<TD>'. $file_release['processor'] .'</TD>'
						. '<TD>'. $file_release['type'] .'</TD>'
						. '<TD>'. format_date( $sys_datefmt, $file_release['release_time'] ) .'</TD>'
						. '</TR>' . "\n";

					$proj_stats['size'] += $file_release['file_size'];
					$proj_stats['downloads'] += $file_release['downloads'];
				}	
			}
		}
	}

}

if ( $proj_stats['size'] ) {
	print '<TR><TD COLSPAN="8">&nbsp;</TR>'."\n";
	print '<TR><TD><B>Project Totals: </B></TD>'
		. '<TD><B><I>' . $proj_stats['releases'] . '</I></B></TD>'
		. '<TD><B><I>' . $proj_stats['files'] . '</I></B></TD>'
		. '<TD><B><I>' . $proj_stats['size'] . '</I></B></TD>'
		. '<TD><B><I>' . $proj_stats['downloads'] . '</I></B></TD>'
		. '<TD COLSPAN="3">&nbsp;</TD></TR>'."\n";
}

print "</TABLE>\n\n";

site_project_footer(array());

} else {

 /*
    Not logged in
  */
  exit_not_logged_in();
}

?>
