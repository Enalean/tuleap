<?php
/*
 *  index.php
 *  gitphp: A PHP git repository browser
 *  Component: Index script
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 */

/**
 * Define some paths
 */
define('GITPHP_BASEDIR', dirname(__FILE__) . '/');
define('GITPHP_CONFIGDIR', GITPHP_BASEDIR . 'config/');
define('GITPHP_INCLUDEDIR', GITPHP_BASEDIR . 'include/');

 /*
  * Version
  */
 include_once(GITPHP_INCLUDEDIR . 'version.php');

 /*
  * Constants
  */
 require_once(GITPHP_INCLUDEDIR . 'defs.constants.php');

 /*
  * Configuration
  */
 require_once(GITPHP_CONFIGDIR . 'gitphp.conf.php');
 require_once(GITPHP_INCLUDEDIR . 'Config.class.php');
 try {
 	Config::GetInstance()->LoadConfig(GITPHP_CONFIGDIR . 'gitphp.conf.php.example');
 } catch (Exception $e) {
 }
 Config::GetInstance()->LoadConfig(GITPHP_CONFIGDIR . 'gitphp.conf.php');


 $project = null;

 if (isset($_GET['p'])) {
 	$fullpath = realpath(Config::GetInstance()->GetValue('projectroot') . $_GET['p']);
	$realprojroot = realpath(Config::GetInstance()->GetValue('projectroot'));
	$pathpiece = substr($fullpath, 0, strlen($realprojroot));
	if (strcmp($pathpiece, $realprojroot) === 0) {
		if (is_string($git_projects) && is_file($git_projects)) {
			if ($fp = fopen($git_projects, 'r')) {
				while (!feof($fp) && ($line = fgets($fp))) {
					$pinfo = explode(' ', $line);
					$ppath = trim($pinfo[0]);
					if ($ppath == $_GET['p']) {
						$project = $_GET['p'];
						break;
					}
				}
				fclose($fp);
			}
		} else if (is_array($git_projects)) {
			foreach ($git_projects as $category) {
				if (array_search($_GET['p'], $category)) {
					$project = $_GET['p'];
					break;
				}
			}
		} else {
			$project = $_GET['p'];
		}
		if (isset($project))
			$project = str_replace(chr(0), '', $project);
	}
 }

 $extraoutput = FALSE;

 /*
  * Instantiate Smarty
  */
 require_once(Config::GetInstance()->GetValue('smarty_prefix', 'lib/smarty/libs/') . "Smarty.class.php");
 $tpl = new Smarty;
 if (!isset($_GET['a']) ||
	!in_array($_GET['a'], array('commitdiff_plain', 'blob_plain',
		'blobdiff_plain', 'rss', 'opml', 'snapshot'))) {
	$tpl->load_filter('output','trimwhitespace');
	$extraoutput = TRUE;
}

 /*
  * Debug
  */
 if (Config::GetInstance()->GetValue('debug', false)) {
 	if ($extraoutput) {
		define('GITPHP_START_TIME', microtime(true));
		error_reporting(E_ALL|E_STRICT);
	}
 }

/*
 * Caching
 */
 if (Config::GetInstance()->GetValue('cache', false)) {
 	$tpl->caching = 2;
	if (Config::GetInstance()->HasKey('cachelifetime'))
		$tpl->cache_lifetime = Config::GetInstance()->GetValue('cachelifetime');
	if (Config::GetInstance()->GetValue('cacheexpire', true) === false) {
		require_once(GITPHP_INCLUDEDIR . 'cache.cache_expire.php');
		cache_expire(Config::GetInstance()->GetValue('projectroot'), $project, (isset($git_projects) ? $git_projects : null));
	}
 }

/*
 * Setup global assigns used everywhere (such as header/footer)
 */
 $tpl->assign("stylesheet", Config::GetInstance()->GetValue('stylesheet', 'gitphp.css'));
 $tpl->assign("version",$gitphp_version);
 $tpl->assign("pagetitle", Config::GetInstance()->GetValue('title', $gitphp_appstring));
 if ($project) {
	$tpl->assign("validproject",TRUE);
	$tpl->assign("project",$project);
	require_once(GITPHP_INCLUDEDIR . 'git/Project.class.php');
	$projectObj = new Project($project);
	$tpl->assign("projectdescription", $projectObj->GetDescription());
	if (isset($_GET['a'])) {
		$tpl->assign("action",$_GET['a']);
		$tpl->assign("validaction", TRUE);
	}
 }
 if (isset($_GET['st']))
 	$tpl->assign("currentsearchtype",$_GET['st']);
 else
	$tpl->assign("currentsearchtype","commit");
