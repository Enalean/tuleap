<?php
/** @file
 * The main "controller" file of ViewGit.
 *
 * All requests come to this file. You can think of it as the controller in the
 * Model-View-Controller pattern. It reads config, processes user input,
 * fetches required data using git commandline, and finally passes the data to
 * templates to be shown to the user.
 */
error_reporting(E_ALL | E_STRICT);

require_once('inc/config.php');
require_once('inc/functions.php');
require_once('inc/plugins.php');

// Include all plugins
foreach (glob('plugins/*/main.php') as $plugin) {
	require_once($plugin);

	$parts = explode('/', $plugin);
	$name = $parts[1];

	$classname = "${name}plugin";
	$inst = new $classname;
}

$old_error_handler = set_error_handler('vg_error_handler');

// Adjust error_reporting based on config.
if (!$conf['debug']) {
	error_reporting(E_ALL ^ E_NOTICE);
}

if (isset($conf['auth_lib'])){
	require_once("inc/auth_{$conf['auth_lib']}.php");
	auth_check();
}

if (isset($conf['projects_glob'])) {
	foreach ($conf['projects_glob'] as $glob) {
		foreach (glob($glob) as $path) {
			// Get the last part of the path before .git
			$name = preg_replace(array('#/?\.git$#', '#^.*/#'), array('', ''), $path);

			// Workaround against name collisions; proj, proj1, proj2, ...
			$i = '';
			while (in_array($name . $i, array_keys($conf['projects']))) {
				@$i++;
			}
			$name = $name . $i;
			$conf['projects'][$name] = array('repo' => $path);
		}
	}
}

$action = 'index';
$template = 'index';
$page['title'] = 'ViewGit';

if (isset($_REQUEST['a'])) {
	$action = strtolower($_REQUEST['a']);
}
$page['action'] = $action;

/*
 * index - list of projects
 */
if ($action === 'index') {
	$template = 'index';
	$page['title'] = 'List of projects - ViewGit';

	foreach (array_keys($conf['projects']) as $p) {
		$page['projects'][] = get_project_info($p);
	}
}

/*
 * archive - send a tree as an archive to client
 * @param p project
 * @param h tree hash
 * @param t type, "targz" or "zip"
 * @param n OPTIONAL name suggestion
 */
elseif ($action === 'archive') {
	$project = validate_project($_REQUEST['p']);
	$tree = validate_hash($_REQUEST['h']);
	$type = $_REQUEST['t'];

	$basename = "$project-tree-". substr($tree, 0, 7);
	if (isset($_REQUEST['n'])) {
		$basename = "$project-$_REQUEST[n]-". substr($tree, 0, 6);
	}

	if ($type === 'targz') {
		header("Content-Type: application/x-tar-gz");
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=\"$basename.tar.gz\";");
		run_git_passthru($project, "archive --format=tar $tree |gzip");
	}
	elseif ($type === 'zip') {
		header("Content-Type: application/x-zip");
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=\"$basename.zip\";");
		run_git_passthru($project, "archive --format=zip $tree");
	}
	else {
		die('Invalid archive type requested');
	}

	die();
}

/*
 * blob - send a blob to browser with filename suggestion
 * @param p project
 * @param h blob hash
 * @param n filename
 */
elseif ($action === 'blob') {
	$project = validate_project($_REQUEST['p']);
	$hash = validate_hash($_REQUEST['h']);
	$name = $_REQUEST['n'];

	header('Content-type: application/octet-stream');
	header("Content-Disposition: attachment; filename=$name"); // FIXME needs quotation

	run_git_passthru($project, "cat-file blob $hash");
	die();
}

/*
 * co - git checkout. These requests come from mod_rewrite, see the .htaccess file.
 * @param p project
 * @param r path
 */
