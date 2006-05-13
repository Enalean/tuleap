<?php
 ob_start();
 $version = "v01c";
 $gitphp_appstring = "gitphp $version";
/*
 *  index.php
 *  gitphp: A PHP git repository browser
 *  Component: Index script
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

 /*
  * Configuration
  */
 include_once('config.inc.php');

 /*
  * Instantiate Smarty
  */
 include_once($gitphp_conf['smarty_prefix'] . "Smarty.class.php");
 $tpl =& new Smarty;
 $tpl->load_filter('output','trimwhitespace');

 /*
  * Function library
  */
 include_once('gitphp.lib.php');

 $rss_link = FALSE;
 $suppress_headers = FALSE;

 ob_start();
 if (isset($_GET['a']) && $_GET['a'] == "opml") {
	$suppress_headers = TRUE;
	git_opml($gitphp_conf['projectroot'],$git_projects);
 } else if (isset($_GET['p'])) {
 	if (!is_dir($gitphp_conf['projectroot'] . $_GET['p']))
		echo "No such directory";
	else if (!is_file($gitphp_conf['projectroot'] . $_GET['p'] . "/HEAD"))
		echo "No such project";
	else {
		$rss_link = TRUE;
		if (!isset($_GET['a']))
			git_summary($gitphp_conf['projectroot'],$_GET['p']);
		else {
			switch ($_GET['a']) {
				case "summary":
					git_summary($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "tree":
					git_tree($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['f'],$_GET['hb']);
					break;
				case "shortlog":
					git_shortlog($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['pg']);
					break;
				case "log":
					git_log($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['pg']);
					break;
				case "commit":
					git_commit($gitphp_conf['projectroot'],$_GET['p'],$_GET['h']);
					break;
				case "commitdiff":
					git_commitdiff($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hp']);
					break;
				case "commitdiff_plain":
					$suppress_headers = TRUE;
					git_commitdiff_plain($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hp']);
					break;
				case "heads":
					git_heads($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "tags":
					git_tags($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "rss":
					$suppress_headers = TRUE;
					git_rss($gitphp_conf['projectroot'],$_GET['p']);
					break;
				case "blob":
					git_blob($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['f'],$_GET['hb']);
					break;
				case "blob_plain":
					$suppress_headers = TRUE;
					git_blob_plain($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['f']);
					break;
				case "blobdiff":
					git_blobdiff($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hb'],$_GET['hp'],$_GET['f']);
					break;
				case "blobdiff_plain":
					$suppress_headers = TRUE;
					git_blobdiff_plain($gitphp_conf['projectroot'],$_GET['p'],$_GET['h'],$_GET['hb'],$_GET['hp'],$_GET['f']);
					break;
				case "snapshot":
					$suppress_headers = TRUE;
					git_snapshot($gitphp_conf['projectroot'],$_GET['p'],$_GET['h']);
					break;
				case "history":
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
 	git_project_list($gitphp_conf['projectroot'],$git_projects,$_GET['o']);
 }
 $main = ob_get_contents();
 ob_end_clean();

 if (!$suppress_headers) {
	 $tpl->clear_all_assign();
	 $tpl->assign("stylesheet",$gitphp_conf['stylesheet']);
	 $tpl->assign("version",$version);
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
		$tpl->assign("descr",git_project_descr($gitphp_conf['projectroot'],$_GET['p']));
	 }
	 $tpl->display("footer.tpl");
 }

 ob_end_flush();

?>
