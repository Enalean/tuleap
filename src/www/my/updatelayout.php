<?php
require_once('pre.php');
require_once('common/widget/Widget.class.php');

$request =& HTTPRequest::instance();
if ($layout_id = (int)$request->get('layout_id') || $request->get('action') == 'preferences') {
    $name = null;
    if ($request->exist('name')) {
        $param = $request->get('name');
        $name = array_pop(array_keys($param));
        $instance_id = (int)$param[$name];
    }
    switch($request->get('action')) {
        case 'widget':
            if ($name && $request->exist('layout_id')) {
                if ($widget = Widget::getInstance($name)) {
                    $param = $request->get('name');
                    $action = array_pop(array_keys($param[$name]));
                    $instance_id = (int)$param[$name][$action];
                    switch($action) {
                        case 'remove':
                            $sql = "DELETE FROM layouts_contents WHERE owner_type = 'u' AND owner_id = ". user_getid() ." AND layout_id = $layout_id AND name = '$name' AND content_id = $instance_id";
                            db_query($sql);
                            if (!db_error()) {
                                $widget->destroy($instance_id);
                            }
                            break;
                        case 'add':
                        default:
                            //Search for the right column. (The first used)
                            $sql = "SELECT u.column_id AS id
                            FROM layouts_contents AS u
                            LEFT JOIN (SELECT r.rank AS rank, c.id as id
                            FROM layouts_rows AS r INNER JOIN layouts_rows_columns AS c
                            ON (c.layout_row_id = r.id)
                            WHERE r.layout_id = $layout_id) AS col
                            ON (u.column_id = col.id)
                            WHERE u.owner_type = 'u' 
                              AND u.owner_id = ". user_getid() ."
                              AND u.layout_id = $layout_id
                              AND u.column_id <> 0
                            ORDER BY col.rank, col.id";
                            $res = db_query($sql);
                            echo db_error();
                            $column_id = db_result($res, 0, 'id');
                            $column_id = $column_id ? $column_id : 0;
                            
                            //content_id
                            if ($widget->isUnique()) {
                                //unique widgets do not have content_id
                                $content_id = 0;
                            } else {
                                $content_id = $widget->create($request);
                            }
                            
                            //See if it already exists but not used
                            $sql = "SELECT column_id FROM layouts_contents 
                            WHERE owner_type = 'u'
                              AND owner_id = ". user_getid() ."
                              AND layout_id = $layout_id 
                              AND name = '$name'";
                            $res = db_query($sql);
                            echo db_error();
                            if (db_numrows($res) && !$widget->isUnique() && db_result($res, 0, 'column_id') == 0) {
                                //search for rank
                                $sql = "SELECT min(rank) - 1 AS rank FROM layouts_contents WHERE owner_type = 'u' AND owner_id = ". user_getid() ." AND layout_id = $layout_id AND column_id = $column_id ";
                                $res = db_query($sql);
                                echo db_error();
                                $rank = db_result($res, 0, 'rank');
                                
                                //Update
                                $sql = "UPDATE layouts_contents
                                    SET column_id = ". $column_id .", rank = $rank
                                    WHERE owner_type = 'u' 
                                      AND owner_id = ". user_getid() ."
                                      AND name = '$name'
                                      AND layout_id = ". $layout_id;
                                $res = db_query($sql);
                                echo db_error();
                            } else {
                                //Insert
                                $sql = "INSERT INTO layouts_contents(owner_type, owner_id, layout_id, column_id, name, content_id, rank) 
                                SELECT owner_type, owner_id, layout_id, column_id, '$name', $content_id, rank - 1 
                                FROM layouts_contents 
                                WHERE owner_type = 'u' AND owner_id = ". user_getid() ." AND layout_id = $layout_id AND column_id = $column_id 
                                ORDER BY rank ASC
                                LIMIT 1";
                                db_query($sql);
                                echo db_error();
                            }
                            break;
                    }
                }
            }
            break;
        case 'minimize':
            if ($name) {
                $sql = "UPDATE layouts_contents SET is_minimized = 1 WHERE owner_type = 'u' AND owner_id = ". user_getid() .' AND layout_id = '. $layout_id ." AND name = '". db_escape_string($name) ."' AND content_id = $instance_id";
                db_query($sql);
                echo db_error();
            }
            break;
        case 'maximize':
            if ($name) {
                $sql = "UPDATE layouts_contents SET is_minimized = 0 WHERE owner_type = 'u' AND owner_id = ". user_getid() .' AND layout_id = '. $layout_id ." AND name = '". db_escape_string($name) ."' AND content_id = $instance_id";
                db_query($sql);
                echo db_error();
            }
            break;
        case 'preferences':
            if ($name) {
                $sql = "UPDATE layouts_contents SET display_preferences = 1, is_minimized = 0 WHERE owner_type = 'u' AND owner_id = ". user_getid() ." AND name = '". db_escape_string($name) ."' AND content_id = $instance_id";
                db_query($sql);
                echo db_error();
            }
            break;
        default:
            $keys = array_keys($_REQUEST);
            foreach($keys as $key) {
                if (preg_match('`widgetlayout_col_\d+`', $key)) {
                    
                    function array_diff_names($tab1, $tab2) {
                        $diff = array();
                        foreach($tab1 as $e1) {
                            $found = false;
                            reset($tab2);
                            while(!$found && list(,$e2) = each($tab2)) {
                                $found = !count(array_diff($e1, $e2));
                            }
                            if (!$found) {
                                $diff[] = $e1;
                            }
                        }
                        return $diff;
                    }
                    
                    $split = explode('_', $key);
                    $column_id = (int)$split[count($split)-1];
                    
                    $names = array();
                    foreach($request->get($key) as $name) {
                        list($name, $id) = explode('-', $name);
                        $names[] = array($id, db_escape_string($name));
                    }
                    
                    //Compute differences
                    $originals = array();
                    $sql = "SELECT * FROM layouts_contents WHERE owner_type = 'u' AND owner_id = ". user_getid() .' AND column_id = '. $column_id .' ORDER BY rank';
                    echo $sql;
                    $res = db_query($sql);
                    echo db_error();
                    while($data = db_fetch_array($res)) {
                        $originals[] = array($data['content_id'], db_escape_string($data['name']));
                    }
                    
                    //delete removed contents
                    $deleted_names = array_diff_names($originals, $names);
                    if (count($deleted_names)) {
                        $_and = '';
                        foreach($deleted_names as $id => $name) {
                            if ($_and) {
                                $_and .= ' OR ';
                            } else {
                                $_and .= ' AND (';
                            }
                            $_and .= " (name = '".$name[1]."' AND content_id = ". $name[0] .") ";
                        }
                        $_and .= ')';
                        $sql = "UPDATE layouts_contents
                            SET column_id = 0
                            WHERE owner_type = 'u' 
                              AND owner_id = ". user_getid() .'
                              AND column_id = '. $column_id .
                              $_and;
                        $res = db_query($sql);
                    echo $sql;
                        echo db_error();
                    }
                    
                    //Insert new contents
                    $added_names = array_diff_names($names, $originals);
                    if (count($added_names)) {
                        $_and = '';
                        foreach($added_names as $name) {
                            if ($_and) {
                                $_and .= ' OR ';
                            } else {
                                $_and .= ' AND (';
                            }
                            $_and .= " (name = '".$name[1]."' AND content_id = ". $name[0] .") ";
                        }
                        $_and .= ')';
                        //old and new column must be part of the same layout
                        $sql = 'UPDATE layouts_contents
                            SET column_id = '. $column_id ." 
                            WHERE owner_type = 'u' 
                              AND owner_id = ". user_getid() .
                              $_and ."
                              AND layout_id = ". $layout_id;
                        $res = db_query($sql);
                    echo $sql;
                        echo db_error();
                    }
                    
                    
                    //Update ranks
                    $rank = 0;
                    $values = array();
                    foreach($names as $name) {
                        $sql = 'UPDATE layouts_contents SET rank = '. ($rank++) ." WHERE owner_type = 'u' AND owner_id = ". user_getid() .' AND column_id = '. $column_id ." AND name = '".$name[1]."' AND content_id = ". $name[0];
                    echo $sql;
                        db_query($sql);
                        echo db_error();
                    }
                }
            }
            break;
    }
}
if (!$request->isAjax()) {
    $GLOBALS['Response']->redirect('/my/');
}
?>
