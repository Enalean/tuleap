<?php

/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


require_once('pre.php');
require('../forum/forum_utils.php');
$Language->loadLanguageMsg('forum/forum');

if ( !user_isloggedin()) {
    exit_not_logged_in();
    return;
}

if ($forum_id) {

    $result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");
    $group_id=db_result($result,0,'group_id');
    $forum_name=db_result($result,0,'forum_name');
    
    $params=array('title'=>group_getname($group_id).' forum: '.$forum_name,
                      'pv'   =>isset($pv)?$pv:false);
    forum_header($params);
    forum_footer($params);

} else {

    forum_header(array('title'=>$Language->getText('global','error')));
    echo '<H1'.$Language->getText('forum_forum','choose_forum_first').'</H1>';
    forum_footer(array());

}
  
?>