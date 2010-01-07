<?php
global $page, $conf;
?>

<h1><a href="<?php echo makelink(array('a' => 'issue', 'p' => $page['project']));?>">Issues</a> - <?php echo $page['issue']['title'];?></h1>

<dl>
  <dt>Title</dt>
    <dd><?php echo $page['issue']['title'];?></dd>
  <dt>Reported By</dt>
    <dd><?php echo $page['issue']['author'];?></dd>
  <dt>Type</dt>
    <dd><?php echo $page['issue']['type'];?></dd>
  <dt>Status</dt>
    <dd><?php echo $page['issue']['status'];?></dd>
  <dt>Severity</dt>
    <dd><?php echo $page['issue']['severity'];?></dd>
  <dt>Priority</dt>
    <dd><?php echo $page['issue']['priority'];?></dd>
  <dt>Created</dt>
    <dd><?php print $page['issue']['created'];?></dd>
</dl>

<?php if (count($page['issue']['comments'])):?>
<h2>Comments</h2>
<table>
<thead><tr>
<th>Date</th>
<th>Time</th>
<th>Text</th>
</tr></thead><tbody>
<?php foreach($page['issue']['comments'] as $comment):?>
<tr>
<td><?php echo $comment['date'];?></td>
<td><?php echo $comment['time'];?></td>
<td><?php echo $comment['text'];?></td>
</tr>
<?php endforeach;?>
</tbody>
</table>
<?php endif;?>

