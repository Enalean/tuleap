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

survey_header(array('title'=>$Language->getText('survey_admin_update_radio','update_r'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingQuestions'));



/*
	Select all radio buttons from the database
*/


?>

<P>
<H2><?php echo $Language->getText('survey_admin_update_radio','update_r'); ?></H2>

<H3><?php echo $Language->getText('survey_admin_update_question','warn'); ?></h3>
<P>

<?php

survey_utils_show_radio_form($group_id, $question_id, $choice_id);
survey_footer(array());

?>




