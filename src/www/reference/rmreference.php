<?php

require_once('pre.php');

$target_id = $request->get('target_id');
$target_gid = $request->get('target_gid');
$target_type = $request->get('target_type');

$source_id = $request->get('source_id');
$source_gid = $request->get('source_gid');
$source_type = $request->get('source_type');
$sql = "DELETE FROM cross_references 
		        WHERE ((target_gid=" . $target_gid . " AND target_id='" . $target_id . "' AND target_type='" . $target_type . "' ) 
				     AND (source_gid=" . $source_gid." AND source_id='" .$source_id . "' AND source_type='" . $source_type. "' )) 
                     OR ((target_gid=" . $source_gid . " AND target_id='" . $source_id . "' AND target_type='" . $source_type . "' ) 
				     AND (source_gid=" . $target_gid." AND source_id='" .$target_id . "' AND source_type='" . $target_type. "' ))";
                     
db_query($sql);

?>
