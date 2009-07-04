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
  * Constants
  */
 require_once('include/defs.constants.php');

 /*
  * Configuration
  */
 require_once('config/gitphp.conf.php');

 /*
  * Instantiate Smarty
  */
 require_once($gitphp_conf['smarty_prefix'] . "Smarty.class.php");
 $tpl =& new Smarty;
 if (isset($_GET['a']) &&
     ($_GET['a'] != "commitdiff_plain") &&
     ($_GET['a'] != "blob_plain") &&
     ($_GET['a'] != "blobdiff_plain")) {
	$tpl->load_filter('output','trimwhitespace');
}


 $rss_link = FALSE;
 $suppress_headers = FALSE;

 ob_start();
 if (isset($_GET['a']) && $_GET['a'] == "opml") {
	$suppress_headers = TRUE;
	require_once('include/display.git_opml.php');
	git_opml($gitphp_conf['projectroot'],$git_projects);
 } else if (isset($_GET['a']) && $_GET['a'] == "project_index") {
	$suppress_headers = TRUE;
	require_once('include/display.git_project_index.php');
	git_project_index($gitphp_conf['projectroot'],$git_projects);
 } else if (isset($_GET['p'])) {
 	if (!is_dir($gitphp_conf['projectroot'] . $_GET['p']))
		echo "No such directory";
	else if (!is_file($gitphp_conf['projectroot'] . $_GET['p'] . "/HEAD"))
		echo "No such project";
	else {
		$rss_link = TRUE;
		if (!isset($_GET['a'])) {
			require_once('include/display.git_summary.php');
			git_summary($gitphp_conf['projectroot'],$_GET['p']);
		} else {
			switch ($_GET['a']) {
				case "summary":
					require_once('include/display.git_summary.php');
					git_summary($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "tree":
					require_once('include/display.git_tree.php');
					git_tree($gitphp_conf['projectroot'], $_GET['p'], (isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['f']) ? $_GET['f'] : NULL), (isset($_GET['hb']) ? $_GET['hb'] : NULL));
					break;
				case "shortlog":
					require_once('include/display.git_shortlog.php');
					git_shortlog($gitphp_conf['projectroot'],$_GET['p'],(isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['pg']) ? $_GET['pg'] : NULL));
					break;
				case "log":
					require_once('include/display.git_log.php');
					git_log($gitphp_conf['projectroot'],$_GET['p'], (isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['pg']) ? $_GET['pg'] : NULL));
					break;
				case "commit":
					require_once('include/display.git_commit.php');
					git_commit($gitphp_conf['projectroot'],$_GET['p'],$_GET['h']);
					break;
				case "commitdiff":
					require_once('include/display.git_commitdiff.php');
					git_commitdiff($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'], (isset($_GET['hp']) ? $_GET['hp'] : NULL));
					break;
				case "commitdiff_plain":
					$suppress_headers = TRUE;
					require_once('include/display.git_commitdiff_plain.php');
					git_commitdiff_plain($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],(isset($_GET['hp']) ? $_GET['hp'] : NULL));
					break;
				case "heads":
					require_once('include/display.git_heads.php');
					git_heads($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "tags":
					require_once('include/display.git_tags.php');
					git_tags($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "rss":
					$suppress_headers = TRUE;
					require_once('include/display.git_rss.php');
					git_rss($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "blob":
					require_once('include/display.git_blob.php');
					git_blob($gitphp_conf['projectroot'],$_GET['p'], (isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['f']) ? $_GET['f'] : NULL), (isset($_GET['hb']) ? $_GET['hb'] : NULL));
					break;
				case "blob_plain":
					$suppress_headers = TRUE;
					require_once('include/display.git_blob_plain.php');
					git_blob_plain($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],(isset($_GET['f']) ? $_GET['f'] : NULL));
					break;
				case "blobdiff":
					require_once('include/display.git_blobdiff.php');
					git_blobdiff($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hb'],$_GET['hp'],(isset($_GET['f']) ? $_GET['f'] : NULL));
					break;
				case "blobdiff_plain":
					$suppress_headers = TRUE;
					require_once('include/display.git_blobdiff_plain.php');
					git_blobdiff_plain($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hb'],$_GET['hp'], (isset($_GET['f']) ? $_GET['f'] : NULL));
					break;
				case "snapshot":
					$suppress_headers = TRUE;
					require_once('include/display.git_snapshot.php');
					git_snapshot($gitphp_conf['projectroot'],$_GET['p'], (isset($_GET['h']) ? $_GET['h'] : NULL));
					break;
				case "history":
					require_once('include/display.git_history.php');
					git_history($gitphp_conf['projectroot'],$_GET['p'], (isset($_GET['h']) ? $_GET['h'] : NULL),$_GET['f']);
					break;
				case "search":
					if (isset($_GET['st']) && ($_GET['st'] == 'file')) {
						require_once('include/display.git_search_files.php');
						git_search_files($gitphp_conf['projectroot'],$_GET['p'],(isset($_GET['h']) ? $_GET['h'] : NULL),(isset($_GET['s']) ? $_GET['s'] : NULL),(isset($_GET['pg']) ? $_GET['pg'] : 0));
					} else {
						require_once('include/display.git_search.php');
						git_search($gitphp_conf['projectroot'],$_GET['p'],(isset($_GET['h']) ? $_GET['h'] : NULL),(isset($_GET['s']) ? $_GET['s'] : NULL),(isset($_GET['st']) ? $_GET['st'] : "commit"),(isset($_GET['pg']) ? $_GET['pg'] : 0));
					}
					break;
				case "tag":
					require_once('include/display.git_tag.php');
					git_tag($gitphp_conf['projectroot'],$_GET['p'],$_GET['h']);
					break;
				default:
					echo "Unknown action";
					break;
			}
		}
	}
 } else {
 	$tpl->display("hometext.tpl");
	require_once('include/display.git_project_list.php');
 	git_project_list($gitphp_conf['projectroot'],$git_projects,(isset($_GET['o']) ? $_GET['o'] : "project"));
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
	 if (isset($_GET['st']))
	 	$tpl->assign("searchtype",$_GET['st']);
	else
		$tpl->assign("searchtype","commit");
	if (isset($_GET['s']))
		$tpl->assign("search",$_GET['s']);
	if (isset($_GET['hb']))
		$tpl->assign("hash",$_GET['hb']);
	else if (isset($_GET['h']))
		$tpl->assign("hash",$_GET['h']);
	if ($gitphp_conf['search'])
		$tpl->assign("enablesearch",TRUE);
	if ($gitphp_conf['filesearch'])
		$tpl->assign("filesearch",TRUE);
	 $tpl->display("header.tpl");
 }

 echo $main;

 if (!$suppress_headers) {
	 if ($rss_link) {
		$tpl->assign("project",$_GET['p']);
		require_once('include/gitutil.git_project_descr.php');
		$tpl->assign("descr",git_project_descr($gitphp_conf['projectroot'],$_GET['p']));
	 }
	 $tpl->display("footer.tpl");
 }

 ob_end_flush();

?>
