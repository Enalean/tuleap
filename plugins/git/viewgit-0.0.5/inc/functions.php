<?php
/** @file
 * Functions used by ViewGit.
 */

function debug($msg)
{
	global $conf;

	if ($conf['debug']) {
		file_put_contents('php://stderr', gmstrftime('%H:%M:%S') ." viewgit: $_SERVER[REMOTE_ADDR]:$_SERVER[REMOTE_PORT] $msg\n", FILE_APPEND);
	}
}

/**
 * Formats "git diff" output into xhtml.
 * @return array(array of filenames, xhtml)
 */
function format_diff($text)
{
	$files = array();

	// match every "^diff --git a/<path> b/<path>$" line
	foreach (explode("\n", $text) as $line) {
		if (preg_match('#^diff --git a/(.*) b/(.*)$#', $line, $matches) > 0) {
			$files[$matches[1]] = urlencode($matches[1]);
		}
	}

	$text = htmlentities_wrapper($text);

	$text = preg_replace(
		array(
			'/^(\+.*)$/m',
			'/^(-.*)$/m',
			'/^(@.*)$/m',
			'/^([^d\+-@].*)$/m',
		),
		array(
			'<span class="add">$1</span>',
			'<span class="del">$1</span>',
			'<span class="pos">$1</span>',
			'<span class="etc">$1</span>',
		),
		$text);
	$text = preg_replace_callback('#^diff --git a/(.*) b/(.*)$#m',
		create_function(
			'$m',
			'return "<span class=\"diffline\"><a name=\"". urlencode($m[1]) ."\">diff --git a/$m[1] b/$m[2]</a></span>";'
		),
		$text);

	return array($files, $text);
}

/**
 * Get project information from config and git, name/description and HEAD
 * commit info are returned in an array.
 */
function get_project_info($name)
{
	global $conf;

	$info = $conf['projects'][$name];
	$info['name'] = $name;
	$info['description'] = file_get_contents($info['repo'] .'/description');

	$headinfo = git_get_commit_info($name, 'HEAD');
	$info['head_stamp'] = $headinfo['author_utcstamp'];
	$info['head_datetime'] = gmstrftime($conf['datetime'], $headinfo['author_utcstamp']);
	$info['head_hash'] = $headinfo['h'];
	$info['head_tree'] = $headinfo['tree'];

	return $info;
}

/**
 * Get diff between given revisions as text.
 */
function git_diff($project, $from, $to)
{
	return join("\n", run_git($project, "diff $from..$to"));
}

function git_diffstat($project, $commit, $commit_base = null)
{
	if (is_null($commit_base)) {
		$commit_base = "$commit^";
	}
	return join("\n", run_git($project, "diff --stat $commit_base..$commit"));
}

/**
 * Get details of a commit: tree, parents, author/committer (name, mail, date), message
 */
function git_get_commit_info($project, $hash = 'HEAD')
{
	global $conf;

	$info = array();
	$info['h_name'] = $hash;
	$info['message_full'] = '';
	$info['parents'] = array();

	$output = run_git($project, "rev-list --header --max-count=1 $hash");
	// tree <h>
	// parent <h>
	// author <name> "<"<mail>">" <stamp> <timezone>
	// committer
	// <empty>
	//     <message>
	$pattern = '/^(author|committer) ([^<]+) <([^>]*)> ([0-9]+) (.*)$/';
	foreach ($output as $line) {
		if (substr($line, 0, 4) === 'tree') {
			$info['tree'] = substr($line, 5);
		}
		// may be repeated multiple times for merge/octopus
		elseif (substr($line, 0, 6) === 'parent') {
			$info['parents'][] = substr($line, 7);
		}
		elseif (preg_match($pattern, $line, $matches) > 0) {
			$info[$matches[1] .'_name'] = $matches[2];
			$info[$matches[1] .'_mail'] = $matches[3];
			$info[$matches[1] .'_stamp'] = $matches[4] + ((intval($matches[5]) / 100.0) * 3600);
			$info[$matches[1] .'_timezone'] = $matches[5];
			$info[$matches[1] .'_utcstamp'] = $matches[4];

			if (isset($conf['mail_filter'])) {
				$info[$matches[1] .'_mail'] = $conf['mail_filter']($info[$matches[1] .'_mail']);
			}
		}
		// Lines starting with four spaces and empty lines after first such line are part of commit message
		elseif (substr($line, 0, 4) === '    ' || (strlen($line) == 0 && isset($info['message']))) {
			$info['message_full'] .= substr($line, 4) ."\n";
			if (!isset($info['message'])) {
				$info['message'] = substr($line, 4, $conf['commit_message_maxlen']);
				$info['message_firstline'] = substr($line, 4);
			}
		}
		elseif (preg_match('/^[0-9a-f]{40}$/', $line) > 0) {
			$info['h'] = $line;
		}
	}

	return $info;
}

