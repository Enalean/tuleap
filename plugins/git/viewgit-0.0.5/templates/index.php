
<table>
<thead>
<tr>
	<th>Project</th>
	<th>Description</th>
	<th>Last Change</th>
	<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
foreach ($page['projects'] as $p) {
	$tr_class = $tr_class=="odd" ? "even" : "odd";
	echo "<tr class=\"$tr_class\">\n";
	echo "\t<td><a href=\"". makelink(array('a' => 'summary', 'p' => $p['name'])) ."\">$p[name]</a></td>\n";
	echo "\t<td>". htmlentities_wrapper($p['description']) ."</td>\n";
	echo "\t<td>". htmlentities_wrapper($p['head_datetime']) ."</td>\n";
	echo "\t<td>";
	echo "<a href=\"". makelink(array('a' => 'tree', 'p' => $p['name'], 'h' => $p['head_tree'], 'hb' => $p['head_hash'])) ."\" class=\"tree_link\" title=\"Tree\">tree</a>";
	echo " <a href=\"". makelink(array('a' => 'archive', 'p' => $p['name'], 'h' => $p['head_tree'], 't' => 'targz')) ."\" rel=\"nofollow\" class=\"tar_link\" title=\"tar/gz\">tar/gz</a>";
	echo " <a href=\"". makelink(array('a' => 'archive', 'p' => $p['name'], 'h' => $p['head_tree'], 't' => 'zip')) ."\" rel=\"nofollow\" class=\"zip_link\" title=\"zip\">zip</a>";
	echo "</td>\n";
	echo "</tr>\n";
}
?>
</tbody>
</table>

