<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
* WidgetLayoutManager
*
* Manage layouts for users, groups and homepage
*/
class WidgetLayoutManager {
    const OWNER_TYPE_USER  = 'u';
    const OWNER_TYPE_GROUP = 'g';
    const OWNER_TYPE_HOME  = 'h';

    /**
     * @return int
     */
    function getDefaultLayoutId($owner_id, $owner_type) {
        $owner_type = db_es($owner_type);
        $owner_id   = db_ei($owner_id);

        $sql = "SELECT l.*
            FROM layouts AS l INNER JOIN owner_layouts AS o ON(l.id = o.layout_id)
            WHERE o.owner_type = '". $owner_type ."'
              AND o.owner_id = ". $owner_id ."
              AND o.is_default = 1
        ";
        $req = db_query($sql);
        if ($data = db_fetch_array($req)) {
            return $data['id'];
        }
        return null;
    }

    /**
    * displayLayout
    *
    * Display the default layout for the "owner". It my be the home page, the project summary page or /my/ page.
    *
    * @param  owner_id
    * @param  owner_type
    */
    public function displayLayout($owner_id, $owner_type)
    {
        $owner_type = db_es($owner_type);
        $owner_id   = db_ei($owner_id);

        $sql = "SELECT l.*
            FROM layouts AS l INNER JOIN owner_layouts AS o ON(l.id = o.layout_id)
            WHERE o.owner_type = '". $owner_type ."'
              AND o.owner_id = ". $owner_id ."
              AND o.is_default = 1
        ";
        $req = db_query($sql);
        if ($data = db_fetch_array($req)) {
            $readonly = !$this->_currentUserCanUpdateLayout($owner_id, $owner_type);
            if (!$readonly) {
                echo '<a href="/widgets/widgets.php?owner='. $owner_type.$owner_id .'&amp;layout_id='. $data['id'] .'" class="layout_manager_customize btn btn-small"><i class="icon-cog"></i> '. $GLOBALS['Language']->getText('widget_add', 'link_add') .'</a>';
            } else if ($owner_type === self::OWNER_TYPE_GROUP) {
                echo '<br />';
            }
            $layout = new WidgetLayout($data['id'], $data['name'], $data['description'], $data['scope']);
            $sql = 'SELECT * FROM layouts_rows WHERE layout_id = '. db_ei($layout->id) .' ORDER BY rank';
            $req_rows = db_query($sql);
            while ($data = db_fetch_array($req_rows)) {
                $row = new WidgetLayout_Row($data['id'], $data['rank']);
                $sql = 'SELECT * FROM layouts_rows_columns WHERE layout_row_id = '. db_ei($row->id);
                $req_cols = db_query($sql);
                while ($data = db_fetch_array($req_cols)) {
                    $col = new WidgetLayout_Row_Column($data['id'], $data['width']);
                    $sql = "SELECT * FROM layouts_contents WHERE owner_type = '". db_es($owner_type) ."' AND owner_id = ". db_ei($owner_id) .' AND column_id = '. db_ei($col->id) .' ORDER BY rank';
                    $req_content = db_query($sql);
                    while ($data = db_fetch_array($req_content)) {
                        $c = Widget::getInstance($data['name']);
                        if ($c && $c->isAvailable()) {
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
            $csrk_token = new CSRFSynchronizerToken('widget_management');
            $layout->display($readonly, $owner_id, $owner_type, $csrk_token);
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
        $request = HTTPRequest::instance();
        switch ($owner_type) {
        case self::OWNER_TYPE_USER:
                if (user_getid() == $owner_id) { //Current user can only update its own /my/ page
                    $readonly = false;
                }
                break;
            case self::OWNER_TYPE_GROUP:
                if (user_is_super_user() || user_ismember($request->get('group_id'), 'A')) { //Only project admin
                    $readonly = false;
                }
                break;
            case self::OWNER_TYPE_HOME:
                //Only site admin
                break;
            default:
                break;
        }
        return !$readonly;
    }
    /**
    * createDefaultLayoutForUser
    *
    * Create the first layout for the user and add some initial widgets:
    * - MyArtifacts
    * - MyProjects
    * - MyBookmarks
    * - MyMonitoredFP
    * - MyMonitoredForums
    * - and widgets of plugins if they want to listen to the event default_widgets_for_new_owner
    *
    * @param  owner_id The id of the newly created user
    */
    function createDefaultLayoutForUser($owner_id) {
        $service_manager = ServiceManager::instance();
        $owner_id   = db_ei($owner_id);
        $owner_type = db_es(self::OWNER_TYPE_USER);
        $sql = "INSERT INTO owner_layouts(layout_id, is_default, owner_id, owner_type) VALUES (1, 1, $owner_id, '$owner_type')";
        if (db_query($sql)) {

            $sql = "INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank) VALUES ";
            $sql .= "($owner_id, '$owner_type', 1, 1, 'myprojects', 0)";
            $sql .= ",($owner_id, '$owner_type', 1, 1, 'mybookmarks', 1)";
            if ($service_manager->isServiceAvailableAtSiteLevelByShortName(Service::FORUM)) {
                $sql .= ",($owner_id, '$owner_type', 1, 1, 'mymonitoredforums', 2)";
            }
            if ($service_manager->isServiceAvailableAtSiteLevelByShortName(Service::FILE)) {
                $sql .= ",($owner_id, '$owner_type', 1, 2, 'mymonitoredfp', 20)";
            }

            $em = EventManager::instance();
            $widgets = array();
            $em->processEvent('default_widgets_for_new_owner', array('widgets' => &$widgets, 'owner_type' => $owner_type));
            foreach($widgets as $widget) {
                $sql .= ",($owner_id, '$owner_type', 1, ". db_ei($widget['column']) .", '". db_es($widget['name']) ."', ". db_ei($widget['rank']) .")";
            }
            db_query($sql);
        }
        echo db_error();
    }

    /**
    * createDefaultLayoutForProject
    *
    * Create the first layout for a new project, based on its parent template.
    * Add some widgets based also on its parent configuration and on its service configuration.
    *
    * @param  group_id  the id of the newly created project
    * @param  template_id  the id of the project template
    */
    function createDefaultLayoutForProject($group_id, $template_id) {
        $pm = ProjectManager::instance();
        $pm->clear($group_id);
        $project     = $pm->getProject($group_id);
        $group_id    = db_ei($group_id);
        $template_id = db_ei($template_id);
        $sql = "INSERT INTO owner_layouts(layout_id, is_default, owner_id, owner_type)
        SELECT layout_id, is_default, $group_id, owner_type
        FROM owner_layouts
        WHERE owner_type = '". db_es(self::OWNER_TYPE_GROUP) ."'
          AND owner_id = $template_id
        ";
        if (db_query($sql)) {
            $sql = "SELECT layout_id, column_id, name, rank, is_minimized, is_removed, display_preferences, content_id
            FROM layouts_contents
            WHERE owner_type = '". db_es(self::OWNER_TYPE_GROUP) ."'
              AND owner_id = $template_id
            ";
            if ($req = db_query($sql)) {
                while($data = db_fetch_array($req)) {
                    $w = Widget::getInstance($data['name']);
                    if ($w) {
                        $w->setOwner($template_id, self::OWNER_TYPE_GROUP);
                        if ($w->canBeUsedByProject($project)) {
                            $content_id = $w->cloneContent($w->content_id, $group_id, self::OWNER_TYPE_GROUP);
                            $sql = "INSERT INTO layouts_contents(owner_id, owner_type, content_id, layout_id, column_id, name, rank, is_minimized, is_removed, display_preferences)
                            VALUES (". $group_id .", '". db_es(self::OWNER_TYPE_GROUP) ."', ". db_ei($content_id) .", ". db_ei($data['layout_id']) .", ". db_ei($data['column_id']) .", '". db_es($data['name']) ."', ". db_ei($data['rank']) .", ". db_ei($data['is_minimized']) .", ". db_ei($data['is_removed']) .", ". db_ei($data['display_preferences']) .")
                            ";
                            db_query($sql);
                            echo db_error();
                        }
                    }
                }
            }
        }
        echo db_error();
    }

    /**
     * displayAvailableWidgets
     *
     * Display all widget that the user can add to the layout
     */
    public function displayAvailableWidgets($owner_id, $owner_type, $layout_id, CSRFSynchronizerToken $csrf_token)
    {
        $used_widgets = array();
        $sql = "SELECT *
        FROM layouts_contents
        WHERE owner_type = '". db_es($owner_type) ."'
        AND owner_id = ". db_ei($owner_id) .'
        AND layout_id = '. db_ei($layout_id) .'
        AND content_id = 0 AND column_id <> 0';
        $res = db_query($sql);
        while($data = db_fetch_array($res)) {
            $used_widgets[] = $data['name'];
        }
        echo '<ul class="widget_toolbar">';
        $parameters = array(
            'owner' => $owner_type.$owner_id
        );
        if ($update_layout = HTTPRequest::instance()->get('update') == 'layout') {
            echo '<li><a href="'. str_replace('&update=layout', '', $_SERVER['REQUEST_URI']) .'">'. $GLOBALS['Language']->getText('widget_add', 'title') .'</a></li>';
            echo '<li class="current"><a href="'. $_SERVER['REQUEST_URI'] .'">'. $GLOBALS['Language']->getText('widget_layout', 'title') .'</a></li>';
            $parameters['action'] = 'layout';
        } else {
            echo '<li class="current"><a href="'. $_SERVER['REQUEST_URI'] .'">'. $GLOBALS['Language']->getText('widget_add', 'title') .'</a></li>';
            echo '<li><a href="'. $_SERVER['REQUEST_URI'] .'&amp;update=layout">'. $GLOBALS['Language']->getText('widget_layout', 'title') .'</a></li>';
            $parameters['action']    = 'widget';
            $parameters['layout_id'] = $layout_id;
        }
        echo '</ul>';

        if ($update_layout) {
            $this->displayUpdateLayout($layout_id, $parameters, $csrf_token);
        } else {
            $this->displayAddWidgetForm($owner_id, $owner_type, $parameters, $csrf_token, $used_widgets);
        }
    }

    function updateLayout($owner_id, $owner_type, $layout, $custom_layout) {
        $sql = "SELECT l.*
            FROM layouts AS l INNER JOIN owner_layouts AS o ON(l.id = o.layout_id)
            WHERE o.owner_type = '". db_es($owner_type) ."'
              AND o.owner_id = ". db_ei($owner_id) ."
              AND o.is_default = 1
        ";
        $req = db_query($sql);
        if ($data = db_fetch_array($req)) {
            if ($this->_currentUserCanUpdateLayout($owner_id, $owner_type)) {
                $old_scope         = $data['scope'];
                $old_layout_id = $data['id'];
                $new_layout_id = null;
                if ($layout == '-1') {
                    if (! is_array($custom_layout)) {
                        return;
                    }

                    //Create a new layout based on the custom layout structure defined by the user
                    $rows = array();
                    foreach($custom_layout as $widths) {
                        $row = array();
                        $cols = explode(',', $widths);
                        foreach($cols as $col) {
                            if ($width = (int)$col) {
                                $row[] = $width;
                            }
                        }
                        if (count($row)) {
                            $rows[] = $row;
                        }
                    }
                    //If the structure contains at least one column, create a new layout
                    if (count($rows)) {
                        $sql = "INSERT INTO layouts(name, description, scope)
                                VALUES ('custom', '', 'P')";
                        if (db_query($sql)) {
                            $sql = "SELECT LAST_INSERT_ID() AS id";
                            if ($res = db_query($sql)) {
                                if ($data = db_fetch_array($res)) {
                                    $new_layout_id = $data['id'];

                                    //Create rows & columns
                                    $rank = 0;
                                    foreach($rows as $cols) {
                                        $sql = "INSERT INTO layouts_rows(layout_id, rank)
                                                VALUES (" . db_ei($new_layout_id) .", ". db_ei($rank++) .")";
                                        if (db_query($sql)) {
                                            $sql = "SELECT LAST_INSERT_ID() AS id";
                                            if ($res = db_query($sql)) {
                                                if ($data = db_fetch_array($res)) {
                                                    $row_id = $data['id'];
                                                    foreach($cols as $width) {
                                                        $sql = "INSERT INTO layouts_rows_columns(layout_row_id, width)
                                                                VALUES (". db_ei($row_id) .", ". db_ei($width) .")";
                                                        db_query($sql);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $new_layout_id = $layout;
                }

                if ($new_layout_id) {
                    //Retrieve columns of old layout
                    $old = $this->_retrieveStructureOfLayout($old_layout_id);

                    //Retrieve columns of new layout
                    $new = $this->_retrieveStructureOfLayout($new_layout_id);

                    //Switch content from old columns to knew columns
                    $last_new_col_id = null;
                    reset($new['columns']);
                    foreach($old['columns'] as $old_col) {
                        if (list(,$new_col) = each($new['columns'])) {
                            $last_new_col_id = $new_col['id'];
                        }
                        $sql = "UPDATE layouts_contents
                                SET layout_id  = ". db_ei($new_layout_id) ."
                                  , column_id  = ". db_ei($last_new_col_id) ."
                                WHERE owner_type = '". db_es($owner_type) ."'
                                  AND owner_id   = ". db_ei($owner_id) ."
                                  AND layout_id  = ". db_ei($old_layout_id) ."
                                  AND column_id  = ". db_ei($old_col['id']);
                        db_query($sql);
                    }
                    $sql = "UPDATE owner_layouts
                                SET layout_id  = ". db_ei($new_layout_id) ."
                                WHERE owner_type = '". db_es($owner_type) ."'
                                  AND owner_id   = ". db_ei($owner_id) ."
                                  AND layout_id  = ". db_ei($old_layout_id);
                    db_query($sql);

                    //If the old layout is custom remove it
                    if ($old_scope != 'S') {
                        $structure = $this->_retrieveStructureOfLayout($old_layout_id);
                        foreach($structure['rows'] as $row) {
                            $sql = "DELETE FROM layouts_rows
                                    WHERE id  = ". db_ei($row['id']);
                            db_query($sql);
                            $sql = "DELETE FROM layouts_rows_columns
                                    WHERE layout_row_id  = ".db_ei($row['id']);
                            db_query($sql);
                        }
                        $sql = "DELETE FROM layouts
                                WHERE id  = ". db_ei($old_layout_id);
                        db_query($sql);
                    }

                }
            }
        }
        $this->feedback($owner_id, $owner_type);
    }

    function _retrieveStructureOfLayout($layout_id) {
        $structure = array('rows' => array(), 'columns' => array());
        $sql = 'SELECT * FROM layouts_rows WHERE layout_id = '. db_ei($layout_id) .' ORDER BY rank';
        $req_rows = db_query($sql);
        while ($row = db_fetch_array($req_rows)) {
            $structure['rows'][] = $row;
            $sql = 'SELECT * FROM layouts_rows_columns WHERE layout_row_id = '. db_ei($row['id']) .' ORDER BY id';
            $req_cols = db_query($sql);
            while ($col = db_fetch_array($req_cols)) {
                $structure['columns'][] = $col;
            }
        }
        return $structure;
    }

    /**
    * _displayWidgetsSelectionForm
    *
    * @param  title
    * @param  widgets
    * @param  used_widgets
    */
    function _displayWidgetsSelectionForm($owner_id, $title, $widgets, $used_widgets) {
        $hp = Codendi_HTMLPurifier::instance();
        $additionnal_html = '';
        if (count($widgets)) {
            echo '<tr><td colspan="2">';
            $categs = $this->getCategories($widgets);
            $widget_rows = array();
            if (count($categs)) {
                foreach($categs as $c => $ws) {
                    $widget_rows[$c] = '<a class="widget-categ-switcher" href="#widget-categ-'. $c .'"><span>'.   $hp->purify($GLOBALS['Language']->getText('widget_categ_label', $c), CODENDI_PURIFIER_CONVERT_HTML)  .'</span></a>';
                }
                uksort($widget_rows, 'strnatcasecmp');
                echo '<ul id="widget-categories">';
                foreach($widget_rows as $row) {
                    echo '<li>'. $row .'</li>';
                }
                echo '</ul>';
                echo '</td></tr>';
            } else {
                echo '</td></tr>';
                foreach($widgets as $widget_name) {
                    if ($widget = Widget::getInstance($widget_name)) {
                        if ($widget->isAvailable()) {
                            $row = '';
                            $row .= '<td>'. $widget->getTitle() . $widget->getInstallPreferences() .'</td>';
                            $row .= '<td align="right">';
                            if ($widget->isUnique() && in_array($widget_name, $used_widgets)) {
                                $row .= '<em>'. $GLOBALS['Language']->getText('widget_add', 'already_used') .'</em>';
                            } else {
                                $row .= '<input type="submit" name="name['. $widget_name .'][add]" value="'. $GLOBALS['Language']->getText('widget_add', 'add') .'" />';
                            }
                            $row .= '</td>';
                            $widget_rows[$widget->getTitle()] = $row;
                        }
                    }
                }
                $i = 0;
                foreach($widget_rows as $row) {
                    echo '<tr class="'. (count($widget_rows) ? '' : util_get_alt_row_color($i++)) .'">'. $row .'</tr>';
                }
            }
            if (count($categs)) {
                foreach($categs as $c => $ws) {
                    $i = 0;
                    $widget_rows = array();
                    foreach($ws as $widget_name => $widget) {
                        $row = '';
                        $row .= '<div class="widget-preview '. $widget->getPreviewCssClass() .'">';
                        $row .= '<strong>'. $widget->getTitle()  .'</strong>';
                        $row .= '<p>'. $widget->getDescription() .'</p>';
                        if ($widget->isInstallAllowed()) {
                            $row .= $widget->getInstallPreferences();
                            $row .= '</div><div style="text-align:right; border-bottom:1px solid #ddd; padding-bottom:10px; margin-bottom:20px;">';
                            if ($widget->isUnique() && in_array($widget_name, $used_widgets)) {
                                $row .= '<em>'. $GLOBALS['Language']->getText('widget_add', 'already_used') .'</em>';
                            } else {
                                $row .= '<input type="submit" name="name['. $widget_name .'][add]" value="'. $GLOBALS['Language']->getText('widget_add', 'add') .'" />';
                            }
                            $row .= '</div>';
                        } else {
                            $row .= $widget->getInstallNotAllowedMessage();
                            $row .= '</div><div style="text-align:right; border-bottom:1px solid #ddd; padding-bottom:10px; margin-bottom:20px;"></div>';
                        }
                        $widget_rows[$widget->getTitle()] = $row;
                    }
                    uksort($widget_rows, 'strnatcasecmp');
                    $additionnal_html .= '<div id="widget-categ-'. $c .'"><h4 class="boxtitle">'. $hp->purify($GLOBALS['Language']->getText('widget_categ_label', $c), CODENDI_PURIFIER_CONVERT_HTML) .'</h4>';
                    foreach($widget_rows as $row) {
                        $additionnal_html .= $row;
                    }
                    $additionnal_html .= '</div>';
                }
            }
        }
        return $additionnal_html;
    }
    function getCategories($widgets) {
        $categ = array();
        foreach($widgets as $widget_name) {
            if ($widget = Widget::getInstance($widget_name)) {
                if ($widget->isAvailable()) {
                    $cs = explode(',', $widget->getCategory());
                    foreach($cs as $c) {
                        if ($c = trim($c)) {
                            if (!isset($categ[$c])) {
                                $categ[$c] = array();
                            }
                            $categ[$c][$widget_name] = $widget;
                        }
                    }
                }
            }
        }
        return $categ;
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
        WHERE r.layout_id = " . db_ei($layout_id) . ") AS col
        ON (u.column_id = col.id)
        WHERE u.owner_type = '". db_es($owner_type) ."'
          AND u.owner_id = ". db_ei($owner_id) ."
          AND u.layout_id = " . db_ei($layout_id) . "
          AND u.column_id <> 0
        ORDER BY col.rank, col.id";
        $res = db_query($sql);
        echo db_error();
        $column_id = db_result($res, 0, 'id');
        if (!$column_id) {
            $sql = "SELECT r.rank AS rank, c.id as id
                    FROM layouts_rows AS r
                         INNER JOIN layouts_rows_columns AS c
                         ON (c.layout_row_id = r.id)
                    WHERE r.layout_id = " . db_ei($layout_id) . "
                    ORDER BY rank, id";
            $res = db_query($sql);
            $column_id = db_result($res, 0, 'id');
        }

        //content_id
        if ($widget->isUnique()) {
            //unique widgets do not have content_id
            $content_id = 0;
        } else {
            $content_id = $widget->create($request);
        }

        //See if it already exists but not used
        $sql = "SELECT column_id FROM layouts_contents
        WHERE owner_type = '". db_es($owner_type) ."'
          AND owner_id = ". db_ei($owner_id) ."
          AND layout_id = " . db_ei($layout_id) . "
          AND name = '". db_es($name) . "'";
        $res = db_query($sql);
        echo db_error();
        if (db_numrows($res) && !$widget->isUnique() && db_result($res, 0, 'column_id') == 0) {
            //search for rank
            $sql = "SELECT min(rank) - 1 AS rank FROM layouts_contents WHERE owner_type = '". db_es($owner_type) ."' AND owner_id = ". db_ei($owner_id) ." AND layout_id = " . db_ei($layout_id) . " AND column_id = " . db_ei($column_id);
            $res = db_query($sql);
            echo db_error();
            $rank = db_result($res, 0, 'rank');

            //Update
            $sql = "UPDATE layouts_contents
                SET column_id = ". db_ei($column_id) .", rank = " . db_ei($rank) . "
                WHERE owner_type = '". db_es($owner_type) ."'
                  AND owner_id = ". db_ei($owner_id) ."
                  AND name = '" . db_es($name) . "'
                  AND layout_id = ". db_ei($layout_id);
            $res = db_query($sql);
            echo db_error();
        } else {
            //Insert
            $sql = "INSERT INTO layouts_contents(owner_type, owner_id, layout_id, column_id, name, content_id, rank)
            SELECT R1.owner_type, R1.owner_id, R1.layout_id, R1.column_id, '" . db_es($name) . "', " . db_ei($content_id) . ", IFNULL(R2.rank, 1) - 1
            FROM ( SELECT '". db_es($owner_type) . "' AS owner_type, " . db_ei($owner_id) . " AS owner_id, " . db_ei($layout_id) . " AS layout_id, "  . db_ei($column_id) . " AS column_id ) AS R1
            LEFT JOIN layouts_contents AS R2 USING ( owner_type, owner_id, layout_id, column_id )
            ORDER BY rank ASC
            LIMIT 1";
            db_query($sql);
            echo db_error();
        }
        $this->feedback($owner_id, $owner_type);
    }

    protected function feedback($owner_id, $owner_type) {
        $link = '/';
        if ($owner_type == self::OWNER_TYPE_GROUP) {
            //retrieve the short name of the project
            if ($project = ProjectManager::instance()->getProject($owner_id)) {
                $hp = Codendi_HTMLPurifier::instance();
                $link = '/projects/'.  $hp->purify($project->getUnixName(), CODENDI_PURIFIER_CONVERT_HTML) ;
            }
        } else if ($owner_type == self::OWNER_TYPE_USER) {
            $link = '/my/';
        }
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('widget_dashboard', 'updated', $link), CODENDI_PURIFIER_DISABLED);
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
        $sql = "DELETE FROM layouts_contents WHERE owner_type = '". db_es($owner_type) ."' AND owner_id = ". db_ei($owner_id) ." AND layout_id = " . db_ei($layout_id) . " AND name = '" . db_es($name) . "' AND content_id = " . db_ei($instance_id);
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
        $sql = "UPDATE layouts_contents SET is_minimized = 1 WHERE owner_type = '". db_es($owner_type) ."' AND owner_id = ". db_ei($owner_id) ." AND layout_id = ". db_ei($layout_id) ." AND name = '". db_escape_string($name) ."' AND content_id = ". db_ei($instance_id);
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
        $sql = "UPDATE layouts_contents SET is_minimized = 0 WHERE owner_type = '". db_es($owner_type) ."' AND owner_id = ". db_ei($owner_id) ." AND layout_id = ". db_ei($layout_id) ." AND name = '". db_escape_string($name) ."' AND content_id = ". db_ei($instance_id);
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
        $sql = "UPDATE layouts_contents SET display_preferences = 1, is_minimized = 0 WHERE owner_type = '". db_es($owner_type) ."' AND owner_id = ". db_ei($owner_id) ." AND layout_id = ". db_ei($layout_id) ." AND name = '". db_escape_string($name) ."' AND content_id = " . db_ei($instance_id);
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
        $sql = "UPDATE layouts_contents SET display_preferences = 0 WHERE owner_type = '". db_es($owner_type) ."' AND owner_id = ". db_ei($owner_id) ." AND layout_id = ". db_ei($layout_id) ." AND name = '". db_escape_string($name) ."' AND content_id = " . db_ei($instance_id);
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
                $sql = "SELECT * FROM layouts_contents WHERE owner_type = '". db_es($owner_type) ."' AND owner_id = ". db_ei($owner_id) ." AND column_id = ". db_ei($column_id) .' ORDER BY rank';
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
                        WHERE owner_type = '". db_es($owner_type) ."'
                          AND owner_id = ". db_ei($owner_id) .'
                          AND column_id = '. db_ei($column_id) .
                          $_and;
                    $res = db_query($sql);
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
                        SET column_id = '. db_ei($column_id) ."
                        WHERE owner_type = '". db_es($owner_type) ."'
                          AND owner_id = ". db_ei($owner_id) .
                          $_and ."
                          AND layout_id = ". db_ei($layout_id);
                    $res = db_query($sql);
                    echo db_error();
                }


                //Update ranks
                $rank = 0;
                $values = array();
                foreach($names as $name) {
                    $sql = 'UPDATE layouts_contents SET rank = '. db_ei($rank++) ." WHERE owner_type = '". db_es($owner_type) ."' AND owner_id = ". db_ei($owner_id) .' AND column_id = '. db_ei($column_id) ." AND name = '".$name[1]."' AND content_id = ". $name[0];
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

    private function displayUpdateLayout($layout_id, array $parameters, CSRFSynchronizerToken $csrf_token)
    {
        $sql         = "SELECT * FROM layouts WHERE scope='S' ORDER BY id ";
        $req_layouts = db_query($sql);
        echo '<form action="/widgets/updatelayout.php?'. http_build_query($parameters) .'" method="POST">';
        echo $csrf_token->fetchHTMLInput();
        echo '<table cellspacing="0" cellpading="0">';
        $is_custom = true;
        while ($data = db_fetch_array($req_layouts)) {
            $checked   = $layout_id == $data['id'] ? 'checked="checked"' : '';
            $is_custom = $is_custom && ! $checked;
            echo '<tr class="layout-manager-chooser ' . ($checked ? 'layout-manager-chooser_selected' : '') . '" ><td>';
            echo '<input type="radio" name="layout_id" value="' . $data['id'] . '" id="layout_' . $data['id'] . '" ' . $checked . '/>';
            echo '</td><td>';
            echo '<label for="layout_' . $data['id'] . '">';
            echo $GLOBALS['HTML']->getImage('layout/' . strtolower(preg_replace('/(\W+)/', '-',
                    $data['name'])) . '.png');
            echo '</label>';
            echo '</td><td>';
            echo '<label for="layout_' . $data['id'] . '"><strong>' . $data['name'] . '</strong><br />';
            echo $data['description'];
            echo '</label>';
            echo '</td></tr>';
        }
        /* Custom layout are not available yet */
        $checked = $is_custom ? 'checked="checked"' : '';
        echo '<tr class="layout-manager-chooser ' . ($checked ? 'layout-manager-chooser_selected' : '') . '"><td>';
        echo '<input type="radio" name="layout_id" value="-1" id="layout_custom" ' . $checked . '/>';
        echo '</td><td>';
        echo '<label for="layout_custom">';
        echo $GLOBALS['HTML']->getImage('layout/custom.png', array('style' => 'vertical-align:top;float:left;'));
        echo '</label>';
        echo '</td><td>';
        echo '<label for="layout_custom"><strong>' . 'Custom' . '</strong><br />';
        echo 'Define your own layout:';
        echo '</label>';
        echo '<table id="layout-manager" cellpadding="0" cellspacing="0">
                    <tr>
                      <td>
                        <div class="layout-manager-row-add">+</div>';
        $sql      = 'SELECT * FROM layouts_rows WHERE layout_id = ' . db_ei($layout_id) . ' ORDER BY rank';
        $req_rows = db_query($sql);
        while ($data = db_fetch_array($req_rows)) {
            echo '<table class="layout-manager-row" cellspacing="5" cellpadding="2" border="0">
                        <tr>
                          <td class="layout-manager-column-add">+</td>';
            $sql      = 'SELECT * FROM layouts_rows_columns WHERE layout_row_id = ' . db_ei($data['id']);
            $req_cols = db_query($sql);
            while ($data = db_fetch_array($req_cols)) {
                echo '<td class="layout-manager-column">
                            <div class="layout-manager-column-remove">x</div>
                            <div class="layout-manager-column-width">
                                <input type="text" value="' . $data['width'] . '" autocomplete="off" size="1" maxlength="3" />%
                            </div>
                          </td>
                          <td class="layout-manager-column-add">+</td>';
            }
            echo '  </tr>
                      </table>
                      <div class="layout-manager-row-add">+</div>';
        }
        echo '    </td>
                    </tr>
                  </table>';
        echo '</td></tr>';
        echo '</table>';
        echo '<input type="submit" id="save" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
        echo '</form>';
    }

    private function displayAddWidgetForm(
        $owner_id,
        $owner_type,
        array $parameters,
        CSRFSynchronizerToken $csrf_token,
        array $used_widgets
    ) {
        echo '<form action="/widgets/updatelayout.php?' . http_build_query($parameters) . '" method="POST">';
        echo $csrf_token->fetchHTMLInput();
        echo '<table cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr valign="top">
                            <td>
                                <table cellpadding="2" cellspacing="0">
                                    <tbody>';
        $after = $this->_displayWidgetsSelectionForm(
            $owner_id,
            $GLOBALS['Language']->getText('widget_add', 'codendi_widgets', $GLOBALS['sys_name']),
            Widget::getCodendiWidgets($owner_type),
            $used_widgets
        );
        echo '                      </tbody>
                                </table>
                            </td>
                            <td id="widget-content-categ">'. $after .'</td>
                        </tr>
                    </tbody>
                </table>';
        echo '</form>';
    }
}
