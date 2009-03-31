<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once('CodeXUpgrade.class.php');

class Update_011 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        if($this->tableExists('plugin_graphtrackers_report_graphic') &&
           $this->tableExists('plugin_graphtrackers_gantt_chart') &&
           $this->tableExists('plugin_graphtrackers_pie_chart') &&
           $this->tableExists('plugin_graphtrackers_bar_chart') &&
           $this->tableExists('plugin_graphtrackers_line_chart')) {

           	echo "create plugin_graphontrackers_line_chart table";
            echo $this->getLineSeparator();                      
            $sql = "CREATE TABLE plugin_graphontrackers_line_chart(
                    id int(11)  NOT NULL PRIMARY KEY ,
                    field_base varchar(255) ,
                    state_source varchar(255) ,
                    state_target varchar(255) ,
                    date_min int(11) ,
                    date_max int(11) ,
                    date_reference int(11) ,
                    method varchar(255))";
            $res = $this->update($sql);
            if (!$res) {
                $this->addUpgradeError("An error occured while creating plugin_graphontrackers_line_chart table': ".$this->da->isError());
            }            
            

            // copy existing reports and charts
            echo "copy existing reports and charts";
            echo $this->getLineSeparator();
            
            // get reports project by project
            
            $sqlPrj = "SELECT DISTINCT group_artifact_id " .
                      "FROM plugin_graphtrackers_report_graphic " .
                      "WHERE user_id <> 100";
            $darPrj = $this->retrieve($sqlPrj);
            $new_rpt_id = 5;
            $new_chart_id = 11; 
            if($darPrj && !$darPrj->isError()) {
                while($rowPrj = $darPrj->getRow()) {
                    $group_id = $rowPrj['group_artifact_id'];
                    echo "Transfert the reports of the project : ".$group_id;
                    echo $this->getLineSeparator();
                    // get reports for the project
                    $sqlRpt = "SELECT * " .
                              "FROM plugin_graphtrackers_report_graphic " .
                              "WHERE group_artifact_id = ".$group_id;
                    $darRpt = $this->retrieve($sqlRpt);
                    if($darRpt && !$darRpt->isError()) {
                        while($rowRpt = $darRpt->getRow()) {
                            $rpt_id = $rowRpt['report_graphic_id'];
                            echo "---- Transfert of report: ".$rpt_id;
                            echo $this->getLineSeparator();
                            $rank = 5;
                            // create the report in the new structure
                            $sql = "INSERT INTO plugin_graphontrackers_report_graphic (report_graphic_id,group_artifact_id,user_id,name,description,scope) " .
                                   "VALUES($new_rpt_id,".$rowRpt['group_artifact_id'].",".$rowRpt['user_id'].",".$this->da->quoteSmart($rowRpt['name']).",".$this->da->quoteSmart($rowRpt['description']).",'".$rowRpt['scope']."')";
                            $res = $this->update($sql);
                            if (!$res) {
                                $this->addUpgradeError("An error occured while creating the report ".$rpt_id.": ".$this->da->isError());
                            }
                            // get the new graphic report id 
                            echo "New Graphic report Created: ".$new_rpt_id." Instead of the report ".$rpt_id;
                            echo $this->getLineSeparator();
                            echo "++++Update graphic report user prefs to the new created report+++";
                            echo $this->getLineSeparator();
                            $sql = "UPDATE user_preferences SET preference_value='&report_graphic_id=".$new_rpt_id."' WHERE preference_name LIKE '%tracker_graph_brow_cust%' and preference_value='&report_graphic_id=".$rpt_id."'";
                            $res = $this->update($sql);
                            if (!$res) {
                                $this->addUpgradeError("An error occured while updating user prefs for graphic report ".$rpt_id.": ".$this->da->isError());
                            }
                            echo $this->getLineSeparator();
                            
                            
                            // get gantt charts of the report
                            $sqlGantt = "SELECT * " .
                                        "FROM plugin_graphtrackers_gantt_chart " .
                                        "WHERE report_graphic_id = ".$rpt_id;
                            $darGantt = $this->retrieve($sqlGantt);
                            if($darGantt && !$darGantt->isError()) {
                                while($rowGantt = $darGantt->getRow()) {
                                    $gantt_id = $rowGantt['gantt_id'];
                                    echo "---- ---- Creating the Gantt chart: ".$gantt_id;
                                    echo $this->getLineSeparator();
                                    $sql = "INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description,width,height) " .
                                           "VALUES ($new_chart_id,".$rpt_id.",".$rank.",'gantt',".$this->da->quoteSmart($rowGantt['title']).",".$this->da->quoteSmart($rowGantt['description']).",0,0)";
                                    $res = $this->update($sql);
                                    if (!$res) {
                                        $this->addUpgradeError("An error occured while creating the gantt chart ".$gantt_id.": ".$this->da->isError());
                                    }
                                    $rank = $rank+5;
                                    // get the newly created Gantt id
                                    
                                    echo "New gantt Created: ".$new_chart_id." Instead of the gantt ".$gantt_id;
                                    echo $this->getLineSeparator();
                                    $sql = "INSERT INTO plugin_graphontrackers_gantt_chart (id,field_start,field_due,field_finish,field_percentage,field_righttext,scale,as_of_date,summary) " .
                                           "VALUES(".$new_chart_id.",'".$rowGantt['field_start']."','".$rowGantt['field_due']."','".$rowGantt['field_finish']."','".$rowGantt['field_percentage']."','".$rowGantt['field_righttext']."','".$rowGantt['scale']."','".$rowGantt['as_of_date']."','".$rowGantt['summary']."')";
                                    $res = $this->update($sql);
                                    if (!$res) {
                                        $this->addUpgradeError("An error occured while creating the gantt chart ".$gantt_id.": ".$this->da->isError());
                                    }
                                    $new_chart_id++;
                                }
                            }
                            
                            // get pie charts of the report
                            $sqlPie = "SELECT * " .
                                        "FROM plugin_graphtrackers_pie_chart " .
                                        "WHERE report_graphic_id = ".$rpt_id;
                            $darPie = $this->retrieve($sqlPie);
                            if($darPie && !$darPie->isError()) {
                                while($rowPie = $darPie->getRow()) {
                                    $pie_id = $rowPie['pie_id'];
                                    echo "---- ---- Creating the Pie chart: ".$pie_id;
                                    echo $this->getLineSeparator();
                                    if ($rowPie['height'] == '') $rowPie['height'] = 500;
                                    if ($rowPie['width'] == '') $rowPie['width'] = 500;
                                    $sql = "INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description,width,height) " .
                                           "VALUES ($new_chart_id,".$rpt_id.",".$rank.",'pie',".$this->da->quoteSmart($rowPie['title']).",".$this->da->quoteSmart($rowPie['description']).",".$rowPie['width'].",".$rowPie['height'].")";
                                    $res = $this->update($sql);       
                                    if (!$res) {
                                        $this->addUpgradeError("An error occured while creating the pie chart ".$pie_id.": ".$this->da->isError());
                                    }
                                    $rank = $rank+5;
                                    // get the newly created pie id
                                    echo "New pie Created: ".$new_chart_id." Instead of the pie ".$pie_id;
                                    echo $this->getLineSeparator();
                                    $sql = "INSERT INTO plugin_graphontrackers_pie_chart (id,field_base) " .
                                           "VALUES(".$new_chart_id.",'".$rowPie['field_base']."')";
                                    $res = $this->update($sql);
                                    if (!$res) {
                                        $this->addUpgradeError("An error occured while creating the pie chart ".$pie_id.": ".$this->da->isError());
                                    }
                                    $new_chart_id++;
                                }
                            }
                            
                            // get bar charts of the report
                            $sqlBar = "SELECT * " .
                                        "FROM plugin_graphtrackers_bar_chart " .
                                        "WHERE report_graphic_id = ".$rpt_id;
                            $darBar = $this->retrieve($sqlBar);
                            if($darBar && !$darBar->isError()) {
                                while($rowBar = $darBar->getRow()) {
                                    $bar_id = $rowBar['bar_id'];
                                    echo "---- ---- Creating the Bar chart: ".$bar_id;
                                    echo $this->getLineSeparator();
                                    if ($rowBar['height'] == '') $rowBar['height'] = 500;
                                    if ($rowBar['width'] == '') $rowBar['width'] = 500;
                                    $sql = "INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description,width,height) " .
                                           "VALUES ($new_chart_id,".$rpt_id.",".$rank.",'bar',".$this->da->quoteSmart($rowBar['title']).",".$this->da->quoteSmart($rowBar['description']).",".$rowBar['width'].",".$rowBar['height'].")";
                                    $res = $this->update($sql);
                                    if (!$res) {
                                        $this->addUpgradeError("An error occured while creating the bar chart ".$bar_id.": ".$this->da->isError());
                                    }
                                    $rank = $rank+5;
                                    // get the newly created bar id
                                    echo "New bar Created: ".$new_chart_id." Instead of the bar ".$bar_id;
                                    echo $this->getLineSeparator();
                                    $sql = "INSERT INTO plugin_graphontrackers_bar_chart (id,field_base,field_group) " .
                                           "VALUES(".$new_chart_id.",'".$rowBar['field_base']."','".$rowBar['field_group']."')";
                                    $res = $this->update($sql);
                                    if (!$res) {
                                        $this->addUpgradeError("An error occured while creating the bar chart ".$bar_id.": ".$this->da->isError());
                                    }
                                    $new_chart_id++;
                                }
                            }
                            
                            // get line charts of the report
                            $sqlLine = "SELECT * " .
                                        "FROM plugin_graphtrackers_line_chart " .
                                        "WHERE field_base <> null and report_graphic_id = ".$rpt_id;
                            $darLine = $this->retrieve($sqlLine);
                            if($darLine && !$darLine->isError()) {
                                while($rowLine = $darLine->getRow()) {
                                    $line_id = $rowLine['line_id'];
                                    echo "---- ---- Creating the Line chart: ".$line_id;
                                    echo $this->getLineSeparator();
                                    if ($rowLine['height'] == '') $rowLine['height'] = 500;
                                    if ($rowLine['width'] == '') $rowLine['width'] = 500;
                                    $sql = "INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description,width,height) " .
                                           "VALUES ($new_chart_id,".$rpt_id.",".$rank.",'line',".$this->da->quoteSmart($rowLine['title']).",".$this->da->quoteSmart($rowLine['description']).",".$rowLine['width'].",".$rowLine['height'].")";
                                    $res = $this->update($sql);
                                    if (!$res) {
                                        $this->addUpgradeError("An error occured while creating the line chart ".$line_id.": ".$this->da->isError());
                                    }
                                    $rank = $rank+5;
                                    // get the newly created line id
                                    echo "New line Created: ".$new_chart_id." Instead of the line ".$line_id;
                                    echo $this->getLineSeparator();
                                    $sql = "INSERT INTO plugin_graphontrackers_line_chart (id,field_base,state_source,state_target,date_min,date_max,date_reference,method) " .
                                           "VALUES(".$new_chart_id.",'".$rowLine['field_base']."','".$rowLine['state_source']."','".$rowLine['state_target']."','".$rowLine['date_min']."','".$rowLine['date_max']."','".$rowLine['date_reference']."','".$rowLine['method']."')";
                                    $res = $this->update($sql);
                                    if (!$res) {
                                        $this->addUpgradeError("An error occured while creating the line chart ".$line_id.": ".$this->da->isError());
                                    }
                                    $new_chart_id++;
                                }
                                $new_rpt_id++;
                            }
                        }
                    }                    
                }
            }
            
            // delete old structure
            echo "delete old structure";
            echo $this->getLineSeparator();
                        
            $sql = "DROP TABLE plugin_graphtrackers_report_graphic";
            $res = $this->update($sql);
            if (!$res) {
                $this->addUpgradeError("An error occured while deleting plugin_graphtrackers_report_graphic table ': ".$this->da->isError());
            }
            $sql = "DROP TABLE plugin_graphtrackers_gantt_chart";
            $res = $this->update($sql);
            if (!$res) {
                $this->addUpgradeError("An error occured while deleting plugin_graphtrackers_gantt_chart table ': ".$this->da->isError());
            }
            $sql = "DROP TABLE plugin_graphtrackers_pie_chart";
            $res = $this->update($sql);
            if (!$res) {
                $this->addUpgradeError("An error occured while deleting plugin_graphtrackers_pie_chart table ': ".$this->da->isError());
            }
            $sql = "DROP TABLE plugin_graphtrackers_bar_chart";
            $res = $this->update($sql);
            if (!$res) {
                $this->addUpgradeError("An error occured while deleting plugin_graphtrackers_bar_chart table ': ".$this->da->isError());
            }
            $sql = "DROP TABLE plugin_graphtrackers_line_chart";
            $res = $this->update($sql);
            if (!$res) {
                $this->addUpgradeError("An error occured while deleting plugin_graphtrackers_line_chart table ': ".$this->da->isError());
            }
            $sql = "DROP TABLE plugin_graphtrackers_mta_chart";
            $res = $this->update($sql);
            if (!$res) {
                $this->addUpgradeError("An error occured while deleting plugin_graphtrackers_mta_chart table ': ".$this->da->isError());
            }
            
        } else {
            $this->addUpgradeError("The GraphOnTrackers plugin was never been installed on this server or the current version is not the right one. please contact the author of this script (MAALEJ Mahmoud)!");
        }
    }
}