elseif ($action === 'co') {
	if (!$conf['allow_checkout']) { die('Checkout not allowed'); }

	// For debugging
	debug("Project: $_REQUEST[p] Request: $_REQUEST[r]");

	// eg. info/refs, HEAD
	$p = validate_project($_REQUEST['p']); // project
	$r = $_REQUEST['r']; // path

	$gitdir = $conf['projects'][$p]['repo'];
	$filename = $gitdir .'/'. $r;

	// make sure the request is legit (no reading of other files besides those under git projects)
	if ($r === 'HEAD' || $r === 'info/refs' || preg_match('!^objects/info/(packs|http-alternates|alternates)$!', $r) > 0 || preg_match('!^objects/[0-9a-f]{2}/[0-9a-f]{38}$!', $r) > 0) {
		if (file_exists($filename)) {
			debug('OK, sending');
			readfile($filename);
		} else {
			debug('Not found');
			header('HTTP/1.0 404 Not Found');
		}
	} else {
		debug("Denied");
	}

	die();
}

/*
 * commit - view commit information
 * @param p project
 * @param h commit hash
 */
elseif ($action === 'commit') {
	$template = 'commit';
	$page['project'] = validate_project($_REQUEST['p']);
	$page['title'] = "$page[project] - Commit - ViewGit";
	$page['commit_id'] = validate_hash($_REQUEST['h']);
	$page['subtitle'] = "Commit ". substr($page['commit_id'], 0, 6);

	$info = git_get_commit_info($page['project'], $page['commit_id']);

	$page['author_name'] = $info['author_name'];
	$page['author_mail'] = $info['author_mail'];
	$page['author_datetime'] = gmstrftime($conf['datetime_full'], $info['author_utcstamp']);
	$page['author_datetime_local'] = gmstrftime($conf['datetime_full'], $info['author_stamp']) .' '. $info['author_timezone'];
	$page['committer_name'] = $info['committer_name'];
	$page['committer_mail'] = $info['committer_mail'];
	$page['committer_datetime'] = gmstrftime($conf['datetime_full'], $info['committer_utcstamp']);
	$page['committer_datetime_local'] = gmstrftime($conf['datetime_full'], $info['committer_stamp']) .' '. $info['committer_timezone'];
	$page['tree_id'] = $info['tree'];
	$page['parents'] = $info['parents'];
	$page['message'] = $info['message'];
	$page['message_firstline'] = $info['message_firstline'];
	$page['message_full'] = $info['message_full'];

}

/*
 * commitdiff - view diff of a commit
 * @param p project
 * @param h commit hash
 */
elseif ($action === 'commitdiff') {
	$template = 'commitdiff';
	$page['project'] = validate_project($_REQUEST['p']);
	$page['title'] = "$page[project] - Commitdiff - ViewGit";
	$hash = validate_hash($_REQUEST['h']);
	$page['commit_id'] = $hash;
	$page['subtitle'] = "Commitdiff ". substr($page['commit_id'], 0, 6);

	$info = git_get_commit_info($page['project'], $hash);

	$page['tree_id'] = $info['tree'];

	$page['message'] = $info['message'];
	$page['message_firstline'] = $info['message_firstline'];
	$page['message_full'] = $info['message_full'];
	$page['author_name'] = $info['author_name'];
	$page['author_mail'] = $info['author_mail'];
	$page['author_datetime'] = gmstrftime($conf['datetime'], $info['author_utcstamp']);

	$text = git_diff($page['project'], "$hash^", $hash);
	list($page['files'], $page['diffdata']) = format_diff($text);
	//$page['diffdata'] = format_diff($text);
}

elseif ($action === 'patch') {
	$project = validate_project($_REQUEST['p']);
	$hash = validate_hash($_REQUEST['h']);
	$filename = "$project-". substr($hash, 0, 7) .".patch";

	//header("Content-Type: text/x-diff");
	header("Content-Type: application/octet-stream");
	header("Content-Transfer-Encoding: binary");
	// TODO git-style filename
	header("Content-Disposition: attachment; filename=\"$filename\";");

	run_git_passthru($project, "format-patch --stdout $hash^..$hash");
	die();
}

/*
 * rss-log - RSS feed of project changes
 * @param p project
 */
