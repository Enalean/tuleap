<?php
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><?php echo $page['title']; ?></title>
	<link rel="stylesheet" href="<?php echo $conf['style']; ?>.css" type="text/css" />
	<link rel="icon" type="image/png" href="favicon.png" />
<?php
if (isset($page['project'])) {
	echo "\t<link rel=\"alternate\" type=\"application/rss+xml\" title=\"". htmlentities_wrapper($page['project']) ." log\" href=\"". makelink(array('a' => 'rss-log', 'p' => $page['project'])) ."\" />\n";
}
?>
	<meta name="generator" content="ViewGit" />
<?php VGPlugin::call_hooks('header'); ?>
</head>
<body>

<?php
VGPlugin::call_hooks('page_start');
if (isset($page['notices'])) {
	echo '<div class="notices">';
	foreach ($page['notices'] as $n) {
		echo "<p class=\"$n[class]\">$n[message]</p>";
	}
	echo '</div>';
}
?>

<div class="nav">
<a href=".">Index</a>
<?php
if (isset($page['project'])) {
	echo " &raquo; <a href=\"". makelink(array('a' => 'summary', 'p' => $page['project'])) ."\">$page[project]</a>";
}
if (isset($page['subtitle'])) {
	echo " : $page[subtitle]";
}

if (isset($page['path'])) {
	if (isset($page['pathinfo'])) {
		echo ' ';
		$f = '';
		foreach ($page['pathinfo'] as $p) {
			if (strlen($f) > 0) { $f .= '/'; }
			$f .= "$p[name]";
			echo "/ <a href=\"". makelink(array('a' => ($p['type'] === 'tree' ? 'tree' : 'viewblob'), 'p' => $page['project'], 'h' => $p['hash'], 'hb' => $page['commit_id'], 'f' => $f)) ."\">$p[name]</a> ";
		}
	}
}
?>
</div>
<div id="page_body">

<?php if (isset($page['project'])) { ?>
<div class="pagenav">
<?php
$page['links'] = array(
	'summary' => array(),
	'shortlog' => array(),
	'commit' => array('h' => $page['commit_id']),
	'commitdiff' => array('h' => $page['commit_id']),
	'tree' => array('h' => $page['tree_id'], 'hb' => $page['commit_id']),
);
VGPlugin::call_hooks('pagenav');
$first = true;
foreach ($page['links'] as $link => $params) {
	if (!$first) { echo " | "; }
	if ($page['action'] === $link) { echo '<span class="cur">'; }
	echo "<a href=\"". makelink(array_merge(array('a' => $link, 'p' => $page['project']), $params)) ."\">". ucfirst($link) . "</a>";
	if ($page['action'] === $link) { echo '</span>'; }
	$first = false;
}
?>
 | 
<form action="?" type="get" class="search">
<input type="hidden" name="a" value="search" />
<input type="hidden" name="p" value="<?php echo $page['project']; ?>" />
<select name="st">
<?php
$opts = array('commit', 'change', 'author', 'committer');
foreach ($opts as $opt) {
	echo "\t<option";
	if (isset($page['search_t']) && $opt == $page['search_t']) {
		echo ' selected="selected"';
	}
	echo ">$opt</option>\n";
}
?>
</select>
<input type="text" name="s"<?php if (isset($page['search_s'])) { echo ' value="'. htmlentities_wrapper($page['search_s']) .'"'; } ?> />
</form>
</div>
<?php } ?>

