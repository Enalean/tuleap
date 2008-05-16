<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// 
//
//  Parts of code come from bug_util.php (written by Laurent Julliard)
//
//  Written for CodeX by Stephane Bouhet
//

require_once('common/event/EventManager.class.php');

$Language->loadLanguageMsg('tracker/tracker');

class ArtifactReportHtml extends ArtifactReport {
    
    var $fields_per_line;
        
        /**
         *  Constructor.
         *
         *      @param  report_id       
         *  @param  atid: the artifact type id
         *
         *      @return boolean success.
         */
        function ArtifactReportHtml($report_id,$atid) {
        // echo 'ArtifactReportHtml('.$report_id.','.$atid.')';
                return $this->ArtifactReport($report_id,$atid);
        }
        
        /**
         *      Return the HTML table which displays the priority colors and export button
         *
         *      @param msg: the label of te table
         *
         *      @return string
         */
        function showPriorityColorsKey($msg,$aids,$masschange,$pv) {
            $hp = CodeX_HTMLPurifier::instance();
	  global $Language,$group_id;
                $html_result = "";

		if (!$masschange) {
		  $html_result .= '<table width="100%"><tr><td align="left" width="50%">';
                }

                $html_result .= '<P class="small"><B>'.($msg ? $msg : $Language->getText('tracker_include_report','prio_colors')).'</B><BR><TABLE BORDER=0><TR>';
        
                for ($i=1; $i<10; $i++) {
                        $html_result .=  '<TD class="'.get_priority_color($i).'">'.$i.'</TD>';
                }
                $html_result .=  '</TR></TABLE>';
                
		if ((!$masschange)&&($pv == 0)) {
		  $html_result .= '</td><td align="right" width="50%">';
		  $html_result .= '
                          <FORM ACTION="" METHOD="POST" NAME="artifact_export_form">
                          <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$this->group_artifact_id.'">
                          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$group_id.'">
			  <INPUT TYPE="HIDDEN" NAME="func" VALUE="export">
                          <INPUT TYPE="HIDDEN" NAME="export_aids" VALUE="'. $hp->purify(implode(",",$aids), CODEX_PURIFIER_CONVERT_HTML) .'">
                          <input type="checkbox" name="only_displayed_fields" /> <small>'.$Language->getText('tracker_include_report','export_only_report_fields').'</small><br />
                          <FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_report','btn_export').'"></FONT><br />
                          <input type="hidden" name="report_id" value="'.(int)$this->getReportId().'" />
                          </FORM>';
		  
		  $html_result .=  '</td></tr></table>';
		}
		
                return $html_result;
        }
        
        /**
         *  Check is a sort criteria is already in the list of comma
         *  separated criterias. If so invert the sort order, if not then
         *  simply add it
         *
         *      @param criteria_list: the criteria list
         *  @param order: the order chosen by the UI
         *  @param msort: if multi sort is activate
         *
         *      @return string
         */
        function addSortCriteria($criteria_list, $order, $msort)
        {
            //echo "<br>DBG \$criteria_list=$criteria_list,\$order=$order";
            $found = false;
            if ($criteria_list) {
                        $arr = explode(',',$criteria_list);
                        $i = 0;
                        while (list(,$attr) = each($arr)) {
                            preg_match("/\s*([^<>]*)([<>]*)/", $attr,$match);
                            list(,$mattr,$mdir) = $match;
                            //echo "<br>DBG \$mattr=$mattr,\$mdir=$mdir";
                            if ($mattr == $order) {
                                        if ( ($mdir == '>') || (!isset($mdir)) ) {
                                            $arr[$i] = $order.'<';
                                        } else {
                                            $arr[$i] = $order.'>';
                                        }
                                        $found = true;
                            }
                            $i++;
                        }
            }
        
            if (!$found) {
                        if (!$msort) { unset($arr); }
                        if ( ($order == 'severity') || ($order == 'hours') ) {
                            // severity, effort and dates sorted in descending order by default
                            $arr[] = $order.'<';
                        } else {
                            $arr[] = $order.'>';
                        }
            }
            
            //echo "<br>DBG \$arr[]=".join(',',$arr);
        
            return(join(',', $arr));    
        
        }       

        /**
         * Transform criteria list to readable text statement
         * $url must not contain the morder parameter
         *
         *      @param criteria_list: the criteria list
         *  @param url: HTTP Get variables to add
         *
         *      @return string
         */
        function criteriaListToText($criteria_list, $url) {
                
	  global $art_field_fact;
	  $arr_text = array();
	  if ($criteria_list) {
	    
	    $arr = explode(',',$criteria_list);
	    $morder='';
	    while (list(,$crit) = each($arr)) {
	      
	      $morder .= ($morder ? ",".$crit : $crit);
	      $attr = str_replace('>','',$crit);
	      $attr = str_replace('<','',$attr);
	      
	      $field = $art_field_fact->getFieldFromName($attr);
	      if ( $field && $field->isUsed() ) {
		$label = $field->getLabel();
		$arr_text[] = '<a href="'.$url.'&morder='.urlencode($morder).'#results">'.
		  $label.'</a><img src="'.util_get_dir_image_theme().
		  ((substr($crit, -1) == '<') ? 'dn' : 'up').
		  '_arrow.png" border="0">';
	      }
	    }
	  }
	  
	  return join(' > ',$arr_text);
        }
        
