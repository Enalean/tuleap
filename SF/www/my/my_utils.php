<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//



/*
  Function that generates hide/show urls to expand/collapse
  sections of the personal page

Input:
  $svc : service name to hide/show (sr, bug, pm...)
  $db_item_id : the item (group, forum, task sub-project,...) from the
     database that we are curently processing and about to display
  $item_id : the item_id as given in the URL and on which the show/hide switch
     is going to apply.
  $hide = hide param as given in the script URL (-1 means no param was given)

Output:
  $hide_url: URL to use in the page to switch from hide to show or vice versa
  $count_diff: difference between the number of items in the list between now and
     the previous last time the section was open (can be negative if items were removed)
  $hide_flag: true if the section must be hidden, false otherwise

*/
function my_hide_url ($svc, $db_item_id, $item_id, $count, $hide) {

    global $PHP_SELF;

    $pref_name = 'my_hide_'.$svc.$db_item_id;
    $old_pref_value = user_get_preference($pref_name);
    list($old_hide,$old_count) = explode('|', $old_pref_value);
  
    // Make sure they are both 0 if never set before
    if ($old_count == false) { $old_count = 0; }
    if ($old_hide == false) { $old_hide = 0; }

    if ($item_id == $db_item_id) {
		if (isset($hide)) {
		    $pref_value = "$hide|$count";
		} else {
		    $pref_value = "$old_hide|$count";
		    $hide = $old_hide;
		}
    } else {
		if ($old_hide) {
		    // if items are hidden keep the old count and keep pref as is
		    $pref_value = $old_pref_value;
		} else {
		    // only update the item count if the items are visible
		    // if they are hidden keep reporting the old count
		    $pref_value = "$old_hide|$count";
		}
		$hide = $old_hide;
    }

    // Update pref value if needed
    if ($old_pref_value != $pref_value) {
		user_set_preference($pref_name, $pref_value);
    }

    if ($hide) {
		$hide_url= '<a href="'.$PHP_SELF.'?hide_'.$svc.'=0&hide_item_id='.$db_item_id.'"><img src="'.util_get_image_theme("pointer_right.png").'" align="middle" border="0" alt="Collapse"></a>&nbsp;';
		$hide_now = true;
    } else {		
		$hide_url= '<a href="'.$PHP_SELF.'?hide_'.$svc.'=1&hide_item_id='.$db_item_id.'"><img src="'.util_get_image_theme("pointer_down.png").'" align="middle" border="0" alt="Expand"></a>&nbsp;';
		$hide_now = false;
    }

    return array($hide_now, $count-$old_count, $hide_url);
}

function my_format_as_flag($assigned_to, $submitted_by, $multi_assigned_to=null) {
    $AS_flag = '';
    if ($assigned_to == user_getid()) {
	$AS_flag = 'A';
    } else if ($multi_assigned_to) {
     // For multiple assigned to
       for ($i=0; $i<count($multi_assigned_to); $i++) {
            if ($multi_assigned_to[$i]==user_getid()) {
                $AS_flag = 'A';
            }
        }
    }
    if ($submitted_by == user_getid()) {
	$AS_flag .= 'S';
    }
    if ($AS_flag) { $AS_flag = '[<b>'.$AS_flag.'</b>]'; }

    return $AS_flag;
}

function my_item_count($total, $new) {
    return '['.$total.($new ? ", <b>$new new</b>]" : ']');
}

?>
