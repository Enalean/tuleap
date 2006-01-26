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

$Language->loadLanguageMsg('survey/survey');

// add radio button in database, when submitted
if ($GLOBALS['create_submit']) {
    survey_data_radio_create($group_id,$question_id,$answer,$rank);    
}


/*
	Select all radio buttons from the database
*/

$sql="SELECT * ".
    "FROM survey_radio_choices ".
    "WHERE question_id='$question_id' AND group_id='$group_id' ".
    "ORDER BY choice_rank";
$result=db_query($sql);

?>

<P>
<H2><?php echo $Language->getText('survey_admin_browse_radio','edit_r'); ?></H2>
<?php 

echo $Language->getText('survey_admin_browse_radio','edit_r_msg'); 

survey_utils_show_radio_list($result);
survey_utils_show_radio_create_form($group_id, $question_id);

?>




