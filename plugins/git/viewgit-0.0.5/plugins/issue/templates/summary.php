<?php
global $page;
?>
<?php if ($page['action'] == 'issue'): ?>
<h2>Issues</h2>
<?php else: ?>
<h2><a href="<?php echo makelink(array('a' => 'issue', 'p' => $page['project']));?>">Issues</a></h2>
<?php endif; ?>

<?php if (count($page['issues'])): ?>

<table>
<thead>
<tr>
	<th>Date</th>
	<th>Title</th>
	<th>Assigned</th>
	<th>State</th>
</tr>
</thead>
<tbody>
<?php foreach($page['issues'] as $issue):?>
<tr>
	<td><?php echo $issue['date']; ?></td>
	<td><a href="<?php echo makelink(array('a' => 'issue', 'p' => $page['project'], 'h'=>$issue['id']));?>"><?php echo $issue['title']; ?></a></td>
	<td><?php echo $issue['assign']; ?></td>
	<td><?php echo $issue['state']; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else: ?>
There are no issues.
<?php endif; ?>

