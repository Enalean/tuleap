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
 require_once(GITPHP_INCLUDEDIR . 'Config.class.php');
 try {
 	GitPHP_Config::GetInstance()->LoadConfig(GITPHP_CONFIGDIR . 'gitphp.conf.php.example');
 } catch (Exception $e) {
 }
 GitPHP_Config::GetInstance()->LoadConfig(GITPHP_CONFIGDIR . 'gitphp.conf.php');

 /**
  * Project list
  */
 require_once(GITPHP_INCLUDEDIR . 'git/ProjectList.class.php');
 GitPHP_ProjectList::Instantiate(GITPHP_CONFIGDIR . 'gitphp.conf.php');

 /**
  * Check for projectroot
  */
if (!GitPHP_Config::GetInstance()->GetValue('projectroot', null)) {
	throw new Exception ('A projectroot must be set in the config.');
}

 $project = null;
 $gitphp_current_project = null;

 if (isset($_GET['p'])) {
 	$gitphp_current_project = GitPHP_ProjectList::GetInstance()->GetProject(str_replace(chr(0), '', $_GET['p']));
	$project = $gitphp_current_project->GetProject();
 }

 $extraoutput = FALSE;

 /*
  * Instantiate Smarty
  */
 require_once(GitPHP_Config::GetInstance()->GetValue('smarty_prefix', 'lib/smarty/libs/') . "Smarty.class.php");
 $tpl = new Smarty;
 if (!isset($_GET['a']) ||
	!in_array($_GET['a'], array('commitdiff_plain', 'blob_plain',
		'blobdiff_plain', 'rss', 'opml', 'snapshot'))) {
	$tpl->load_filter('output','trimwhitespace');
	$extraoutput = TRUE;
}

require_once(GITPHP_INCLUDEDIR . 'util.age_string.php');
$tpl->register_modifier('agestring', 'age_string');

 /*
  * Debug
  */
 if (GitPHP_Config::GetInstance()->GetValue('debug', false)) {
 	if ($extraoutput) {
		define('GITPHP_START_TIME', microtime(true));
		error_reporting(E_ALL|E_STRICT);
	}
 }

/*
 * Caching
 */
 if (GitPHP_Config::GetInstance()->GetValue('cache', false)) {
 	$tpl->caching = 2;
	if (GitPHP_Config::GetInstance()->HasKey('cachelifetime'))
		$tpl->cache_lifetime = GitPHP_Config::GetInstance()->GetValue('cachelifetime');
	if (GitPHP_Config::GetInstance()->GetValue('cacheexpire', true) === false) {
		require_once(GITPHP_INCLUDEDIR . 'cache.cache_expire.php');
		cache_expire();
	}
 }

