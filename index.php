<?php
/*
 *  index.php
 *  gitphp: A PHP git repository browser
 *  Component: Index script
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 */
 ob_start();

 /*
  * Version
  */
 include_once('include/version.php');

 /*
  * Configuration
  */
 include_once('config/gitphp.conf.php');

 /*
  * Instantiate Smarty
  */
 include_once($gitphp_conf['smarty_prefix'] . "Smarty.class.php");
 $tpl =& new Smarty;
 $tpl->load_filter('output','trimwhitespace');

 $rss_link = FALSE;
 $suppress_headers = FALSE;

 ob_start();
 if (isset($_GET['a']) && $_GET['a'] == "opml") {
	$suppress_headers = TRUE;
	include_once('include/display.git_opml.php');
	git_opml($gitphp_conf['projectroot'],$git_projects);
 } else if (isset($_GET['a']) && $_GET['a'] == "project_index") {
	$suppress_headers = TRUE;
	include_once('include/display.git_project_index.php');
	git_project_index($gitphp_conf['projectroot'],$git_projects);
 } else if (isset($_GET['p'])) {
 	if (!is_dir($gitphp_conf['projectroot'] . $_GET['p']))
		echo "No such directory";
	else if (!is_file($gitphp_conf['projectroot'] . $_GET['p'] . "/HEAD"))
		echo "No such project";
	else {
		$rss_link = TRUE;
		if (!isset($_GET['a'])) {
			include_once('include/display.git_summary.php');
			git_summary($gitphp_conf['projectroot'],$_GET['p']);
		} else {
			switch ($_GET['a']) {
				case "summary":
					include_once('include/display.git_summary.php');
					git_summary($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "tree":
					include_once('include/display.git_tree.php');
					git_tree($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['f'],$_GET['hb']);
					break;
				case "shortlog":
					include_once('include/display.git_shortlog.php');
					git_shortlog($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['pg']);
					break;
				case "log":
					include_once('include/display.git_log.php');
					git_log($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['pg']);
					break;
				case "commit":
					include_once('include/display.git_commit.php');
					git_commit($gitphp_conf['projectroot'],$_GET['p'],$_GET['h']);
					break;
				case "commitdiff":
					include_once('include/display.git_commitdiff.php');
					git_commitdiff($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hp']);
					break;
				case "commitdiff_plain":
					$suppress_headers = TRUE;
					include_once('include/display.git_commitdiff_plain.php');
					git_commitdiff_plain($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hp']);
					break;
				case "heads":
					include_once('include/display.git_heads.php');
					git_heads($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "tags":
					include_once('include/display.git_tags.php');
					git_tags($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "rss":
					$suppress_headers = TRUE;
					include_once('include/display.git_rss.php');
					git_rss($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "blob":
					include_once('include/display.git_blob.php');
					git_blob($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['f'],$_GET['hb']);
					break;
				case "blob_plain":
					$suppress_headers = TRUE;
					include_once('include/display.git_blob_plain.php');
					git_blob_plain($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['f']);
					break;
				case "blobdiff":
					include_once('include/display.git_blobdiff.php');
					git_blobdiff($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hb'],$_GET['hp'],$_GET['f']);
					break;
				case "blobdiff_plain":
					$suppress_headers = TRUE;
					include_once('include/display.git_blobdiff_plain.php');
					git_blobdiff_plain($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hb'],$_GET['hp'],$_GET['f']);
					break;
				case "snapshot":
					$suppress_headers = TRUE;
					include_once('include/display.git_snapshot.php');
					git_snapshot($gitphp_conf['projectroot'],$_GET['p'],$_GET['h']);
					break;
				case "history":
					include_once('include/display.git_history.php');
					git_history($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['f']);
					break;
				default:
					echo "Unknown action";
					break;
			}
		}
	}
 } else {
 	$tpl->display("hometext.tpl");
	include_once('include/display.git_project_list.php');
 	git_project_list($gitphp_conf['projectroot'],$git_projects,$_GET['o']);
 }
 $main = ob_get_contents();
 ob_end_clean();

 if (!$suppress_headers) {
	 $tpl->clear_all_assign();
	 $tpl->assign("stylesheet",$gitphp_conf['stylesheet']);
	 $tpl->assign("version",$gitphp_version);
	 $title = $gitphp_conf['title'];
	 if ($rss_link) {
		$tpl->assign("rss_link",TRUE);
		$tpl->assign("project",$_GET['p']);
		$title .= " :: " . $_GET['p'];
		if (isset($_GET['a'])) {
			$tpl->assign("action",$_GET['a']);
			$title .= "/" . $_GET['a'];
		}
	 }
	 $tpl->assign("title",$title);
	 $tpl->display("header.tpl");
 }

 echo $main;

 if (!$suppress_headers) {
	 if ($rss_link) {
		$tpl->assign("project",$_GET['p']);
		include_once('include/gitutil.git_project_descr.php');
		$tpl->assign("descr",git_project_descr($gitphp_conf['projectroot'],$_GET['p']));
	 }
	 $tpl->display("footer.tpl");
 }

 ob_end_flush();

?>