        /**
         *  Display the HTML code to display the query fields
         *
         *  @param prefs: array of parameters used for the current query
         *  @param advsrch,pv: HTTP get variables
         *
         *      @return string
         *
         */
        function displayQueryFields($prefs,$advsrch,$pv) {
            global $ath,$Language;
            $hp = CodeX_HTMLPurifier::instance();
            //
            // Loop through the list of used fields to define label and fields/boxes
            // used as search criteria
            //
            
            $html_select = "<table width='100%'>";
            $labels = '';
            $boxes  = '';
            // Number of search criteria (boxes) displayed in one row
            $this->fields_per_line = 5;

            $ib=0;$is=0;
            $load_cal=false;

            $query_fields = $this->getQueryFields();
            while (list($key,$field) = each($query_fields) ) {
            
                $field_html = new ArtifactFieldHtml($field);
                    
                //echo $field->getName()."-".$field->display_type."-".$field->data_type."-".$field->dump()."<br>";
                                    
                // beginning of a new row
                if ($ib % $this->fields_per_line == 0) {
                    $align = "center";
                    $labels .= "\n".'<TR align="'.$align.'" valign="top">';
                    $boxes .= "\n".'<TR align="'.$align.'" valign="top">';
                }

                // Need to build help button argument. 
                // Concatenate 3 args in one string
                $group_id = $ath->Group->getID();
                $help_args = $group_id.'|'.$this->group_artifact_id.'|'.$field->getName();
                $labels .= '<td class="small"><b>'. $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'&nbsp;'.
                    help_button('browse_tracker_query_field',$help_args).
                    '</b></td>';
            
                $boxes .= '<TD><FONT SIZE="-1">';
            
                if ( $field->isSelectBox() ) {
    
    				// Check for advanced search if you have the field in the $prefs (HTTP parameters)
    				if ( $advsrch ) {
    					if ( isset($prefs[$field->getName()]) && $prefs[$field->getName()] ) {
    						if ( is_array($prefs[$field->getName()]) ) {
    							$values = $prefs[$field->getName()];
    						} else {
    							$values[] = $prefs[$field->getName()];
    						}
    					} else {
							$values[] = 0;
    					}
    				} else {
                                    if (isset($prefs[$field->getName()][0]))
    					$values = $prefs[$field->getName()][0];
                                    else $values="";
    				}
    			
                    $boxes .= 
                        $field_html->display($this->group_artifact_id,$values,
                                          false,false,($pv != 0?true:false),false,true,$Language->getText('global','none'), true,$Language->getText('global','any'));
            
                } else if ( $field->isMultiSelectBox() ) {
            
                    $boxes .= 
                        $field_html->display($this->group_artifact_id,
                                          $prefs[$field->getName()],
                                          false,false,($pv != 0?true:false),false,true,$Language->getText('global','none'), true,$Language->getText('global','any'));
            
                } else if ($field->isDateField() ){
            
                    $load_cal = true; // We need to load the Javascript Calendar
                    if ($advsrch) {
                        $date_begin = isset($prefs[$field->getName()][0])        ? $prefs[$field->getName()][0]        : '';
                        $date_end   = isset($prefs[$field->getName().'_end'][0]) ? $prefs[$field->getName().'_end'][0] : '';
                        $boxes .= $field_html->multipleFieldDate($date_begin, $date_end, 0, 0, $pv);
                    } else {
                        $val_op = isset($prefs[$field->getName().'_op'][0]) ? $prefs[$field->getName().'_op'][0] : '';
                        $val    = isset($prefs[$field->getName()][0])       ? $prefs[$field->getName()][0]       : '';
                        $boxes .= $field_html->fieldDateOperator($val_op, $pv) . $field_html->fieldDate($val, $pv);
                    }
                            
                } else if ( $field->isTextField() || 
                           $field->isTextArea() ) {
                    $val=isset($prefs[$field->getName()][0])?$prefs[$field->getName()][0]:"";
                    $boxes .= 
                        ($pv != 0 ? $val : $field_html->fieldText(stripslashes($val),15,80)) ;
            
                }
                $boxes .= "</TD>\n";
            
                $ib++;
            
                // end of this row
                if ($ib % $this->fields_per_line == 0) {
                    $html_select .= $labels.'</TR>'.$boxes.'</TR>';
                    $labels = $boxes = '';
                }
            
            }
            
            // Make sure the last few cells are in the table
            if ($labels) {
                $html_select .= $labels.'</TR>'.$boxes.'</TR>';
            }
            
            $html_select .= "</table>";
            
            return $html_select;

        }


        /**
         *  Return the HTML code to display the results of the query fields
         *
         *  @param group_id: the group id
         *  @param prefs: array of parameters used for the current query
         *  @param total_rows: number of rows of the result
         *  @param url: HTTP Get variables to add
         *  @param nolink: link to detailartifact
         *  @param offset,chunksz,morder,advsrch,offset,chunksz: HTTP get variables
         *
         *      @return string
         *
         */
        function showResult ($group_id,$prefs,$offset,$total_rows,$url,$nolink,$chunksz,$morder,$advsrch,$chunksz,$aids,$masschange=false,$pv) {
            global $PHP_SELF,$Language;
            $hp = CodeX_HTMLPurifier::instance();
            $html_result = "";
        
            // Build the list of links to use for column headings
            // Used to trigger sort on that column
            $result_fields = $this->getResultFields();      
            
            $links_arr = array();
            $title_arr = array();
            $width_arr = array();

            if ( count($result_fields) == 0 ) return;

            reset($result_fields);
            while (list(,$field) = each($result_fields)) {
                if ($pv != 0) {
		    $links_arr[] = $url.'&pv='.(int)$pv.'&order='.urlencode($field->getName()).'#results';
		} else {
		    $links_arr[] = $url.'&order='.urlencode($field->getName()).'#results';
		}	
                $title_arr[] =  $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) ;
                $width_arr[$field->getName()] = $field->getColWidth();
            }

            $query = $this->createQueryReport($prefs,$morder,$advsrch,$offset,$chunksz,$aids);
            $result = $this->getResultQueryReport($query);
            $rows = count($result);
                    
           /*
              Show extra rows for <-- Prev / Next -->
            */  
            $nav_bar ='<table width= "100%"><tr>';
            $nav_bar .= '<td width="40%" align ="left">';
        