/*
 * Setup global assigns used everywhere (such as header/footer)
 */
 $tpl->assign("stylesheet", GitPHP_Config::GetInstance()->GetValue('stylesheet', 'gitphp.css'));
 $tpl->assign("version",$gitphp_version);
 $tpl->assign("pagetitle", GitPHP_Config::GetInstance()->GetValue('title', $gitphp_appstring));
 if ($project) {
	$tpl->assign("validproject",TRUE);
	$tpl->assign("project",$project);
	$projectObj = GitPHP_ProjectList::GetInstance()->GetProject($project);
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
if (GitPHP_Config::GetInstance()->GetValue('search', true))
	$tpl->assign("enablesearch",TRUE);
if (GitPHP_Config::GetInstance()->GetValue('filesearch', true))
	$tpl->assign("filesearch",TRUE);


 if (isset($_GET['a']) && $_GET['a'] == "expire") {
 	require_once(GITPHP_INCLUDEDIR . 'cache.cache_expire.php');
	require_once(GITPHP_INCLUDEDIR . 'display.git_message.php');
	cache_expire(true);
	git_message("Cache expired");
 } else if (isset($_GET['a']) && $_GET['a'] == "opml") {
	require_once(GITPHP_INCLUDEDIR . 'display.git_opml.php');
	git_opml(GitPHP_Config::GetInstance()->GetValue('projectroot'), GitPHP_ProjectList::GetInstance()->GetConfig());
 } else if (isset($_GET['a']) && $_GET['a'] == "project_index") {
	require_once(GITPHP_INCLUDEDIR . 'display.git_project_index.php');
	git_project_index();
 } else if ($project) {
 	if (!is_dir(GitPHP_Config::GetInstance()->GetValue('projectroot') . $project)) {
		$tpl->assign("validproject",FALSE);
		require_once(GITPHP_INCLUDEDIR . 'display.git_message.php');
		git_message("No such directory",TRUE);
	} else if (!is_file(GitPHP_Config::GetInstance()->GetValue('projectroot') . $project . "/HEAD")) {
		$tpl->assign("validproject",FALSE);
		require_once(GITPHP_INCLUDEDIR . 'display.git_message.php');
		git_message("No such project",TRUE);
	} else {
		if (!isset($_GET['a'])) {
			require_once(GITPHP_INCLUDEDIR . 'display.git_summary.php');
			git_summary();
		} else {
			switch ($_GET['a']) {
				case "summary":
					require_once(GITPHP_INCLUDEDIR . 'display.git_summary.php');
					git_summary();
					break;
				case "tree":
					require_once(GITPHP_INCLUDEDIR . 'display.git_tree.php');
					git_tree((isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['f']) ? $_GET['f'] : NULL), (isset($_GET['hb']) ? $_GET['hb'] : NULL));
					break;
				case "shortlog":
					require_once(GITPHP_INCLUDEDIR . 'display.git_shortlog.php');
					git_shortlog((isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['pg']) ? $_GET['pg'] : NULL));
					break;
				case "log":
					require_once(GITPHP_INCLUDEDIR . 'display.git_log.php');
					git_log((isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['pg']) ? $_GET['pg'] : NULL));
					break;
				case "commit":
					require_once(GITPHP_INCLUDEDIR . 'display.git_commit.php');
					git_commit($_GET['h']);
					break;
				case "commitdiff":
					require_once(GITPHP_INCLUDEDIR . 'display.git_commitdiff.php');
					git_commitdiff($_GET['h'], (isset($_GET['hp']) ? $_GET['hp'] : NULL));
					break;
				case "commitdiff_plain":
					require_once(GITPHP_INCLUDEDIR . 'display.git_commitdiff_plain.php');
					git_commitdiff_plain($_GET['h'],(isset($_GET['hp']) ? $_GET['hp'] : NULL));
					break;
				case "heads":
					require_once(GITPHP_INCLUDEDIR . 'display.git_heads.php');
					git_heads();
					break;
				case "tags":
					require_once(GITPHP_INCLUDEDIR . 'display.git_tags.php');
					git_tags();
					break;
				case "rss":
					require_once(GITPHP_INCLUDEDIR . 'display.git_rss.php');
					git_rss();
					break;
				case "blob":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blob.php');
					git_blob((isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['f']) ? $_GET['f'] : NULL), (isset($_GET['hb']) ? $_GET['hb'] : NULL));
					break;
				case "blob_plain":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blob_plain.php');
					git_blob_plain(GitPHP_Config::GetInstance()->GetValue('projectroot'),$project,$_GET['h'],(isset($_GET['f']) ? $_GET['f'] : NULL));
					break;
				case "blobdiff":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blobdiff.php');
					git_blobdiff($_GET['h'],$_GET['hb'],$_GET['hp'],(isset($_GET['f']) ? $_GET['f'] : NULL));
					break;
				case "blobdiff_plain":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blobdiff_plain.php');
					git_blobdiff_plain(GitPHP_Config::GetInstance()->GetValue('projectroot'),$project,$_GET['h'],$_GET['hb'],$_GET['hp'], (isset($_GET['f']) ? $_GET['f'] : NULL));
					break;
				case "blame":
					require_once(GITPHP_INCLUDEDIR . 'display.git_blame.php');
					git_blame((isset($_GET['h']) ? $_GET['h'] : NULL), (isset($_GET['f']) ? $_GET['f'] : NULL), (isset($_GET['hb']) ? $_GET['hb'] : NULL));
					break;
				case "snapshot":
					require_once(GITPHP_INCLUDEDIR . 'display.git_snapshot.php');
					git_snapshot(GitPHP_Config::GetInstance()->GetValue('projectroot'),$project, (isset($_GET['h']) ? $_GET['h'] : NULL));
					break;
				case "history":
					require_once(GITPHP_INCLUDEDIR . 'display.git_history.php');
					git_history((isset($_GET['h']) ? $_GET['h'] : NULL),$_GET['f']);
					break;
				case "search":
					if (isset($_GET['st']) && ($_GET['st'] == 'file')) {
						require_once(GITPHP_INCLUDEDIR . 'display.git_search_files.php');
						git_search_files((isset($_GET['h']) ? $_GET['h'] : NULL),(isset($_GET['s']) ? $_GET['s'] : NULL),(isset($_GET['pg']) ? $_GET['pg'] : 0));
					} else {
						require_once(GITPHP_INCLUDEDIR . 'display.git_search.php');
						git_search((isset($_GET['h']) ? $_GET['h'] : NULL),(isset($_GET['s']) ? $_GET['s'] : NULL),(isset($_GET['st']) ? $_GET['st'] : "commit"),(isset($_GET['pg']) ? $_GET['pg'] : 0));
					}
					break;
				case "tag":
					require_once(GITPHP_INCLUDEDIR . 'display.git_tag.php');
					git_tag($_GET['h']);
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
	git_project_list((isset($_GET['o']) ? $_GET['o'] : "project"));
 }

 if (GitPHP_Config::GetInstance()->GetValue('debug', false) && $extraoutput)
 	echo "Execution time: " . round(microtime(true) - GITPHP_START_TIME, 8) . " sec";

?>
