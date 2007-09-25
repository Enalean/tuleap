<?php

require_once('common/widget/WidgetLayout.class.php');
require_once('common/widget/Widget.class.php');

/**
* WidgetLayoutManager
* 
* Manage layouts for users, groups and homepage
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class WidgetLayoutManager {
    var $OWNER_TYPE_USER  = 'u';
    var $OWNER_TYPE_GROUP = 'g';
    var $OWNER_TYPE_HOME  = 'h';
    
    /**
    * displayLayout
    * 
    * Display the default layout for the "owner". It my be the home page, the project summary page or /my/ page.
    *
    * @param  owner_id  
    * @param  owner_type  
    */
    function displayLayout($owner_id, $owner_type) {
        $sql = "SELECT l.* 
            FROM layouts AS l INNER JOIN owner_layouts AS o ON(l.id = o.layout_id) 
            WHERE o.owner_type = '". $owner_type ."' 
              AND o.owner_id = ". $owner_id ." 
              AND o.is_default = 1
        ";
        $req = db_query($sql);
        if ($data = db_fetch_array($req)) {
            $readonly = $this->_currentUserCanUpdateLayout($owner_id, $owner_type);
            if (!$readonly) {
                echo '<a href="widgets.php?layout_id='. $data['id'] .'">[Add widget]</a>';
            }
            $layout =& new WidgetLayout($data['id'], $data['name'], $data['description'], $data['scope']);
            $sql = 'SELECT * FROM layouts_rows WHERE layout_id = '. $layout->id .' ORDER BY rank';
            $req_rows = db_query($sql);
            while ($data = db_fetch_array($req_rows)) {
                $row =& new WidgetLayout_Row($data['id'], $data['rank']);
                $sql = 'SELECT * FROM layouts_rows_columns WHERE layout_row_id = '. $row->id;
                $req_cols = db_query($sql);
                while ($data = db_fetch_array($req_cols)) {
                    $col =& new WidgetLayout_Row_Column($data['id'], $data['width']);
                    $sql = "SELECT * FROM layouts_contents WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id .' AND column_id = '. $col->id .' ORDER BY rank';
                    $req_content = db_query($sql);
                    while ($data = db_fetch_array($req_content)) {
                        $c =& Widget::getInstance($data['name']);
                        if ($c !== null) {
                            $c->loadContent($data['content_id']);
                            $col->add($c, $data['is_minimized'], $data['display_preferences']);
                        }
                        unset($c);
                    }
                    $row->add($col);
                    unset($col);
                }
                $layout->add($row);
                unset($row);
            }
            $layout->display($readonly);
        }
    }
    
    /**
    * _currentUserCanUpdateLayout
    * 
    * @return boolean true if the user dan uppdate the layout (add/remove widget, collapse, set preferences, ...)
    * @param  owner_id  
    * @param  owner_type  
    */
    function _currentUserCanUpdateLayout($owner_id, $owner_type) {
        $readonly = true;
        $request =& HTTPRequest::instance();
        switch ($owner_type) {
            case $this->OWNER_TYPE_USER:
                if (user_getid() == $owner_id) { //Current user can only update its own /my/ page
                    $readonly = false;
                }
                break;
            case $this->OWNER_TYPE_GROUP:
                if (user_is_super_user() || user_ismember($request->get('group_id'), 'A')) {
                    $readonly = false;
                }
                //Only project admin
                break;
            case $this->OWNER_TYPE_HOME:
                //Only site admin
                break;
            default:
                break;
        }
        return $readonly;
    }
    
    /**
    * displayAvailableWidgets
    * 
    * Display all widget that the user can add to the layout
    *
    * @param  owner_id 
    * @param  owner_type 
    * @param  layout_id 
    */
    function displayAvailableWidgets($owner_id, $owner_type, $layout_id) {
        $used_widgets = array();
        $sql = "SELECT * 
        FROM layouts_contents 
        WHERE owner_type = '". $owner_type ."' 
        AND owner_id = ". $owner_id .' 
        AND layout_id = '. $layout_id .' 
        AND content_id = 0 AND column_id <> 0';
        $res = db_query($sql);
        while($data = db_fetch_array($res)) {
            $used_widgets[] = $data['name'];
        }
        echo '<h3>Widgets</h3>';
        echo '<form action="updatelayout.php?action=widget&amp;layout_id='. $layout_id .'" method="POST">';
        echo '<table cellpadding="0" cellspacing="0">';
        $this->_displayWidgetsSelectionForm('CodeX Widgets', Widget::getCodeXWidgets(), $used_widgets);
        echo '<tr><td>&nbsp;</td><td></td></tr>';
        $this->_displayWidgetsSelectionForm('External Widgets', Widget::getExternalWidgets(), $used_widgets);
        echo '</table>';
        echo '</form>';
    }
    
    /**
    * _displayWidgetsSelectionForm
    *
    * @param  title  
    * @param  widgets  
    * @param  used_widgets  
    */
    function _displayWidgetsSelectionForm($title, $widgets, $used_widgets) {
        if (count($widgets)) {
            echo '<tr class="boxtitle"><td colspan="2">'. $title .'</td></tr>';
            $i = 0;
            foreach($widgets as $widget_name) {
                if ($widget = Widget::getInstance($widget_name)) {
                    echo '<tr class="'. util_get_alt_row_color($i++) .'">';
                    echo '<td>'. $widget->getTitle() . $widget->getInstallPreferences() .'</td>';
                    echo '<td align="right">';
                    if ($widget->isUnique() && in_array($widget_name, $used_widgets)) {
                        echo '<em>Already used</em>';
                    } else {
                        echo '<input type="submit" name="name['. $widget_name .'][add]" value="Add" />';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            }
        }
    }
    
    /**
    * addWidget
    *
    * @param  owner_id  
    * @param  owner_type  
    * @param  layout_id  
    * @param  name  
    * @param  widget  
    * @param  request  
    */
    function addWidget($owner_id, $owner_type, $layout_id, $name, &$widget, &$request) {
        //Search for the right column. (The first used)
        $sql = "SELECT u.column_id AS id
        FROM layouts_contents AS u
        LEFT JOIN (SELECT r.rank AS rank, c.id as id
        FROM layouts_rows AS r INNER JOIN layouts_rows_columns AS c
        ON (c.layout_row_id = r.id)
        WHERE r.layout_id = $layout_id) AS col
        ON (u.column_id = col.id)
        WHERE u.owner_type = '". $owner_type ."' 
          AND u.owner_id = ". $owner_id ."
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
        WHERE owner_type = '". $owner_type ."'
          AND owner_id = ". $owner_id ."
          AND layout_id = $layout_id 
          AND name = '$name'";
        $res = db_query($sql);
        echo db_error();
        if (db_numrows($res) && !$widget->isUnique() && db_result($res, 0, 'column_id') == 0) {
            //search for rank
            $sql = "SELECT min(rank) - 1 AS rank FROM layouts_contents WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id ." AND layout_id = $layout_id AND column_id = $column_id ";
            $res = db_query($sql);
            echo db_error();
            $rank = db_result($res, 0, 'rank');
            
            //Update
            $sql = "UPDATE layouts_contents
                SET column_id = ". $column_id .", rank = $rank
                WHERE owner_type = '". $owner_type ."'
                  AND owner_id = ". $owner_id ."
                  AND name = '$name'
                  AND layout_id = ". $layout_id;
            $res = db_query($sql);
            echo db_error();
        } else {
            //Insert
            $sql = "INSERT INTO layouts_contents(owner_type, owner_id, layout_id, column_id, name, content_id, rank) 
            SELECT owner_type, owner_id, layout_id, column_id, '$name', $content_id, rank - 1 
            FROM layouts_contents 
            WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id ." AND layout_id = $layout_id AND column_id = $column_id 
            ORDER BY rank ASC
            LIMIT 1";
            db_query($sql);
            echo db_error();
        }
    }
    
    /**
    * removeWidget
    *
    * @param  owner_id  
    * @param  owner_type  
    * @param  layout_id  
    * @param  name  
    * @param  instance_id  
    */
    function removeWidget($owner_id, $owner_type, $layout_id, $name, $instance_id, &$widget) {
        $sql = "DELETE FROM layouts_contents WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id ." AND layout_id = $layout_id AND name = '$name' AND content_id = $instance_id";
        db_query($sql);
        if (!db_error()) {
            $widget->destroy($instance_id);
        }
    }
    
    /**
    * mimizeWidget
    *
    * @param  owner_id  
    * @param  owner_type  
    * @param  layout_id  
    * @param  name  
    * @param  instance_id  
    */
    function mimizeWidget($owner_id, $owner_type, $layout_id, $name, $instance_id) {
        $sql = "UPDATE layouts_contents SET is_minimized = 1 WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id ." AND layout_id = ". $layout_id ." AND name = '". db_escape_string($name) ."' AND content_id = $instance_id";
        db_query($sql);
        echo db_error();
    }
    
    /**
    * maximizeWidget
    *
    * @param  owner_id  
    * @param  owner_type  
    * @param  layout_id  
    * @param  name  
    * @param  instance_id  
    */
    function maximizeWidget($owner_id, $owner_type, $layout_id, $name, $instance_id) {
        $sql = "UPDATE layouts_contents SET is_minimized = 0 WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id ." AND layout_id = ". $layout_id ." AND name = '". db_escape_string($name) ."' AND content_id = $instance_id";
        db_query($sql);
        echo db_error();
    }
    
    /**
    * displayWidgetPreferences
    *
    * @param  owner_id  
    * @param  owner_type  
    * @param  layout_id  
    * @param  name  
    * @param  instance_id  
    */
    function displayWidgetPreferences($owner_id, $owner_type, $layout_id, $name, $instance_id) {
        $sql = "UPDATE layouts_contents SET display_preferences = 1, is_minimized = 0 WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id ." AND layout_id = ". $layout_id ." AND name = '". db_escape_string($name) ."' AND content_id = $instance_id";
        db_query($sql);
        echo db_error();
    }
    
    /**
    * hideWidgetPreferences
    *
    * @param  owner_id  
    * @param  owner_type  
    * @param  layout_id  
    * @param  name  
    * @param  instance_id  
    */
    function hideWidgetPreferences($owner_id, $owner_type, $layout_id, $name, $instance_id) {
        $sql = "UPDATE layouts_contents SET display_preferences = 0 WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id ." AND layout_id = ". $layout_id ." AND name = '". db_escape_string($name) ."' AND content_id = $instance_id";
        db_query($sql);
        echo db_error();
    }
    
    /**
    * reorderLayout
    *
    * @param  owner_id  
    * @param  owner_type  
    * @param  layout_id  
    * @param  name  
    * @param  instance_id  
    */
    function reorderLayout($owner_id, $owner_type, $layout_id, &$request) {
        $keys = array_keys($_REQUEST);
        foreach($keys as $key) {
            if (preg_match('`widgetlayout_col_\d+`', $key)) {
                
                
                $split = explode('_', $key);
                $column_id = (int)$split[count($split)-1];
                
                $names = array();
                foreach($request->get($key) as $name) {
                    list($name, $id) = explode('-', $name);
                    $names[] = array($id, db_escape_string($name));
                }
                
                //Compute differences
                $originals = array();
                $sql = "SELECT * FROM layouts_contents WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id ." AND layout_id = ". $column_id .' ORDER BY rank';
                echo $sql;
                $res = db_query($sql);
                echo db_error();
                while($data = db_fetch_array($res)) {
                    $originals[] = array($data['content_id'], db_escape_string($data['name']));
                }
                
                //delete removed contents
                $deleted_names = $this->_array_diff_names($originals, $names);
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
                        WHERE owner_type = '". $owner_type ."' 
                          AND owner_id = ". $owner_id .'
                          AND column_id = '. $column_id .
                          $_and;
                    $res = db_query($sql);
                echo $sql;
                    echo db_error();
                }
                
                //Insert new contents
                $added_names = $this->_array_diff_names($names, $originals);
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
                        WHERE owner_type = '". $owner_type ."' 
                          AND owner_id = ". $owner_id .
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
                    $sql = 'UPDATE layouts_contents SET rank = '. ($rank++) ." WHERE owner_type = '". $owner_type ."' AND owner_id = ". $owner_id .' AND column_id = '. $column_id ." AND name = '".$name[1]."' AND content_id = ". $name[0];
                echo $sql;
                    db_query($sql);
                    echo db_error();
                }
            }
        }
    }
    
    /**
    * compute the differences between two arrays
    */
    function _array_diff_names($tab1, $tab2) {
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
}
?>
