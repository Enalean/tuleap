<?php

require_once('pre.php');

function rank($parent_id) {
    $sql = 'SELECT item_id FROM plugin_docman_item WHERE parent_id = '. $parent_id .' ORDER BY rank, item_id';
    $rank = 0;
    $req = mysql_query($sql) or die(mysql_error().'<br>'.$sql);
    while($data = mysql_fetch_assoc($req)) {
        $sql = 'UPDATE plugin_docman_item SET rank = '. $rank++ .' WHERE item_id = '. $data['item_id'];
        mysql_query($sql) or die(mysql_error().'<br>'.$sql);
        rank($data['item_id']);
    }
}
rank(0);

?>