            // If all artifacts on screen so no prev/begin pointer at all
            if ($total_rows > $chunksz) {
                if ($offset > 0) {
                    $nav_bar .=
                    '<A HREF="'.$url.'&offset=0#results" class="small"><B>&lt;&lt; '.$Language->getText('global','begin').'</B></A>'.
                    '&nbsp;&nbsp;&nbsp;'.
                    '<A HREF="'.$url.'&offset='.($offset-$chunksz).
                    '#results" class="small"><B>&lt; '.$Language->getText('global','prev').' '.(int)$chunksz.'</B></A></td>';
                } else {
                    $nav_bar .=
                        '<span class="disable">&lt;&lt; '.$Language->getText('global','begin').'&nbsp;&nbsp;&lt; '.$Language->getText('global','prev').' '.(int)$chunksz.'</span>';
                }
            }
        
            $nav_bar .= '</td>';
            
            $offset_last = min($offset+$chunksz-1, $total_rows-1);
        
            #display 'Items x - y'  only in normal and printer-version modes
	    if ($pv != 2) {
	    $nav_bar .= '<td width= "20% " align = "center" class="small">'.$Language->getText('tracker_include_report','items').' '.($offset+1).' - '.
                ($offset_last+1)."</td>\n";
	    }	
        
            $nav_bar .= '<td width="40%" align ="right">';
        
            // If all artifacts on screen, no next/end pointer at all
            if ($total_rows > $chunksz) {
                if ( ($offset+$chunksz) < $total_rows ) {
        
                    $offset_end = ($total_rows - ($total_rows % $chunksz));
                    if ($offset_end == $total_rows) { $offset_end -= $chunksz; }
        
                    $nav_bar .= 
                        '<A HREF="'.$url.'&offset='.($offset+$chunksz).
                        '#results" class="small"><B>'.$Language->getText('global','next').' '.(int)$chunksz.' &gt;</B></A>'.
                        '&nbsp;&nbsp;&nbsp;'.
                        '<A HREF="'.$url.'&offset='.($offset_end).
                        '#results" class="small"><B>'.$Language->getText('global','end').' &gt;&gt;</B></A></td>';
                } else {
                    $nav_bar .= 
                        '<span class="disable">'.$Language->getText('global','next').' '.(int)$chunksz.
                        ' &gt;&nbsp;&nbsp;'.$Language->getText('global','end').' &gt;&gt;</span>';
                }
            }
            $nav_bar .= '</td>';
            $nav_bar .="</tr></table>\n";
        
            $html_result .= $nav_bar;

	    if ($masschange) {
               	$html_result .= '<FORM NAME="artifact_list" action="" METHOD="POST">';
               	$html_result .= html_build_list_table_top ($title_arr,$links_arr,true);
            } else {
               	$html_result .= html_build_list_table_top ($title_arr,$links_arr);
            }

            for ($i=0; $i < $rows ; $i++) {

                $html_result .= '<TR class="'.get_priority_color($result[$i]['severity_id']) .'">'."\n";

                if ($masschange) {
                        $html_result .= '<TD align="center"><INPUT TYPE="checkbox" name="mass_change_ids[]" value="'.$result[$i]['artifact_id'].'"></td>';
                }

                reset($result_fields);  
                while (list($key,$field) = each($result_fields) ) {
                    //echo "$key=".$result[$i][$key]."<br>";
                                    
				    $value = $result[$i][$key];
				    if ($width_arr[$key]) {
						$width = 'WIDTH="'.$width_arr[$key].'%"';
				    } else {
						$width = '';
				    }
				    $width .= ' class="small"';
				    
				    if ( $field->isDateField() ) {
						if ($value) {
							if ($field->getName() == 'last_update_date') {
								$html_result .= "<TD $width>".format_date("Y-m-d H:i",$value).'</TD>'."\n";
							} else {
								$html_result .= "<TD $width>".format_date("Y-m-d",$value).'</TD>'."\n";
							}	
						} else {
						    $html_result .= '<TD align="center">-</TD>';
						}
				    } else if ($field->getName() == 'artifact_id') {
						if ($nolink) 
						    $html_result .= "<TD $width>".  $hp->purify($value, CODEX_PURIFIER_CONVERT_HTML) ."</TD>\n";
						else {
						    $target = ($pv == 0 ? "" : " target=blank");
						    $html_result .= "<TD $width>".'<A HREF="/tracker/?func=detail&aid='.
						    urlencode($value).'&atid='.(int)$this->group_artifact_id.'&group_id='.(int)$group_id.'"'.$target.'>'. 
						    $value .'</A></TD>'."\n";
						}    
	                        
				    } else if ( $field->isUsername() ) {        
						if ($nolink)
						    $html_result .= "<TD $width>".util_user_link($value)."</TD>\n";
						else
						    $html_result .= "<TD $width>".util_multi_user_link($value)."</TD>\n";
					
					} else if ( $field->isFloat() ) {
						$html_result .= "<TD $width>". number_format($value,2) .'&nbsp;</TD>'."\n";
					} else if( $field->isTextArea()){
                        $text = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", nl2br(util_make_links($value,$group_id)));
                        $text = str_replace('  ', '&nbsp; ', $text);
                        $text = str_replace('  ', '&nbsp; ', $text);
                        $html_result .= '<TD '. $width .' style="font-family:monospace; font-size:10pt;">'.  $hp->purify($text, CODEX_PURIFIER_BASIC, $group_id)  .'&nbsp;</TD>';
				    } else{
				    	$html_result .= "<TD $width>".  $hp->purify($value, CODEX_PURIFIER_LIGHT, $group_id)  .'&nbsp;</TD>'."\n";
				    }                             
                                
                } // while 
                $html_result .= "</tr>\n";
            }
        
            $html_result .= '</TABLE>';

