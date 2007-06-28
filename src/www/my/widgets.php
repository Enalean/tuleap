<?php
require_once('pre.php');
require_once('my_utils.php');
require_once('common/widget/Widget.class.php');

$em =& EventManager::instance();
$em->processEvent('plugin_load_language_file', null);

function display_widgets($title, $tab, $used_widgets) {
    if (count($tab)) {
        echo '<tr class="boxtitle"><td colspan="2">'. $title .'</td></tr>';
        $i = 0;
        foreach($tab as $widget_name) {
            if ($widget = Widget::getInstance($widget_name)) {
                echo '<tr class="'. util_get_alt_row_color($i++) .'">';
                echo '<td>'. $widget->getTitle() . $widget->getInstallPreferences() .'</td>';
                echo '<td align="right">';
                if ($widget->isUnique() && in_array($widget_name, $used_widgets)) {
                    echo '<em>Already used</em>';
                    //echo '<input type="image" src="'. util_get_dir_image_theme() .'ic/trash.png" name="name['. $widget_name .'][remove]" value="Remove" />';
                } else {
                    echo '<input type="submit" name="name['. $widget_name .'][add]" value="Add" />';
                }
                echo '</td>';
                echo '</tr>';
            }
        }
    }
}

$request =& HTTPRequest::instance();
$layout_id = $request->get('layout_id');
if (user_isloggedin() && $layout_id) {
    
    $used_widgets = array();
    $sql = 'SELECT * FROM user_layouts_contents WHERE user_id = '. user_getid() .' AND layout_id = '. $layout_id .' AND content_id = 0 AND column_id <> 0';
    $res = db_query($sql);
    while($data = db_fetch_array($res)) {
        $used_widgets[] = $data['name'];
    }
    
    $title = $Language->getText('my_index', 'title', array(user_getrealname(user_getid()).' ('.user_getname().')'));
    my_header(array('title'=>$title));
    echo '<h3>Widgets</h3>';
    echo '<form action="updatelayout?action=widget&amp;layout_id='. $layout_id .'" method="POST">';
    echo '<table cellpadding="0" cellspacing="0">';
    display_widgets('CodeX Widgets', Widget::getCodeXWidgets(), $used_widgets);
    echo '<tr><td>&nbsp;</td><td></td></tr>';
    display_widgets('External Widgets', Widget::getExternalWidgets(), $used_widgets);
    echo '</table>';
    echo '</form>';
    site_footer(array());

} else {
    exit_not_logged_in();
}
?>