if (isset($_GET['s']))
	$tpl->assign("currentsearch",$_GET['s']);
if (isset($_GET['hb']))
	$tpl->assign("currentsearchhash",$_GET['hb']);
else if (isset($_GET['h']))
	$tpl->assign("currentsearchhash",$_GET['h']);
if (Config::GetInstance()->GetValue('search', true))
	$tpl->assign("enablesearch",TRUE);
if (Config::GetInstance()->GetValue('filesearch', true))
	$tpl->assign("filesearch",TRUE);


 if (isset($_GET['a']) && $_GET['a'] == "expire") {
 	require_once(GITPHP_INCLUDEDIR . 'cache.cache_expire.php');
	require_once(GITPHP_INCLUDEDIR . 'display.git_message.php');
	cache_expire(null, null, null, true);
	git_message("Cache expired");
 } else if (isset($_GET['a']) && $_GET['a'] == "opml") {
	require_once(GITPHP_INCLUDEDIR . 'display.git_opml.php');
	git_opml(Config::GetInstance()->GetValue('projectroot'), (isset($git_projects) ? $git_projects : null));
 } else if (isset($_GET['a']) && $_GET['a'] == "project_index") {
	require_once(GITPHP_INCLUDEDIR . 'display.git_project_index.php');
	git_project_index(Config::GetInstance()->GetValue('projectroot'),(isset($git_projects) ? $git_projects : null));
 } else if ($project) {
 	if (!is_dir(Config::GetInstance()->GetValue('projectroot') . $project)) {
		$tpl->assign("validproject",FALSE);
		require_once(GITPHP_INCLUDEDIR . 'display.git_message.php');
		git_message("No such directory",TRUE);
	} else if (!is_file(Config::GetInstance()->GetValue('projectroot') . $project . "/HEAD")) {
		$tpl->assign("validproject",FALSE);
		require_once(GITPHP_INCLUDEDIR . 'display.git_message.php');
		git_message("No such project",TRUE);
	} else {
		if (!isset($_GET['a'])) {
			require_once(GITPHP_INCLUDEDIR . 'display.git_summary.php');
			git_summary(Config::GetInstance()->GetValue('projectroot'),$project);
		} else {
			switch ($_GET['a']) {
				case "summary":
					require_once(GITPHP_INCLUDEDIR . 'display.git_summary.php');
					git_summary(Config::GetInstance()->GetValue('projectroot'),$project);
					break;
				case "tree":
					require_once(GITPHP_INCLUDEDIR . 'display.git_tree.php');
					git_tree(Config::GetInstance()->GetValue('projectroot'), $project, (isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['f']) ? $_GET['f'] : NULL), (isset($_GET['hb']) ? $_GET['hb'] : NULL));
					break;
				case "shortlog":
					require_once(GITPHP_INCLUDEDIR . 'display.git_shortlog.php');
					git_shortlog(Config::GetInstance()->GetValue('projectroot'),$project,(isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['pg']) ? $_GET['pg'] : NULL));
					break;
				case "log":
					require_once(GITPHP_INCLUDEDIR . 'display.git_log.php');
					git_log(Config::GetInstance()->GetValue('projectroot'),$project, (isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['pg']) ? $_GET['pg'] : NULL));
					break;
				case "commit":
					require_once(GITPHP_INCLUDEDIR . 'display.git_commit.php');
					git_commit(Config::GetInstance()->GetValue('projectroot'),$project,$_GET['h']);
					break;
				case "commitdiff":
					require_once(GITPHP_INCLUDEDIR . 'display.git_commitdiff.php');
					git_commitdiff(Config::GetInstance()->GetValue('projectroot'),$project,$_GET['h'], (isset($_GET['hp']) ? $_GET['hp'] : NULL));
					break;
				case "commitdiff_plain":
					require_once(GITPHP_INCLUDEDIR . 'display.git_commitdiff_plain.php');
					git_commitdiff_plain(Config::GetInstance()->GetValue('projectroot'),$project,$_GET['h'],(isset($_GET['hp']) ? $_GET['hp'] : NULL));
					break;
				case "heads":
					require_once(GITPHP_INCLUDEDIR . 'display.git_heads.php');
					git_heads(Config::GetInstance()->GetValue('projectroot'),$project);
					break;
				case "tags":
					require_once(GITPHP_INCLUDEDIR . 'display.git_tags.php');
					git_tags(Config::GetInstance()->GetValue('projectroot'),$project);
					break;
				case "rss":
					require_once(GITPHP_INCLUDEDIR . 'display.git_rss.php');
					git_rss(Config::GetInstance()->GetValue('projectroot'),$project);
					break;
				case "blob":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blob.php');
					git_blob(Config::GetInstance()->GetValue('projectroot'),$project, (isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['f']) ? $_GET['f'] : NULL), (isset($_GET['hb']) ? $_GET['hb'] : NULL));
					break;
				case "blob_plain":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blob_plain.php');
					git_blob_plain(Config::GetInstance()->GetValue('projectroot'),$project,$_GET['h'],(isset($_GET['f']) ? $_GET['f'] : NULL));
					break;
				case "blobdiff":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blobdiff.php');
					git_blobdiff(Config::GetInstance()->GetValue('projectroot'),$project,$_GET['h'],$_GET['hb'],$_GET['hp'],(isset($_GET['f']) ? $_GET['f'] : NULL));
					break;
				case "blobdiff_plain":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blobdiff_plain.php');
					git_blobdiff_plain(Config::GetInstance()->GetValue('projectroot'),$project,$_GET['h'],$_GET['hb'],$_GET['hp'], (isset($_GET['f']) ? $_GET['f'] : NULL));
					break;
				case "blame":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blame.php');
					git_blame(Config::GetInstance()->GetValue('projectroot'),$project, (isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['f']) ? $_GET['f'] : NULL), (isset($_GET['hb']) ? $_GET['hb'] : NULL));
					break;
				case "snapshot":
					require_once(GITPHP_INCLUDEDIR . 'display.git_snapshot.php');
					git_snapshot(Config::GetInstance()->GetValue('projectroot'),$project, (isset($_GET['h']) ? $_GET['h'] : NULL));
					break;
				case "history":
					require_once(GITPHP_INCLUDEDIR . 'display.git_history.php');
					git_history(Config::GetInstance()->GetValue('projectroot'),$project, (isset($_GET['h']) ? $_GET['h'] : NULL),$_GET['f']);
					break;
				case "search":
					if (isset($_GET['st']) && ($_GET['st'] == 'file')) {
						require_once(GITPHP_INCLUDEDIR . 'display.git_search_files.php');
						git_search_files(Config::GetInstance()->GetValue('projectroot'),$project,(isset($_GET['h']) ? $_GET['h'] : NULL),(isset($_GET['s']) ? $_GET['s'] : NULL),(isset($_GET['pg']) ? $_GET['pg'] : 0));
					} else {
						require_once(GITPHP_INCLUDEDIR . 'display.git_search.php');
						git_search(Config::GetInstance()->GetValue('projectroot'),$project,(isset($_GET['h']) ? $_GET['h'] : NULL),(isset($_GET['s']) ? $_GET['s'] : NULL),(isset($_GET['st']) ? $_GET['st'] : "commit"),(isset($_GET['pg']) ? $_GET['pg'] : 0));
					}
					break;
				case "tag":
					require_once(GITPHP_INCLUDEDIR . 'display.git_tag.php');
					git_tag(Config::GetInstance()->GetValue('projectroot'),$project,$_GET['h']);
					break;
				default:
					$tpl->assign("validaction", FALSE);
					require_once(GITPHP_INCLUDEDIR . 'display.git_message.php');
					git_message("Unknown action", TRUE);
					break;
			}
		}
	}
 } else {
	require_once(GITPHP_INCLUDEDIR . 'display.git_project_list.php');
	git_project_list(Config::GetInstance()->GetValue('projectroot'), (isset($git_projects) ? $git_projects : null), (isset($_GET['o']) ? $_GET['o'] : "project"));
 }

 if (Config::GetInstance()->GetValue('debug', false) && $extraoutput)
 	echo "Execution time: " . round(microtime(true) - GITPHP_START_TIME, 8) . " sec";

?>