            if ($masschange) {
               	$html_result .= '
       <script language="JavaScript">
       <!--
              function checkAll(val) {
                       al=document.artifact_list;
                       len = al.elements.length;
                       var i=0;
                       for( i=0 ; i<len ; i++) {
                               if (al.elements[i].name==\'mass_change_ids[]\') {al.elements[i].checked=val;}
                      }
               }
       //-->
       </script>';

		$html_result .= '<INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$this->group_artifact_id.'">
                          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$group_id.'">
                          <INPUT TYPE="HIDDEN" NAME="report_id" VALUE="'.(int)$this->report_id.'">
                          <INPUT TYPE="HIDDEN" NAME="func" VALUE="masschange_detail">';
		// get the query
		while (list($field,$value) = each($prefs) ) {
			if (is_array($value)) {
				while (list(,$val) = each($value)) {
					$html_result .= '<INPUT TYPE="HIDDEN" NAME="'. $hp->purify($field, CODEX_PURIFIER_CONVERT_HTML) .'[]" VALUE="'. $hp->purify($val, CODEX_PURIFIER_CONVERT_HTML) .'">';	
				}
			} else {
				$html_result .= '<INPUT TYPE="HIDDEN" NAME="'. $hp->purify($field, CODEX_PURIFIER_CONVERT_HTML) .'" VALUE="'. $hp->purify($value, CODEX_PURIFIER_CONVERT_HTML) .'">';
			}
		}
		#stuff related to mass-change (buttons, check_all_items link, clear_all_items link) should be hidden in printer version
		#as well as table-only view. keep only 'select' column checkboxes
		if ($pv == 0) {		
		    if ($total_rows > $chunksz) {
               		$html_result .=
           	    	'<a href="javascript:checkAll(1)">'.$Language->getText('tracker_include_report','check_items').' '.($offset+1).'-'.($offset_last+1).'</a>'.
               		' - <a href="javascript:checkAll(0)">'.$Language->getText('tracker_include_report','clear_items').' '.($offset+1).'-'.($offset_last+1).'</a><p>';
		
	      	 	$html_result .= '<table width= "100%"><tr><td width="50%" align ="center" class="small">';
			$html_result .= '<INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('tracker_masschange_detail','selected_items').'('.($offset+1).'-'.($offset_last+1).')">';
			$html_result .= '</td><td width="50%" align ="center" class="small">';
			$html_result .= '<INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('tracker_include_report','mass_change_all',(int)$total_rows).'">';
			
		    } else {
			$html_result .=
           	    	'<a href="javascript:checkAll(1)">'.$Language->getText('tracker_include_report','check_all_items').'</a>'.
               		' - <a href="javascript:checkAll(0)">'.$Language->getText('tracker_include_report','clear_all_items').' </a><p>';
		
			$html_result .= '<table width= "100%"><tr><td width="60%" align ="center" class="small">';
			$html_result .= '<INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('tracker_masschange_detail','selected_items',array(1,(int)$total_rows)).'">';			
		    }
		}
		$html_result .= '</td></tr></table>';		
		$html_result .= '</FORM>';
            } else {
               	$html_result .= $nav_bar;
            }

            
            return $html_result;
        }

        /**
         *  Display the report
         *
         *  @param prefs: array of parameters used for the current query
         *  @param group_id,report_id,set,advsrch,msort,morder,order,pref_stg,offset,chunksz,pv: HTTP get variables
         *
         *      @return string
         *
         */
        function displayReport($prefs,$group_id,$report_id,$set,$advsrch,$msort,$morder,$order,$pref_stg,$offset,$chunksz,$pv,$masschange=false) {
            $hp = CodeX_HTMLPurifier::instance();
	  global $ath,$art_field_fact,$Language;
                
	  $html_result = '<script type="text/javascript" src="/scripts/calendar_js.php"></script>';

                // Display browse informations if any
                if ( $ath->getBrowseInstructions() && $pv == 0) {
                        $html_result .=  $hp->purify($ath->getBrowseInstructions(), CODEX_PURIFIER_FULL) ;
                }
                
                $html_result .= '
                          <FORM ACTION="" METHOD="GET" NAME="artifact_form">
                          <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$this->group_artifact_id.'">
                          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$group_id.'">';
		if ($masschange) {
                          $html_result .= ' 
			  <INPUT TYPE="HIDDEN" NAME="func" VALUE="masschange">';
		} else {
			  $html_result .= '
			  <INPUT TYPE="HIDDEN" NAME="func" VALUE="browse">';
		}

                $html_result .= '
			  <INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
                          <INPUT TYPE="HIDDEN" NAME="advsrch" VALUE="'. $hp->purify($advsrch, CODEX_PURIFIER_CONVERT_HTML) .'">
                          <INPUT TYPE="HIDDEN" NAME="msort" VALUE="'. $hp->purify($msort, CODEX_PURIFIER_CONVERT_HTML) .'">
			  <TABLE BORDER="0" CELLPADDING="0" CELLSPACING="5">
                          <TR><TD colspan="'.(int)$this->fields_per_line.'" nowrap>';

                
                //Show the list of available artifact reports
                if ($pv == 0) {
                    $res_report = $this->getReports($this->group_artifact_id,user_getid());
                    $box_name = 'report_id" onChange="document.artifact_form.go_report.click()';

                        $html_result .= '<b>'.$Language->getText('tracker_include_report','using_report');
                        $html_result .= html_build_select_box($res_report,$box_name,$report_id,false,'',false,'',false,'', CODEX_PURIFIER_CONVERT_HTML);
                        $html_result .= '<input VALUE="'.$Language->getText('tracker_include_report','btn_go').'" NAME="go_report" type="submit">'.'</b>';
                }
                
                // Start building the URL that we use to for hyperlink in the form
                $url = "/tracker/?atid=".(int)$this->group_artifact_id."&group_id=". (int)$group_id ."&set=".  $hp->purify($set, CODEX_PURIFIER_CONVERT_HTML)  ."&msort=".  $hp->purify($msort, CODEX_PURIFIER_CONVERT_HTML) ;
                if ($masschange) {
                    $url .= '&func=masschange';
                }
                
                if ($set == 'custom') {
                     $url .= $pref_stg;
                } else {
                     $url .= '&advsrch='. $hp->purify($advsrch, CODEX_PURIFIER_CONVERT_HTML) ;
                }
                
                $url_nomorder = $url;
                if ($pv != 0) {
                    $url_nomorder .= "&pv=". (int)$pv;
                }
                $url .= "&morder=".  $hp->purify($morder, CODEX_PURIFIER_CONVERT_HTML) ;

                $em =& EventManager::instance();
                $params = array('url'=>&$url);
                $em->processEvent('tracker_urlparam_processing', $params);
                $url_nomorder = $url;
                
                // Build the URL for alternate Search
                if ($advsrch) { 
                    $url_alternate_search = str_replace('advsrch=1','advsrch=0',$url);
                    $text = $Language->getText('tracker_include_report','simple_search');
                } else {    
                    $url_alternate_search = str_replace('advsrch=0','advsrch=1',$url); 
                    $text = $Language->getText('tracker_include_report','adv_search');
                }
                
                if ($pv == 0) {
                     $html_result .= '<small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;('.$Language->getText('tracker_include_report','or_use').' <a href="'.
                         $url_alternate_search.'">'. $hp->purify($text, CODEX_PURIFIER_CONVERT_HTML) .'</a>)</small></h3><p>';
                     $params = array('html_result' =>&$html_result);
                     $em->processEvent('tracker_form_browse_add_in', $params);
                }
                
                //$html_result .= $html_select;
                
                $html_result .= '</TABLE>';

                // Display query fields
		if ($pv != 2) {
                    $html_result .= $this->displayQueryFields($prefs,$advsrch,$pv);
                
                    if ($pv == 0) {
                        $html_result .= '<p><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_browse').'"></FONT> '.
                            '<input TYPE="text" name="chunksz" size="3" MAXLENGTH="5" '.
                            'VALUE="'. (int)$chunksz.'">&nbsp;'. $hp->purify($ath->getItemName(), CODEX_PURIFIER_CONVERT_HTML) .$Language->getText('tracker_include_report','at_once').'</FORM>';
                    }
                }
		
