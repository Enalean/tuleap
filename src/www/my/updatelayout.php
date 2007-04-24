<?php
require_once('pre.php');
require_once('common/widget/Widget.class.php');

$request =& HTTPRequest::instance();
if ($layout_id = (int)$request->get('layout_id') || $request->get('action') == 'preferences') {
    switch($request->get('action')) {
        case 'add':
            if ($request->exist('name') && $request->exist('layout_id')) {
                $name = $request->get('name');
                $name = array_pop(array_keys($name));
                if ($widget = Widget::getInstance($name)) {
                    //Search for the right column. (The first used)
                    $sql = "SELECT u.column_id AS id
                    FROM user_layouts_contents AS u
                    LEFT JOIN (SELECT r.rank AS rank, c.id as id
                    FROM layouts_rows AS r INNER JOIN layouts_rows_columns AS c
                    ON (c.layout_row_id = r.id)
                    WHERE r.layout_id = $layout_id) AS col
                    ON (u.column_id = col.id)
                    WHERE u.user_id = ". user_getid() ."
                      AND u.layout_id = $layout_id
                      AND u.column_id <> 0
                    ORDER BY col.rank, col.id";
                    $res = db_query($sql);
                    echo db_error();
                    $column_id = db_result($res, 0, 'id');
                    
                    //content_id
                    if ($widget->isUnique()) {
                        //unique widgets do not have content_id
                        $content_id = 0;
                    } else {
                        $content_id = $widget->create($request);
                    }
                    
                    //See if it already exists but not used
                    $sql = "SELECT * FROM user_layouts_contents 
                    WHERE user_id = ". user_getid() ."
                      AND layout_id = $layout_id 
                      AND column_id = 0 
                      AND name = '$name'";
                    $res = db_query($sql);
                    echo db_error();
                    if (db_numrows($res)) {
                        //Update
                        $sql = 'UPDATE user_layouts_contents
                            SET column_id = '. $column_id .' 
                            WHERE user_id = '. user_getid() ."
                              AND name = $names
                              AND layout_id = ". $layout_id;
                        $res = db_query($sql);
                        echo db_error();
                    } else {
                        //Insert
                        $sql = "INSERT INTO user_layouts_contents(user_id, layout_id, column_id, name, content_id, rank) 
                        SELECT user_id, layout_id, column_id, '$name', $content_id, rank - 1 
                        FROM user_layouts_contents 
                        WHERE user_id = ". user_getid() ." AND layout_id = $layout_id AND column_id = $column_id 
                        ORDER BY rank ASC
                        LIMIT 1";
                        db_query($sql);
                        echo db_error();
                    }
                }
            }
            break;
        case 'minimize':
            if ($request->exist('name')) {
                $sql = 'UPDATE user_layouts_contents SET is_minimized = 1 WHERE user_id = '. user_getid() .' AND layout_id = '. $layout_id ." AND name = '". db_escape_string($name) ."'";
                db_query($sql);
                echo db_error();
            }
            break;
        case 'maximize':
            if ($request->exist('name')) {
                $sql = 'UPDATE user_layouts_contents SET is_minimized = 0 WHERE user_id = '. user_getid() .' AND layout_id = '. $layout_id ." AND name = '". db_escape_string($name) ."'";
                db_query($sql);
                echo db_error();
            }
            break;
        case 'preferences':
            if ($request->exist('name')) {
                $sql = 'UPDATE user_layouts_contents SET display_preferences = 1 WHERE user_id = '. user_getid() ." AND name = '". db_escape_string($name) ."'";
                db_query($sql);
                echo db_error();
            }
            break;
        default:
            $keys = array_keys($_REQUEST);
            foreach($keys as $key) {
                if (preg_match('`widgetlayout_col_\d+`', $key)) {
                    $split = explode('_', $key);
                    $column_id = (int)$split[count($split)-1];
                    
                    $names = array();
                    foreach($request->get($key) as $name) {
                        $names[] = db_escape_string($name);
                    }
                    
                    //Compute differences
                    $originals = array();
                    $sql = 'SELECT * FROM user_layouts_contents WHERE user_id = '. user_getid() .' AND column_id = '. $column_id .' ORDER BY rank';
                    $res = db_query($sql);
                    echo db_error();
                    while($data = db_fetch_array($res)) {
                        $originals[] = db_escape_string($data['name']);
                    }
                    
                    //delete removed contents
                    $deleted_names = array_diff($originals, $names);
                    if (count($deleted_names)) {
                        $sql = 'UPDATE user_layouts_contents
                            SET column_id = 0
                            WHERE user_id = '. user_getid() .'
                              AND column_id = '. $column_id ."
                              AND name IN ('". implode("', '", $deleted_names) ."')";
                        $res = db_query($sql);
                        echo db_error();
                    }
                    
                    //Insert new contents
                    $added_names = array_diff($names, $originals);
                    if (count($added_names)) {
                        //old and new column must be part of the same layout
                        $sql = 'UPDATE user_layouts_contents
                            SET column_id = '. $column_id .' 
                            WHERE user_id = '. user_getid() ."
                              AND name IN ('". implode("', '", $added_names) ."')
                              AND layout_id = ". $layout_id;
                        $res = db_query($sql);
                        echo db_error();
                    }
                    
                    
                    //Update ranks
                    $rank = 0;
                    $values = array();
                    foreach($names as $name) {
                        $sql = 'UPDATE user_layouts_contents SET rank = '. ($rank++) .' WHERE user_id = '. user_getid() .' AND column_id = '. $column_id ." AND name = '". $name ."'";
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
