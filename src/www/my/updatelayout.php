<?php
require_once('pre.php');

$request =& HTTPRequest::instance();
if ($layout_id = $request->get('layout_id') || $request->get('action') == 'preferences') {
    switch($request->get('action')) {
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