/**
 * Get list of heads (branches) for a project.
 */
function git_get_heads($project)
{
	$heads = array();

	$output = run_git($project, 'show-ref --heads');
	foreach ($output as $line) {
		$fullname = substr($line, 41);
		$tmp = explode('/', $fullname);
		$name = array_pop($tmp);
		$heads[] = array('h' => substr($line, 0, 40), 'fullname' => "$fullname", 'name' => "$name");
	}

	return $heads;
}

/**
 * Get array containing path information for parts, starting from root_hash.
 *
 * @param root_hash commit/tree hash for the root tree
 * @param path path
 */
function git_get_path_info($project, $root_hash, $path)
{
	if (strlen($path) > 0) {
		$parts = explode('/', $path);
	} else {
		$parts = array();
	}

	$pathinfo = array();

	$tid = $root_hash;
	$pathinfo = array();
	foreach ($parts as $p) {
		$entry = git_ls_tree_part($project, $tid, $p);
		if (is_null($entry)) {
			die("Invalid path info: $path");
		}
		$pathinfo[] = $entry;
		$tid = $entry['hash'];
	}

	return $pathinfo;
}

/**
 * Get revision list starting from given commit.
 * @param max_count number of commit hashes to return, or all if not given
 * @param start revision to start from, or HEAD if not given
 */
function git_get_rev_list($project, $max_count = null, $start = 'HEAD')
{
	$cmd = "rev-list $start";
	if (!is_null($max_count)) {
		$cmd = "rev-list --max-count=$max_count $start";
	}

	return run_git($project, $cmd);
}

/**
 * Get list of tags for a project.
 */
function git_get_tags($project)
{
	$tags = array();

	$output = run_git($project, 'show-ref --tags');
	foreach ($output as $line) {
		$fullname = substr($line, 41);
		$tmp = explode('/', $fullname);
		$name = array_pop($tmp);
		$tags[] = array('h' => substr($line, 0, 40), 'fullname' => $fullname, 'name' => $name);
	}
	return $tags;
}

/**
 * Get information about objects in a tree.
 * @param tree tree or commit hash
 * @return list of arrays containing name, mode, type, hash
 */
function git_ls_tree($project, $tree)
{
	$entries = array();
	$output = run_git($project, "ls-tree $tree");
	// 100644 blob 493b7fc4296d64af45dac64bceac2d9a96c958c1    .gitignore
	// 040000 tree 715c78b1011dc58106da2a1af2fe0aa4c829542f    doc
	foreach ($output as $line) {
		$parts = preg_split('/\s+/', $line, 4);
		$entries[] = array('name' => $parts[3], 'mode' => $parts[0], 'type' => $parts[1], 'hash' => $parts[2]);
	}

	return $entries;
}

/**
 * Get information about the given object in a tree, or null if not in the tree.
 */
function git_ls_tree_part($project, $tree, $name)
{
	$entries = git_ls_tree($project, $tree);
	foreach ($entries as $entry) {
		if ($entry['name'] === $name) {
			return $entry;
		}
	}
	return null;
}