                //
                // Finally display the result table
                // 
                
                $totalrows = $this->selectReportItems($prefs,$morder,$advsrch,$aids); // Filter according to permissions

                if ($totalrows > 0) {
                
                    // Build the sorting header messages
                    if ($pv != 2) {    
		        if ( $morder ) {
                                $order_statement = $Language->getText('tracker_include_report','sorted_by').' '.($pv != 0 ? '':help_button('ArtifactBrowsing.html#ArtifactListSorting',false)).
                                    ' : '.$this->criteriaListToText($morder, $url_nomorder);
                        } else {
                                $order_statement ='';
                        }
                    
                        $html_result .= '<A name="results"></A>';
			$html_result .= '<h3>'.(int)$totalrows.' '.$Language->getText('tracker_include_report','matching').' '. $order_statement .'</h3>';
                
                        if ($pv == 0) {
                                $html_result .= '<P>'.$Language->getText('tracker_include_report','sort_results').' ';
                                $field = $art_field_fact->getFieldFromName('severity');
                                if ( $field && $field->isUsed()) {
				  $html_result .= $Language->getText('global','or').' <A HREF="'.$url.'&order=severity#results"><b>'. $hp->purify($Language->getText('tracker_include_report','sort_sev',$field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</b></A> ';
                                }
                                $html_result .= $Language->getText('global','or').' <A HREF="'.$url.'&order=#results"><b>'.$Language->getText('tracker_include_report','reset_sort').'</b></a>. ';
                        }
                    
                        if ($msort) { 
                                $url_alternate_sort = str_replace('msort=1','msort=0',$url).
                                    '&order=#results';
                                $text = $Language->getText('global','deactivate');
			} else {    
                                $url_alternate_sort = str_replace('msort=0','msort=1',$url).
                            '&order=#results';
                                $text = $Language->getText('global','activate');
                        }
                
                        if ($pv == 0) {
                                $html_result .= $Language->getText('tracker_include_report','multicolumn_sort',array($url_alternate_sort,$text)).'&nbsp;&nbsp;&nbsp;&nbsp;'.
                                    '(<a href="'.$url.'&pv=1"> <img src="'.util_get_image_theme("msg.png").'" border="0">'.
                                    '&nbsp;'.$Language->getText('global','printer_version').'</a>)'."\n";
                        }
		    }    
                
                    if ($pv != 0) { $chunksz = 100000; }
                    $html_result .= $this->showResult($group_id,$prefs,$offset,$totalrows,$url,($pv == 1 ? true:false),$chunksz,$morder,$advsrch,$chunksz,$aids,$masschange,$pv);
                    if ($pv != 2) {  
		        #priority colors are not displayed in table-only view 
		        $html_result .= $this->showPriorityColorsKey($Language->getText('tracker_include_report','sev_colors'),$aids,$masschange,$pv);
                    }
                } else {
                
                    $html_result .= '<h2>'.$Language->getText('tracker_include_report','no_match').'</h2>';
                    $html_result .= db_error();
                
                }
        
                echo $html_result;
                
                //return $html_result;
        }

        /**
         * Return a label for the scope code
         *
         * param scope: the scope code
         *
         * @return string
         */
        function getScopeLabel($scope) {
	  global $Language;

            switch ( $scope ) {
            case 'P':
                return $Language->getText('global','Project');
            case 'I':
                return $Language->getText('global','Personal');
            case 'S':
                return $Language->getText('global','System');
            }
        }                   

    /**
     * Return a link for the setting default report
     *
     * param default_val: the default report  value
     * @return string
     */
    
    function getDefaultLink($default_val,$scope,$report_id) {
        $g = $GLOBALS['ath']->getGroup();
        $group_id = $g->getID();
        $atid = $GLOBALS['ath']->getID();
        if (($scope != 'S') && ($scope != 'I')) {
            switch ( $default_val ) {
                case 0:
                    return '<a href="/tracker/admin/?func=report&group_id='.$group_id.'&atid='.$atid.'&update_default='.$report_id.'">'.$GLOBALS['Language']->getText('tracker_include_report','set_default').'</a>';
                case 1:
                    return '<b>'.$GLOBALS['Language']->getText('tracker_include_report','is_default').'</b>';
                default:
                    return '<a href="/tracker/admin/?func=report&group_id='.$group_id.'&atid='.$atid.'&update_default='.$report_id.'">'.$GLOBALS['Language']->getText('tracker_include_report','set_default').'</a>';
            }
        } else {
            return '<b>-</b>';
        }
    }
	  	 
        /**
         * Display the report list
         *
         * param : $reports      the list the reports within an artifact to display
         *
         * @return void
         */
        function showAvailableReports($reports) {
            $hp = CodeX_HTMLPurifier::instance();
                global $ath,$Language;
                
                $g = $ath->getGroup();
                $group_id = $g->getID();
                $atid = $ath->getID();

                $ath->adminHeader(array ('title'=>$Language->getText('tracker_include_report','report_mgmt'),
                                    'help' => 'TrackerAdministration.html#TrackerReportManagement'));
                $trackerName = $ath->getName();
        
                echo '<H2>'.$Language->getText('tracker_import_admin','tracker').' \'<a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'">';
                echo $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODEX_PURIFIER_CONVERT_HTML);
                echo '</a>\''.$Language->getText('tracker_include_report','report_admin').'</H2>';
                
                 if ($reports) {
                // Loop through the list of all artifact report
                $title_arr=array();
                $title_arr[]=$Language->getText('tracker_include_report','id');
                $title_arr[]=$Language->getText('tracker_include_report','report_name');
                $title_arr[]=$Language->getText('tracker_include_artifact','desc');
                $title_arr[]=$Language->getText('tracker_include_report','scope');
                if ($ath->userIsAdmin()) {
                    $title_arr[]=$Language->getText('tracker_include_report','default');
                }
                $title_arr[]=$Language->getText('tracker_include_canned','delete');
                
                echo '<p>'.$Language->getText('tracker_include_report','mod');
                echo html_build_list_table_top ($title_arr);
                $i=0;
                while ($arr = db_fetch_array($reports)) {
                    
                    echo '<TR class="'. util_get_alt_row_color($i) .'"><TD>';

                    if ( $arr['scope'] == 'S' || (!$ath->userIsAdmin()&&($arr['scope'] == 'P')) ) {
		      echo (int)$arr['report_id'];
		    } else {
		      echo '<A HREF="/tracker/admin/?func=report&group_id='.(int)$group_id.
                            '&show_report=1&report_id='.(int)$arr['report_id'].'&group_id='.(int)$group_id.'&atid='.(int)$ath->getID().'">'.
                             $hp->purify($arr['report_id'], CODEX_PURIFIER_CONVERT_HTML) .'</A>';
                    }

                    echo "</td><td>". $hp->purify($arr['name'], CODEX_PURIFIER_CONVERT_HTML) .'</td>'.
                        "<td>". $hp->purify($arr['description'], CODEX_PURIFIER_BASIC, $group_id) .'</td>'.
                        '<td align="center">'. $hp->purify($this->getScopeLabel($arr['scope']), CODEX_PURIFIER_CONVERT_HTML) .'</td>';
                    
                        $name = $arr['name'];
        
                    if ($ath->userIsAdmin()) {
                        echo "\n<td align=\"center\">".$this->getDefaultLink($arr['is_default'],$arr['scope'],$arr['report_id']).'</td>';
                    }
                    echo "\n<td align=\"center\">";
        			if ( $arr['scope'] == 'S' || (!$ath->userIsAdmin()&&($arr['scope'] == 'P')) ) {
	                    echo '-';
        			} else {
	                    echo '<A HREF="/tracker/admin/?func=report&group_id='.(int)$group_id.
	                        '&atid='.(int)$atid.'&delete_report=1&report_id='.(int)$arr['report_id'].
	                        '" onClick="return confirm(\''.$Language->getText('tracker_include_report','delete_report', $hp->purify(addslashes($name), CODEX_PURIFIER_CONVERT_HTML)).'\');">'.
	                            '<img src="'.util_get_image_theme("ic/trash.png").'" border="0"></A>';
					}
					        
                    echo '</td></tr>';
                    $i++;
                } 
                echo '</TABLE>';
            } else {
                echo '<p><h3>'.$Language->getText('tracker_include_report','no_rep_def').'</h3>';
            }
        
            echo '<P> '.$Language->getText('tracker_include_report','create_report',array('/tracker/admin/?func=report&group_id='.(int)$group_id.'&atid='.(int)$atid.'&new_report=1'));
        }