elseif ($action === 'rss-log') {
	$page['project'] = validate_project($_REQUEST['p']);

	$ext_url = 'http://'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) .'/';

	$page['rss_title'] = "Log for $page[project]";
	$page['rss_link'] = $ext_url . makelink(array('a' => 'summary', 'p' => $page['project']));
	$page['rss_description'] = "Git log for project $page[project], generated by ViewGit.";
	$page['rss_pubDate'] = rss_pubdate(time());
	$page['rss_ttl'] = $conf['rss_ttl'];

	$page['rss_items'] = array();

	$diffstat = strstr($conf['rss_item_description'], '{DIFFSTAT}');

	$revs = git_get_rev_list($page['project'], $conf['rss_max_items']);
	foreach ($revs as $rev) {
		$info = git_get_commit_info($page['project'], $rev);
		$link = $ext_url . makelink(array('a' => 'commit', 'p' => $page['project'], 'h' => $rev));
		if ($diffstat) {
			$info['diffstat'] = git_diffstat($page['project'], $rev);
		}

		$page['rss_items'][] = array(
			'title' => rss_item_format($conf['rss_item_title'], $info),
			'guid' => $link,
			'link' => $link,
			'description' => rss_item_format($conf['rss_item_description'], $info),
			'pubdate' => rss_pubdate($info['author_utcstamp']),
		);
	}

	require('templates/rss.php');
	die();
}

/*
 * search - search project history
 * @param p project
 * @param st search type: commit,grep,author,committer,pickaxe
 * @param s string to search for
 */
elseif ($action === 'search') {
	$template = 'shortlog';

	$page['project'] = validate_project($_REQUEST['p']);

	$info = git_get_commit_info($page['project']);
	$page['commit_id'] = $info['h'];
	$page['tree_id'] = $info['tree'];

	$type = $_REQUEST['st'];
	$string = $_REQUEST['s'];

	$page['search_t'] = $type;
	$page['search_s'] = $string;

	$commits = git_search_commits($page['project'], $type, $string);
	$shortlog = array();
	foreach ($commits as $c) {
		$info = git_get_commit_info($page['project'], $c);
		$shortlog[] = array(
			'author' => $info['author_name'],
			'date' => gmstrftime($conf['datetime'], $info['author_utcstamp']),
			'message' => $info['message'],
			'commit_id' => $info['h'],
			'tree' => $info['tree'],
			'refs' => array(),
		);
	}
	$page['shortlog'] = $shortlog;
}

/*
 * shortlog - project shortlog entries
 * @param p project
 * @param h OPTIONAL commit id to start showing log from
 */
elseif ($action === 'shortlog') {
	$template = 'shortlog';
	$page['project'] = validate_project($_REQUEST['p']);
	$page['title'] = "$page[project] - Shortlog - ViewGit";
	$page['subtitle'] = "Shortlog";
	if (isset($_REQUEST['h'])) {
		$page['ref'] = validate_hash($_REQUEST['h']);
	} else {
		$page['ref'] = 'HEAD';
	}

	$info = git_get_commit_info($page['project'], $page['ref']);
	$page['commit_id'] = $info['h'];
	$page['tree_id'] = $info['tree'];

	$page['shortlog'] = handle_shortlog($page['project'], $page['ref']);
}
elseif ($action === 'summary') {
	$template = 'summary';
	$page['project'] = validate_project($_REQUEST['p']);
	$page['title'] = "$page[project] - Summary - ViewGit";
	$page['subtitle'] = "Summary";

	$info = git_get_commit_info($page['project']);
	$page['commit_id'] = $info['h'];
	$page['tree_id'] = $info['tree'];
	
	$page['shortlog'] = handle_shortlog($page['project']);

	$page['tags'] = handle_tags($page['project'], $conf['summary_tags']);

	$heads = git_get_heads($page['project']);
	$page['heads'] = array();
	foreach ($heads as $h) {
		$info = git_get_commit_info($page['project'], $h['h']);
		$page['heads'][] = array(
			'date' => gmstrftime($conf['datetime'], $info['author_utcstamp']),
			'h' => $h['h'],
			'fullname' => $h['fullname'],
			'name' => $h['name'],
		);
	}
}
elseif ($action === 'tags') {
	$template = 'tags';
	$page['project'] = validate_project($_REQUEST['p']);
	$page['title'] = "$page[project] - Tags - ViewGit";

	$info = git_get_commit_info($page['project']);
	$page['commit_id'] = $info['h'];
	$page['tree_id'] = $info['tree'];

	$page['tags'] = handle_tags($page['project']);
}
/*
 * Shows a tree, with list of directories/files, links to them and download
 * links to archives.
 *
 * @param p project
 * @param h tree hash
 * @param hb OPTIONAL base commit (trees can be part of multiple commits, this
 * one denotes which commit the user navigated from)
 * @param f OPTIONAL path the user has followed to view this tree
 */