/**
 * Get the ref list as dict: hash -> list of names.
 * @param tags whether to show tags
 * @param heads whether to show heads
 * @param remotes whether to show remote heads, currently implies tags and heads too.
 */
function git_ref_list($project, $tags = true, $heads = true, $remotes = true)
{
	$cmd = "show-ref --dereference";
	if (!$remotes) {
		if ($tags) { $cmd .= " --tags"; }
		if ($heads) { $cmd .= " --heads"; }
	}

	$result = array();
	$output = run_git($project, $cmd);
	foreach ($output as $line) {
		// <hash> <ref>
		$parts = explode(' ', $line, 2);
		$name = str_replace(array('refs/', '^{}'), array('', ''), $parts[1]);
		$result[$parts[0]][] = $name;
	}
	return $result;
}

/**
 * Find commits based on search type and string.
 */
function git_search_commits($project, $type, $string)
{
	// git log -sFOO
	if ($type == 'change') {
		$cmd = 'log -S'. escapeshellarg($string);
	}
	elseif ($type == 'commit') {
		$cmd = 'log -i --grep='. escapeshellarg($string);
	}
	elseif ($type == 'author') {
		$cmd = 'log -i --author='. escapeshellarg($string);
	}
	elseif ($type == 'committer') {
		$cmd = 'log -i --committer='. escapeshellarg($string);
	}
	else {
		die('Unsupported type');
	}
	$lines = run_git($project, $cmd);

	$result = array();
	foreach ($lines as $line) {
		if (preg_match('/^commit (.*?)$/', $line, $matches)) {
			$result[] = $matches[1];
		}
	}
	return $result;
}

/**
 * Get shortlog entries for the given project.
 */
function handle_shortlog($project, $hash = 'HEAD')
{
	global $conf;

	$refs_by_hash = git_ref_list($project, true, true, $conf['shortlog_remote_labels']);

	$result = array();
	$revs = git_get_rev_list($project, $conf['summary_shortlog'], $hash);
	foreach ($revs as $rev) {
		$info = git_get_commit_info($project, $rev);
		$refs = array();
		if (in_array($rev, array_keys($refs_by_hash))) {
			$refs = $refs_by_hash[$rev];
		}
		$result[] = array(
			'author' => $info['author_name'],
			'date' => gmstrftime($conf['datetime'], $info['author_utcstamp']),
			'message' => $info['message'],
			'commit_id' => $rev,
			'tree' => $info['tree'],
			'refs' => $refs,
		);
	}
	#print_r($result);
	#die();

	return $result;
}

/**
 * Fetch tags data, newest first.
 *
 * @param limit maximum number of tags to return
 */
function handle_tags($project, $limit = 0)
{
	global $conf;

	$tags = git_get_tags($project);
	$result = array();
	foreach ($tags as $tag) {
		$info = git_get_commit_info($project, $tag['h']);
		$result[] = array(
			'stamp' => $info['author_utcstamp'],
			'date' => gmstrftime($conf['datetime'], $info['author_utcstamp']),
			'h' => $tag['h'],
			'fullname' => $tag['fullname'],
			'name' => $tag['name'],
		);
	}

	// sort tags newest first
	// aka. two more reasons to hate PHP (figuring those out is your homework:)
	usort($result, create_function(
		'$x, $y',
		'$a = $x["stamp"]; $b = $y["stamp"]; return ($a == $b ? 0 : ($a > $b ? -1 : 1));'
	));

	// TODO optimize this some way, currently all tags are fetched when only a
	// few are shown. The problem is that without fetching the commit info
	// above, we can't sort using dates, only by tag name...
	if ($limit > 0) {
		$result = array_splice($result, 0, $limit);
	}

	return $result;
}

function htmlentities_wrapper($text)
{
	return htmlentities(@iconv('UTF-8', 'UTF-8//IGNORE', $text), ENT_NOQUOTES, 'UTF-8');
}

function xmlentities_wrapper($text)
{
	return str_replace(array('&', '<'), array('&#x26;', '&#x3C;'), @iconv('UTF-8', 'UTF-8//IGNORE', $text));
}

