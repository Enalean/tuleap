<h1><a href="<?php echo makelink(array('a' => 'shortlog', 'p' => $page['project'])); ?>">Shortlog</a></h1>

<table class="shortlog">
<thead>
<tr>
	<th class="date">Date</th>
	<th class="author">Author</th>
	<th class="message">Message</th>
	<th class="actions">Actions</th>
</tr>
</thead>
<tbody>
<?php
$page['lasthash'] = 'HEAD';
foreach ($page['shortlog'] as $l) {
	$tr_class = $tr_class=="odd" ? "even" : "odd";
	echo "<tr class=\"$tr_class\">\n";
	echo "\t<td>$l[date]</td>\n";
	echo "\t<td>". htmlentities_wrapper($l['author']) ."</td>\n";
	echo "\t<td><a href=\"". makelink(array('a' => 'commit', 'p' => $page['project'], 'h' => $l['commit_id'])) ."\">". htmlentities_wrapper($l['message']) ."</a>";
	if (count($l['refs']) > 0) {
		foreach ($l['refs'] as $ref) {
			$parts = explode('/', $ref);
			$shortref = join('/', array_slice($parts, 1));
			$type = 'head';
			if ($parts[0] == 'tags') { $type = 'tag'; }
			elseif ($parts[0] == 'remotes') { $type = 'remote'; }
			echo "<span class=\"label $type\" title=\"$ref\">$shortref</span>";
		}
	}
	echo "</td>\n";
	echo "\t<td>";
	echo "<a href=\"". makelink(array('a' => 'commitdiff', 'p' => $page['project'], 'h' => $l['commit_id'])) ."\" class=\"cdiff_link\" title=\"Commit Diff\">commitdiff</a>";
	echo " <a href=\"". makelink(array('a' => 'tree', 'p' => $page['project'], 'h' => $l['tree'], 'hb' => $l['commit_id'])) ."\" class=\"tree_link\" title=\"Tree\">tree</a>";
	echo " <a href=\"". makelink(array('a' => 'archive', 'p' => $page['project'], 'h' => $l['tree'], 't' => 'targz')) ."\" rel=\"nofollow\" class=\"tar_link\" title=\"tar/gz\">tar/gz</a>";
	echo " <a href=\"". makelink(array('a' => 'archive', 'p' => $page['project'], 'h' => $l['tree'], 't' => 'zip')) ."\" rel=\"nofollow\" class=\"zip_link\" title=\"zip\">zip</a>";
	echo " <a href=\"". makelink(array('a' => 'patch', 'p' => $page['project'], 'h' => $l['commit_id'])) ."\" class=\"patch_link\" title=\"Patch\">patch</a>";
	echo "</td>\n";
	echo "</tr>\n";
	$page['lasthash'] = $l['commit_id'];
}
?>
</tbody>
</table>


<?php 
if ($page['lasthash'] !== 'HEAD') {
	echo "<p><a href=\"". makelink(array('a' => 'shortlog', 'p' => $page['project'], 'h' => $page['lasthash'])) ."\">More...</a></p>";
}
?>
