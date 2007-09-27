<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//


function format_bug_field_values($field, $group_id, $result) {

    $is_project_scope = bug_data_is_project_scope($field);
    $title_arr=array();
    if (!$is_project_scope) { $title_arr[]='ID'; }
    $title_arr[]='Value label';
    $title_arr[]='Description';
    $title_arr[]='Rank';
    $title_arr[]='Status';
    
    
    $hdr = html_build_list_table_top ($title_arr);
    
    $ia = $ih = 0;
    $status_stg = array('A' => 'Active', 'P' => 'Permanent', 'H' => 'Hidden');
    
    // Display the list of values in 2 blocks : active first
    // Hidden second
    while ( $fld_val = db_fetch_array($result) ) {
	
	$bug_fv_id = $fld_val['bug_fv_id'];
	$status = $fld_val['status'];	
	$value_id = $fld_val['value_id'];
	$value = $fld_val['value'];
	$description = $fld_val['description'];
	$order_id = $fld_val['order_id'];
	
	$html = '';
	
	// keep the rank of the 'None' value in mind if any (see below)
	if ($value == 100) { $none_rk = $order_id; }
	
	// Show the value ID only for system wide fields which
	// value id are fixed and serve as a guide.
	if (!$is_project_scope) 
	    !$html .='<td>'.$value_id.'</td>';
	
	// The permanent values can't be modified (No link)
	if ($status == 'P') {
	    $html .= '<td>'.$value.'</td>';
	} else {
	    $html .= '<td><A HREF="'.$PHP_SELF.'?update_value=1'.
		'&fv_id='.$bug_fv_id.'&field='.$field.
		'&group_id='.$group_id.'">'.$value.'</A></td>';
	}
	
	$html .= '<TD>'.$description.'&nbsp;</TD>'.
	    '<TD align="center">'.$order_id.'</TD>'.
	    '<TD align="center">'.$status_stg[$status].'</TD>';
	
	if ($status == 'A' || $status == 'P') {
	    $html = '<TR class="'. 
		util_get_alt_row_color($ia) .'">'.$html.'</tr>';
	    $ia++;
	    $ha .= $html;
	} else {
	    $html = '<TR class="'. 
		util_get_alt_row_color($ih) .'">'.$html.'</tr>';
	    $ih++;
	    $hh .= $html;
	}
    }
    
    
    //Display the list of values now
    if ($ia == 0) {
	$hdr = '<p>No Active value for this field. Create one or reactivate a hidden value (if any)<p>'.$hdr;
    } else {
	$ha = '<tr><td colspan="4"><center><b>---- ACTIVE VALUES ----</b></center></tr>'.$ha;		    
    }
    if ($ih) {
	$hh = '<tr><td colspan="4"> &nbsp;</td></tr>'.
	    '<tr><td colspan="4"><center><b>---- HIDDEN VALUES ----</b></center></tr>'.$hh;
    }
    
    echo $hdr.$ha.$hh.'</TABLE>';
}

?>