        /**
         *  Display the report form
         *
         *  @return void
         */
        function createReportForm() {
            $hp = CodeX_HTMLPurifier::instance();
                global $ath,$Language;
                
                $g = $ath->getGroup();
                $group_id = $g->getID();
                $atid = $ath->getID();

                $ath->adminHeader(array ('title'=>$Language->getText('tracker_include_report','create_rep'),
                                    'help' => 'TrackerAdministration.html#TrackerReportSetting'));

		echo '<H2>'.$Language->getText('tracker_import_admin','tracker').' \'<a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'">'. $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODEX_PURIFIER_CONVERT_HTML) .'</a>\'  - '.$Language->getText('tracker_include_report','create_rep').' </H2>';
    
            // display the table of all fields that can be included in the report
            $title_arr=array();
            $title_arr[]=$Language->getText('tracker_include_report','field_label');
            $title_arr[]=$Language->getText('tracker_include_artifact','desc');
            $title_arr[]=$Language->getText('tracker_include_report','search_crit');
            $title_arr[]=$Language->getText('tracker_include_report','rank_search');
            $title_arr[]=$Language->getText('tracker_include_report','rep_col');
            $title_arr[]=$Language->getText('tracker_include_report','rank_repo');      
            $title_arr[]=$Language->getText('tracker_include_report','col_width');     
        
            echo'       
                <FORM ACTION="/tracker/admin/" METHOD="POST">
                   <INPUT TYPE="HIDDEN" NAME="func" VALUE="report">
                   <INPUT TYPE="HIDDEN" NAME="create_report" VALUE="y">
                   <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$group_id.'">
                   <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$atid.'">
                   <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="1">
                   <B>'.$Language->getText('tracker_include_artifact','name').':</B>
                   <INPUT TYPE="TEXT" NAME="rep_name" VALUE="" CLASS="textfield_small" MAXLENGTH="80">
                   &nbsp;&nbsp;&nbsp;&nbsp;<B>'.$Language->getText('tracker_include_report','scope').': </B>';
            
            if ($ath->userIsAdmin()) {
                        echo '<SELECT ID="rep_scope" NAME="rep_scope" onchange="if (document.getElementById(\'rep_scope\').value == \'P\') {document.getElementById(\'rep_default\').disabled=false} else { document.getElementById(\'rep_default\').disabled=true;document.getElementById(\'rep_default\').checked=false }">
                                        <OPTION VALUE="I">'.$Language->getText('global','Personal').'</OPTION>
                                        <OPTION VALUE="P">'.$Language->getText('global','Project').'</OPTION>
                                        </SELECT>';
                        echo '&nbsp;&nbsp;&nbsp;&nbsp;<B>'.$Language->getText('tracker_include_report','default').':</B>'.'<INPUT TYPE="CHECKBOX" ID="rep_default" NAME="rep_default" DISABLED>';
            } else {
                        echo $Language->getText('global','Personal').' <INPUT TYPE="HIDDEN" NAME="rep_scope" VALUE="I">';
                        echo '&nbsp;&nbsp;&nbsp;&nbsp;<B>'.$Language->getText('tracker_include_report','default').':</B>'.'<INPUT TYPE="CHECKBOX" ID="rep_default" NAME="rep_default" DISABLED>';
            }
            echo ' <P>
                    <B>'.$Language->getText('tracker_include_artifact','desc').': </B>
                     <INPUT TYPE="TEXT" NAME="rep_desc" VALUE="" SIZE="50" MAXLENGTH="120">
                          <P>';
        
            echo html_build_list_table_top ($title_arr);
            $i=0;
            $aff = new ArtifactFieldFactory($ath);
            $fields = $aff->getAllUsedFields();
            while ( list($key, $field) = each($fields)) {
        
                // Do not show fields not used by the project
                if ( !$field->isUsed()) { continue; }
        
                // Do not show some special fields any way 
                if ($field->isSpecial()) { 
                    if ( ($field->getName() == 'group_id') ||
                         ($field->getName() == 'comment_type_id') )
                        { continue; }
                }
        
                //Do not show unreadable fields
                if (!$ath->userIsAdmin() && !$field->userCanRead($group_id, $this->group_artifact_id)) {
                    continue;
                }

                $cb_search = 'CBSRCH_'.$field->getName();
                $cb_report = 'CBREP_'.$field->getName();
                $tf_search = 'TFSRCH_'.$field->getName();
                $tf_report = 'TFREP_'.$field->getName();
                $tf_colwidth = 'TFCW_'.$field->getName();
                echo '<TR class="'. util_get_alt_row_color($i) .'">';
                
                echo "\n<td>".$field->label.'</td>'.
                    "\n<td>".$field->description.'</td>'.
                    "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_search.'" value="1"></td>'.
                    "\n<td align=\"center\">".'<input type="text" name="'.$tf_search.'" value="" size="5" maxlen="5"></td>'.        
                    "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_report.'" value="1"></td>'.
                    "\n<td align=\"center\">".'<input type="text" name="'.$tf_report.'" value="" size="5" maxlen="5"></td>'.        
                    "\n<td align=\"center\">".'<input type="text" name="'.$tf_colwidth.'" value="" size="5" maxlen="5"></td>'.      
                    '</tr>';
                $i++;
            }
            echo '</TABLE>'.
                '<P><CENTER><INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('global','btn_submit').'"></CENTER>'.
                '</FORM>';
        
        }