/**
 * Return a URL that contains the given parameters.
 */
function makelink($dict)
{
	$params = array();
	foreach ($dict as $k => $v) {
		$params[] = rawurlencode($k) .'='. str_replace('%2F', '/', rawurlencode($v));
	}
	if (count($params) > 0) {
		return '?'. htmlentities_wrapper(join('&', $params));
	}
	return '';
}

/**
 * Obfuscate the e-mail address.
 */
function obfuscate_mail($mail)
{
	return str_replace(array('@', '.'), array(' at ', ' dot '), $mail);
}

/**
 * Used to format RSS item title and description.
 *
 * @param info commit info from git_get_commit_info()
 */
function rss_item_format($format, $info)
{
	return preg_replace(array(
		'/{AUTHOR}/',
		'/{AUTHOR_MAIL}/',
		'/{SHORTLOG}/',
		'/{LOG}/',
		'/{COMMITTER}/',
		'/{COMMITTER_MAIL}/',
		'/{DIFFSTAT}/',
	), array(
		htmlentities_wrapper($info['author_name']),
		htmlentities_wrapper($info['author_mail']),
		htmlentities_wrapper($info['message_firstline']),
		htmlentities_wrapper($info['message_full']),
		htmlentities_wrapper($info['committer_name']),
		htmlentities_wrapper($info['committer_mail']),
		htmlentities_wrapper(isset($info['diffstat']) ? $info['diffstat'] : ''),
	), $format);
}

function rss_pubdate($secs)
{
	return gmdate('D, d M Y H:i:s O', $secs);
}

/**
 * Executes a git command in the project repo.
 * @return array of output lines
 */
function run_git($project, $command)
{
	global $conf;

	$output = array();
	$cmd = $conf['git'] ." --git-dir=". escapeshellarg($conf['projects'][$project]['repo']) ." $command";
	$ret = 0;
	exec($cmd, $output, $ret);
	//if ($ret != 0) { die('FATAL: exec() for git failed, is the path properly configured?'); }
	return $output;
}

/**
 * Executes a git command in the project repo, sending output directly to the
 * client.
 */
function run_git_passthru($project, $command)
{
	global $conf;

	$cmd = $conf['git'] ." --git-dir=". escapeshellarg($conf['projects'][$project]['repo']) ." $command";
	$result = 0;
	passthru($cmd, $result);
	return $result;
}

/**
 * Makes sure the given project is valid. If it's not, this function will
 * die().
 * @return the project
 */
function validate_project($project)
{
	global $conf;

	if (!in_array($project, array_keys($conf['projects']))) {
		die('Invalid project');
	}
	return $project;
}

/**
 * Makes sure the given hash is valid. If it's not, this function will die().
 * @return the hash
 */
function validate_hash($hash)
{
	if (!preg_match('/^[0-9a-z]{40}$/', $hash) && !preg_match('!^refs/(heads|tags)/[-.0-9a-z]+$!', $hash) && $hash !== 'HEAD') {
		die('Invalid hash');

	}
	return $hash;
}

/**
 * Custom error handler for ViewGit. The errors are pushed to $page['notices']
 * and displayed by templates/header.php.
 */
function vg_error_handler($errno, $errstr, $errfile, $errline)
{
	global $page;

	$mask = ini_get('error_reporting');

	$class = 'error';

	// If mask for this error is not enabled, return silently
	if (!($errno & $mask)) {
		return true;
	}

	// Remove any preceding path until viewgit's directory
	$file = $errfile;
	$file = strstr($file, 'viewgit/');

	$message = "$file:$errline $errstr [$errno]";

	switch ($errno) {
		case E_ERROR:
			$class = 'error';
			break;
		case E_WARNING:
			$class = 'warning';
			break;
		case E_NOTICE:
		case E_STRICT:
		default:
			$class = 'info';
			break;
	}

	$page['notices'][] = array(
		'message' => $message,
		'class' => $class,
	);

	return true;
}

