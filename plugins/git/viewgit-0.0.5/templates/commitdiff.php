<h1><?php echo htmlentities_wrapper($page['message_firstline']); ?></h1>

<div class="authorinfo">
<?php
echo htmlentities_wrapper($page['author_name']);
echo ' ['. $page['author_datetime'] .']';
?>
</div>

<div class="commitmessage">
<pre>
<?php echo htmlentities_wrapper($page['message_full']); ?>
</pre>
</div>

<div class="filelist">
<table>
<thead>
<tr>
	<th>Filename</th>
<?php /*
	<th>Links</th>
*/ ?>
</tr>
</thead>
<tbody>
<?php
// pathname | patch | blob | history
foreach ($page['files'] as $file => $url) {
	echo "<tr>\n";
	echo "<td><a href=\"#$url\">$file</a></td>";
	echo "<td>" /* blob | history */ ."</td>";
	echo "</tr>\n";
}
?>
</tbody>
</table>
</div>

<div class="diff">
<pre>
<?php echo $page['diffdata']; ?>
</pre>
</div>