        /**
         *  Display detail report form
         *
         *  @return void
         */
        function showReportForm() {
            $hp = CodeX_HTMLPurifier::instance();
                global $ath, $Language;
                
                $g = $ath->getGroup();
                $group_id = $g->getID();
                $atid = $ath->getID();

                $ath->adminHeader(array ('title'=>$Language->getText('tracker_include_report','modify_report'),
                                    'help' => 'TrackerAdministration.html#TrackerReportSetting'));
                  
		echo '<H2>'.$Language->getText('tracker_import_admin','tracker').' \'<a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'">'. $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODEX_PURIFIER_CONVERT_HTML) .'</a>\' -  '.$Language->getText('tracker_include_report','modify_report').' \''. $hp->purify($this->name, CODEX_PURIFIER_CONVERT_HTML) .'\'</H2>';
        
                    
            // display the table of all fields that can be included in the report
            // along with their current state in this report
            $title_arr=array();
            $title_arr[]=$Language->getText('tracker_include_report','field_label');
            $title_arr[]=$Language->getText('tracker_include_artifact','desc');
            $title_arr[]=$Language->getText('tracker_include_report','search_crit');
            $title_arr[]=$Language->getText('tracker_include_report','rank_search');
            $title_arr[]=$Language->getText('tracker_include_report','rep_col');
            $title_arr[]=$Language->getText('tracker_include_report','rank_repo');      
            $title_arr[]=$Language->getText('tracker_include_report','col_width');     
                
            echo '<FORM ACTION="/tracker/admin/" METHOD="POST">
                   <INPUT TYPE="HIDDEN" NAME="func" VALUE="report">
                   <INPUT TYPE="HIDDEN" NAME="update_report" VALUE="y">
                   <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$atid.'">
                   <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$group_id.'">
                   <INPUT TYPE="HIDDEN" NAME="report_id" VALUE="'.(int)$this->report_id.'">
                   <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="1">
                   <B>'.$Language->getText('tracker_include_artifact','name').': </B>
                   <INPUT TYPE="TEXT" NAME="rep_name" VALUE="'. $hp->purify($this->name, CODEX_PURIFIER_CONVERT_HTML) .'" CLASS="textfield_small" MAXLENGTH="80">
                         &nbsp;&nbsp;&nbsp;&nbsp;<B>'.$Language->getText('tracker_include_report','scope').': </B>';
            $scope = $this->scope;
            if ($ath->userIsAdmin()) {
                        echo '<SELECT ID="rep_scope" NAME="rep_scope" onchange="if (document.getElementById(\'rep_scope\').value == \'P\') {document.getElementById(\'rep_default\').disabled=false} else { document.getElementById(\'rep_default\').disabled=true;document.getElementById(\'rep_default\').checked=false }" >
                                        <OPTION VALUE="I"'.($scope=='I' ? 'SELECTED':'').'>'.$Language->getText('global','Personal').'</OPTION>
                                        <OPTION VALUE="P"'.($scope=='P' ? 'SELECTED':'').'>'.$Language->getText('global','Project').'</OPTION>
                                        </SELECT>';
                        echo '&nbsp;&nbsp;&nbsp;&nbsp;<B>'.$Language->getText('tracker_include_report','default').':</B>'.'<INPUT TYPE="CHECKBOX" ID="rep_default" NAME="rep_default" '.($this->is_default == 1 ? 'CHECKED':'').' '.($this->scope != 'P' ? 'DISABLED':'').'>';
            } else {
                        echo ($scope=='P' ? $Language->getText('global','Project'):$Language->getText('global','Personal')).
                            '<INPUT TYPE="HIDDEN" NAME="rep_scope" VALUE="'. $hp->purify($scope, CODEX_PURIFIER_CONVERT_HTML) .'">';
                        echo '&nbsp;&nbsp;&nbsp;&nbsp;<B>'.$Language->getText('tracker_include_report','default').':</B>'.'<INPUT TYPE="CHECKBOX" ID="rep_default" NAME="rep_default" '.($this->is_default == 1 ? 'CHECKED':'').' DISABLED >';
            }
            echo '
                    <P>
                    <B>'.$Language->getText('tracker_include_artifact','desc').':</B>
                    <INPUT TYPE="TEXT" NAME="rep_desc" VALUE="'. $hp->purify($this->description, CODEX_PURIFIER_CONVERT_HTML) .'" SIZE="50" MAXLENGTH="120">
                          <P>';
        
            echo html_build_list_table_top ($title_arr);
            $i=0;
            $aff = new ArtifactFieldFactory($ath);
            $fields = $aff->getAllUsedFields();
            while ( list($key, $field) = each($fields) ) {
        
                // Do not show fields not used by the project
                if ( !$field->isUsed()) { continue; }
        
                // Do not show some special fields any way 
                if ($field->isSpecial()) { 
                    if ( ($field->getName() == 'group_id') ||
                         ($field->getName() == 'comment_type_id') )
                        { continue; }
                }
                
                //Do not show unreadable fields
                if (!$ath->userIsAdmin() && !$field->userCanRead($group_id, $this->group_artifact_id)) {
                    continue;
                }
                $cb_search = 'CBSRCH_'.$field->getName();
                $cb_report = 'CBREP_'.$field->getName();
                $tf_search = 'TFSRCH_'.$field->getName();
                $tf_report = 'TFREP_'.$field->getName();
                $tf_colwidth = 'TFCW_'.$field->getName();
                
                $rep_field = null;
                if (isset($this->fields[$field->getName()])) {
                        $rep_field = $this->fields[$field->getName()];
                }
                if (!$rep_field) {
                  $rep_field = new ArtifactReportField();
                }       
        
                $cb_search_chk = ($rep_field->isShowOnQuery() ? 'CHECKED':'');
                $cb_report_chk = ($rep_field->isShowOnResult() ? 'CHECKED':'');
                $tf_search_val = $rep_field->getPlaceQuery();
                $tf_report_val = $rep_field->getPlaceResult();
                $tf_colwidth_val = $rep_field->getColWidth();
        
                echo '<TR class="'. util_get_alt_row_color($i) .'">';
                
                echo "\n<td>".$field->getLabel().'</td>'.
                    "\n<td>".$field->getDescription().'</td>'.
                    "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_search.'" value="1" '.$cb_search_chk.' ></td>'.
                    "\n<td align=\"center\">".'<input type="text" name="'.$tf_search.'" value="'.$tf_search_val.'" size="5" maxlen="5"></td>'.      
                    "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_report.'" value="1" '.$cb_report_chk.' ></td>'.
                    "\n<td align=\"center\">".'<input type="text" name="'.$tf_report.'" value="'.$tf_report_val.'" size="5" maxlen="5"></td>'.      
                    "\n<td align=\"center\">".'<input type="text" name="'.$tf_colwidth.'" value="'.$tf_colwidth_val.'" size="5" maxlen="5"></td>'.          
                    '</tr>';
                $i++;
            }
            echo '</TABLE>'.
                '<P><CENTER><INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('global','btn_submit').'"></CENTER>'.
                '</FORM>';

        }


}

?>