elseif ($action === 'tree') {
	$template = 'tree';
	$page['project'] = validate_project($_REQUEST['p']);
	if (isset($_REQUEST['h'])) {
		$page['tree_id'] = validate_hash($_REQUEST['h']);
	}
	/*
	else {
		// TODO walk the tree
		$page['tree_id'] = 'HEAD';
	}
	*/
	$page['title'] = "$page[project] - Tree - ViewGit";

	// 'hb' optionally contains the commit_id this tree is related to
	if (isset($_REQUEST['hb'])) {
		$page['commit_id'] = validate_hash($_REQUEST['hb']);
	}
	else {
		// for the header
		$info = git_get_commit_info($page['project']);
		$page['commit_id'] = $info['h'];
	}

	$page['path'] = '';
	if (isset($_REQUEST['f'])) {
		$page['path'] = $_REQUEST['f']; // TODO validate?
	}

	// get path info for the header
	$page['pathinfo'] = git_get_path_info($page['project'], $page['commit_id'], $page['path']);
	if (!isset($page['tree_id'])) {
		// Take the last hash from the tree
		if (count($page['pathinfo']) > 0) {
			$page['tree_id'] = $page['pathinfo'][count($page['pathinfo']) - 1]['hash'];
		} else {
			$page['tree_id'] = 'HEAD';
		}
	}

	$page['subtitle'] = "Tree ". substr($page['tree_id'], 0, 6);
	$page['entries'] = git_ls_tree($page['project'], $page['tree_id']);
}
/*
 * View a blob as inline, embedded on the page.
 * @param p project
 * @param h blob hash
 * @param hb OPTIONAL base commit
 */
elseif ($action === 'viewblob') {
	$template = 'blob';
	$page['project'] = validate_project($_REQUEST['p']);
	$page['hash'] = validate_hash($_REQUEST['h']);
	$page['title'] = "$page[project] - Blob - ViewGit";
	if (isset($_REQUEST['hb'])) {
		$page['commit_id'] = validate_hash($_REQUEST['hb']);
	}
	else {
		$page['commit_id'] = 'HEAD';
	}
	$page['subtitle'] = "Blob ". substr($page['hash'], 0, 6);

	$page['path'] = '';
	if (isset($_REQUEST['f'])) {
		$page['path'] = $_REQUEST['f']; // TODO validate?
	}

	// For the header's pagenav
	$info = git_get_commit_info($page['project'], $page['commit_id']);
	$page['commit_id'] = $info['h'];
	$page['tree_id'] = $info['tree'];

	$page['pathinfo'] = git_get_path_info($page['project'], $page['commit_id'], $page['path']);

	$page['data'] = join("\n", run_git($page['project'], "cat-file blob $page[hash]"));

	// GeSHi support
	if ($conf['geshi'] && strpos($page['path'], '.')) {
		$old_mask = error_reporting(E_ALL ^ E_NOTICE);
		require_once($conf['geshi_path']);
		$ext = array_pop(explode('.', $page['path']));
		$lang = Geshi::get_language_name_from_extension($ext);
		if (strlen($lang) > 0) {
			$geshi =& new Geshi($page['data'], $lang);
			if (is_int($conf['geshi_line_numbers'])) {
				if ($conf['geshi_line_numbers'] == 0) {
					$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
				}
				else {
					$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, $conf['geshi_line_numbers']);
				}
			}
			$page['html_data'] = $geshi->parse_code();
		}
		error_reporting($old_mask);
	}
}
elseif (in_array($action, array_keys(VGPlugin::$plugin_actions))) {
	VGPlugin::$plugin_actions[$action]->action($action);
	die();
}
else {
	die('Invalid action');
}

require 'templates/header.php';
require "templates/$template.php";
require 'templates/footer.php';
