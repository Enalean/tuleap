<?php
/*
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

require_once(dirname(__FILE__)."/../data-access/GraphicReportFactory.class.php");
require_once(dirname(__FILE__)."/../common/GraphicEngineUserPrefs.class.php");
require_once('common/tracker/ArtifactField.class.php');

class graphicEngineHtml {

    var $grf;
    
    protected $theme_path;

    function __construct($group_artifact_id,$user_id,$theme_path) {
        $this->grf = new GraphicReportFactory($group_artifact_id,$user_id);
        $this->theme_path = $theme_path;
    }

    function showAvailableReports() {
        $hp = Codendi_HTMLPurifier::instance();
        $g = $GLOBALS['ath']->getGroup();
        $group_id = $g->getID();
        $atid = $GLOBALS['ath']->getID();
        $reports = $this->grf->getReports_ids();
        echo '<H2>'.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','tracker').
             ' \'<a href="/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'">'.$hp->purify($GLOBALS['ath']->getName()).'</a>\''.
             $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','report_admin').
             '</H2>';

        if ($reports) {
            // Loop through the list of all graphic reports
            $title_arr = array();
            $title_arr[] = $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','report_name');
            $title_arr[] = $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','desc');
            $title_arr[] = $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','scope');
            $title_arr[] = $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','delete');

            echo '<p>'.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','mod');
            echo  html_build_list_table_top ($title_arr);

            for ($i=0;$i<count($reports);$i++) {
                $r = new GraphOnTrackers_Report($reports[$i]);
                echo  '<TR class="'. util_get_alt_row_color($i) .'"><TD>';
                if ( $r->getScope()== 'S' || (!$GLOBALS['ath']->userIsAdmin()&&($r->getScope() == 'P')) ) {
                    echo  $hp->purify($r->getName());
                } else {
                    echo  '<A HREF="/tracker/admin/?func=reportgraphic&group_id='.$group_id.
                          '&report_graphic_id='.$r->getId().'&atid='.$GLOBALS['ath']->getID().'">'.
                           $hp->purify($r->getName()).'</A>';
                }

                echo  "</td>".
                      "\n<td>".$hp->purify($r->getDescription(), CODENDI_PURIFIER_BASIC).'</td>'.
                      "\n<td align=\"center\">".$hp->purify($r->getScopeLabel($r->getScope())).'</td>'.
                      "\n<td align=\"center\">";

                if ( $r->getScope() == 'S' || (!$GLOBALS['ath']->userIsAdmin()&&($r->getScope() == 'P')) ) {
                    echo  '-';
                } else {
                    $delete_report_text    = $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','delete_report',$r->getName());
                    $delete_report_text_js = $hp->purify($delete_report_text, CODENDI_PURIFIER_JS_QUOTE);
                    echo  '<A HREF="/tracker/admin/?func=reportgraphic'.'&report_graphic_id='.$r->getId().'&group_id='.$group_id.
                          '&atid='.$atid.'&delete_report_graphic=1"'.
                          '" onClick="return confirm(\''.$delete_report_text_js.'\')">'.
                          '<img src="'.util_get_image_theme("ic/trash.png").'" border="0"></A>';
                }

                echo  '</td></tr>';
            }

            echo  '</TABLE>';
        } else {
            echo  '<p><h3>'.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','no_rep_def').'</h3>';
        }
        echo  '<P> '.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','create_report',array('/tracker/admin/?func=reportgraphic&group_id='.$group_id.'&atid='.$atid.'&new_report_graphic=1'));
    }

    /**
     *  Display the report form
     *
     *  @return void
     */

    function createReportForm() {
        $hp = Codendi_HTMLPurifier::instance();
        $g = $GLOBALS['ath']->getGroup();
        $group_id = $g->getID();
        $atid = $GLOBALS['ath']->getID();
        echo '<H2>'.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','tracker').
             ' \'<a href="/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'">'.
             $hp->purify($GLOBALS['ath']->getName()).'</a>\'  - '.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','create_rep').
             ' </H2>';

        echo '<FORM NAME="create_rep_graphic" ACTION="/tracker/admin/" METHOD="POST">
              <INPUT TYPE="HIDDEN" NAME="func" VALUE="reportgraphic">
              <INPUT TYPE="HIDDEN" NAME="create_report_graphic" VALUE="y">
              <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$hp->purify($group_id).'">
              <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.$hp->purify($atid).'">
              <INPUT TYPE="HIDDEN" NAME="post_changes_graphic" VALUE="y">
              <table><tr valign="top"><td>
              <B>'.$hp->purify($GLOBALS['Language']->getText('plugin_graphontrackers_include_report','name')).':</B>
              </td><td>
              <INPUT TYPE="TEXT" NAME="rep_name" VALUE="" SIZE="20" MAXLENGTH="20">
              &nbsp;&nbsp;&nbsp;&nbsp;
              <B>'.$hp->purify($GLOBALS['Language']->getText('plugin_graphontrackers_include_report','scope')).': </B>';
    
        if ($GLOBALS['ath']->userIsAdmin()) {
            echo '<SELECT NAME="rep_scope">
                      <OPTION VALUE="I">'.$GLOBALS['Language']->getText('global','Personal').'</OPTION>
                      <OPTION VALUE="P">'.$GLOBALS['Language']->getText('global','Project').'</OPTION>
                  </SELECT>';
        } else {
            echo $GLOBALS['Language']->getText('global','Personal').
                 ' <INPUT TYPE="HIDDEN" NAME="rep_scope" VALUE="I">';
        }
        
        echo '</td><td rowspan="2"  valign="middle"><input type="submit" name="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></td></tr>
              <tr valign="top"><td>
               <B>'.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','desc').': </B></td><td>
               <INPUT TYPE="TEXT" NAME="rep_desc" VALUE="" class="textfield_medium" >
              </td></tr></table>';

        echo '</FORM>';
    }


    /**
     *  Display detail report form
     *
     *  @return void
     */


    function showReportForm($report_graphic_id) {
        $hp =& Codendi_HTMLPurifier::instance();
        $group = $GLOBALS['ath']->getGroup();
        $group_id = $group->getID();
        $atid = $GLOBALS['ath']->getID();
        $gr = new GraphOnTrackers_Report($report_graphic_id);
        echo '<H2>'.
                $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','tracker').
                ' \'<A href="/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'">'.
                    $hp->purify($GLOBALS['ath']->getName()).
                '</A>\' -  '.
                $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','modify_report').' \''.$hp->purify($gr->name).'\' ';
		echo '</H2>';
        echo '<p><a href="/tracker/admin/?func=reportgraphic&amp;group_id='. (int)$group_id .'&amp;atid='. (int)$atid .'">&laquo; '. $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','return_reports') .'</a></p>';
        echo '<form name="show_rep_graphic" action="/tracker/admin/" method="post" class="form-inline">
              <input type="hidden" name="func" value="reportgraphic">
              <input type="hidden" name="update_report_graphic" value="y">
              <input type="hidden" name="atid" value="'.$hp->purify($atid).'">
              <input type="hidden" name="group_id" value="'.$hp->purify($group_id).'">
              <input type="hidden" name="report_graphic_id" value="'.$hp->purify($gr->getId()).'">
              <input type="hidden" name="post_changes_graphic" value="y">

              <label>'.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','name').': </label>
              <input type="text" name="rep_name" value="'.$hp->purify($gr->getName()).'" maxlength="20" class="input-small">

              <label>'.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','desc').':</label>
              <input type="text" name="rep_desc" value="'.$hp->purify($gr->getDescription()).'" class="input-xlarge" />

              <label>'.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','scope').': </label>';
    
        if ($GLOBALS['ath']->userIsAdmin()) {
            echo '<select name="rep_scope">
                  <option value="i"'.($gr->getScope() =='I' ? 'selected="selected"':'').'>'.$GLOBALS['Language']->getText('global','Personal').'</option>
                  <option value="p"'.($gr->getScope() =='P' ? 'selected="selected"':'').'>'.$GLOBALS['Language']->getText('global','Project').'</option>
                  </select>';
        } else {
            echo ($gr->getScope() =='P' ? $GLOBALS['Language']->getText('global','Project'):$GLOBALS['Language']->getText('global','Personal'));
        }

        echo '<input type="submit" name="update_report" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" class="btn"/>';

        echo '<hr /><p><strong>'.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','add_chart').'</strong> ';
        $url = '/tracker/admin/?func=reportgraphic&amp;group_id='. (int)$group_id .'&amp;atid='. (int)$atid .'&amp;report_graphic_id='. (int)$gr->getId();
        $url_add = $url .'&amp;add_chart=';
        foreach($gr->getChartFactories() as $factory) {
            $js = 'location.href=\''.$url_add . $factory['chart_type'].'\'';
            $on_click = 'onClick="'.$js.';"';
            echo '<button type="button" class="btn graphontrackers_add_btn" '.$on_click.' style="background-image: url(\''.$factory['icon'].'\');">'. $factory['title'] .'</button> ';
        }
        echo '</p>';
        foreach($gr->getCharts() as $chart) {
            echo '<div style="float:left; padding:10px; text-align:right;">';
            echo '<a title="'. $GLOBALS['Language']->getText('plugin_graphontrackers_include_report', 'tooltip_edit') .'" href="'. $url .'&amp;edit_chart='. $chart->getId() .'"><img src="'. util_get_dir_image_theme() .'ic/edit.png" alt="edit" /></a>';
            echo '<input title="'. $GLOBALS['Language']->getText('plugin_graphontrackers_include_report', 'tooltip_del') .'" type="image" src="'. util_get_dir_image_theme() .'ic/cross.png" onclick="return confirm('.$GLOBALS['Language']->getText('plugin_graphontrackers_include_report','confirm_del').');" name="delete_chart['. $chart->getId() .']" />';
            $chart->display();
            echo '</div>';
        }
        echo '<div style="clear:both;"></div>
        </form>';
    }

    function showChartForm($chart) {
        $hp = Codendi_HTMLPurifier::instance();
        $group_id = (int)$chart->getGraphicReport()->getGroupId();
        $atid = (int)$chart->getGraphicReport()->getAtid();
        echo '<H2>'.
                $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','tracker').
                ' \'<A href="/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'">'.
                    $hp->purify($GLOBALS['ath']->getName()).
                '</A>\' -  '.
                $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','modify_chart', array($hp->purify($chart->getTitle())));
        echo '</H2>';
        echo '<script type="text/javascript" src="/plugins/graphontrackers/dependencies.js"></script>';
        
        $url = '/tracker/admin/?func=reportgraphic&amp;group_id='. $group_id .'&amp;atid='. $atid .'&amp;report_graphic_id='. (int)$chart->getGraphicReport()->getId();
        echo '<p><a href="'. $url .'">&laquo; '. $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','return_report').' '. $hp->purify($chart->getgraphicReport()->getName()) .'</a></p>';
        echo '<form action="'. $url .'&amp;edit_chart='. $chart->getId() .'" name="edit_chart_form" method="post">';
        echo '<table>';
        echo '<thead><tr class="boxtable"><th class="boxtitle">'.$GLOBALS['Language']->getText('plugin_graphontrackers_boxtable','chart_properties').'</th><th class="boxtitle">'.$GLOBALS['Language']->getText('plugin_graphontrackers_boxtable','preview').'</th></tr></thead>';
        echo '<tbody><tr valign="top"><td>';
        //{{{ Chart Properties
        foreach($chart->getProperties() as $prop) {
            echo '<p>'. $prop->render() ."</p>\n";
        }
        echo '<p style="text-align:center;"><input type="submit" name="update_chart" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></p>';
        //}}}
        echo '</td><td style="text-align:center">';
        //{{{ Chart Preview
        $chart->display();
        //}}}
        echo '</tr>';
        if ($help = $chart->getHelp()) {
            echo '<tr><td colspan="2" class="inline_help">'. $help .'</td></tr>';
        }
        echo '</tbody></table>';
        echo '</form>';
    }



    function displayReportGraphic($report_graphic_id, $group_id, $atid, $url){
        $current_user = UserManager::instance()->getCurrentUser();
        echo '<A name="charts"></A>';
        echo '<h3>';
        $onclick = '';
        $onclick .= "if ($('artifacts_charts').empty()) { return true }";
        if (!$current_user->isAnonymous()) {
            $onclick .= "else { new Ajax.Request(this.href); }";
        }
        $onclick .= "if ($('artifacts_charts').visible()) { this.firstChild.src.replace(/minus.png/, 'plus.png'); } else {this.firstChild.src.replace(/plus.png/, 'minus.png');}";
        $onclick .= "new Effect.toggle($('artifacts_charts'), 'slide', {duration:0.1});";
        $onclick .= "return false;";
        echo '<a href="'. $url .'&amp;func=toggle_section&amp;section=charts" onclick="'. $onclick .'">';
        if ($current_user->getPreference('tracker_'. (int)$atid .'_hide_section_charts')) {
            $image = 'ic/toggle_plus.png';
        } else {
            $image = 'ic/toggle_minus.png';
        }
        echo $GLOBALS['HTML']->getImage($image);
        echo '</a>';
        echo $GLOBALS['Language']->getText('plugin_graphontrackers_report','title') .'</h3>';
        echo '<div id="artifacts_charts" style="padding-left:16px;">';
        if (!$current_user->getPreference('tracker_'. (int)$atid .'_hide_section_charts')) {
            echo '<p>'. $this->genGraphRepSelBox($report_graphic_id) .'</p>';
            $gr = new GraphOnTrackers_Report($report_graphic_id);
            foreach($gr->getCharts() as $chart) {
                $overflow = $chart->getWidth() ? '' : 'overflow:auto;';
                echo '<div style="float:left; padding:10px; text-align:right; '. $overflow .'">';
                $chart->display();
                echo '</div>';
            }
            echo '<div style="clear:both;"></div>';
        }
        echo '</div>';
    }

    function _toInputElement($name, $value, $suffix = '') {
        $str = '';
        if (is_array($value)) {
            foreach($value as $k => $v) {
                $str .= $this->_toInputElement($name . $suffix . '[' . $k, $v, ']');
            }
        } else {
            $hp = Codendi_HTMLPurifier::instance();
            $str .= '<input type="hidden" name="'. $hp->purify($name) . $suffix .'" value="'. $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) .'" />';
        }
        return $str;
    }
    
    function genGraphRepSelBox($value) {
        $hp = Codendi_HTMLPurifier::instance();
        require_once(dirname(__FILE__)."/../data-access/GraphOnTrackers_Report.class.php");
        $reports  = $this->grf->getReports_ids();
        $returns  = '<form name="plugin_graphontrackers_selectreport_form" action="'. $hp->purify($_SERVER['REQUEST_URI']) .'" method="GET">';
        parse_str($_SERVER['QUERY_STRING'], $url_params);
        foreach($url_params as $key => $v) {
            if ($key != 'go_graphreport' && $key != 'report_graphic_id') {
                $returns .= $this->_toInputElement($key, $v);
            }
        }
        $returns .= '<B>'.$GLOBALS['Language']->getText('plugin_graphontrackers_graphic_report_label','use_graphic_report').'&nbsp;&nbsp;</B>' .
                    '<SELECT NAME="report_graphic_id" onChange="document.plugin_graphontrackers_selectreport_form.go_graphreport.click()">';
        $returns .= '<OPTION VALUE="0">'.$GLOBALS['Language']->getText('plugin_graphontrackers_empty_select','none_value').'</OPTION>';
        for ($i=0;$i<count($reports);$i++){
            $r = new GraphOnTrackers_Report($reports[$i]);
            if ($reports[$i] == $value) {
                $returns .= '<OPTION selected="selected" VALUE="'.$hp->purify($r->getId()).'">'.$hp->purify(stripslashes($r->getName())).'</OPTION>';
            } else {
                $returns .= '<OPTION  VALUE="'.$hp->purify($r->getId()).'">'.$hp->purify($r->getName()).'</OPTION>';
            }
        }
        $returns .= '</SELECT>&nbsp;<INPUT TYPE="submit" VALUE="'.$GLOBALS['Language']->getText('plugin_graphontrackers_report','btn_go').'" NAME="go_graphreport"/>';
        $returns .= '</form>';
        return $returns;
    }
}
