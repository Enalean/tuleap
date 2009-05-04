<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

echo "<B><u>".$GLOBALS['Language']->getText('register_desc', 'short_description')."<font color='red'>*</font>:</u></B>";
	echo "<BR>".$GLOBALS['Language']->getText('register_desc', 'short_description_desc')."</BR>";

?>
<br><TEXTAREA name="form_short_description" wrap="virtual" cols="70" rows="3"><?=isset($data['project']['form_short_description']) ? $data['project']['form_short_description'] : '' ?>
</TEXTAREA></br>


<?php

global $Language;

$descfieldsinfos = getProjectsDescFieldsInfos();
$hp = Codendi_HTMLPurifier::instance();
for($i=0;$i<sizeof($descfieldsinfos);$i++){

	$desc_name=$descfieldsinfos[$i]["desc_name"];
	$desc_desc=$descfieldsinfos[$i]["desc_description"];
	if(preg_match('/(.*):(.*)/', $desc_name, $matches)) {			
		if ($Language->hasText($matches[1], $matches[2])) {
    		$desc_name = $Language->getText($matches[1], $matches[2]);
		}
	}
	
	if(preg_match('/(.*):(.*)/', $desc_desc, $matches)) {			
		if ($Language->hasText($matches[1], $matches[2])) {
    		$desc_desc = $Language->getText($matches[1], $matches[2]);
		}
	}
	
	echo "<P><B><u>".$hp->purify($desc_name,CODENDI_PURIFIER_BASIC);
	if($descfieldsinfos[$i]["desc_required"]==1){
		echo "<font color='red'>*</font>";
	}
	echo ":</u></B><BR>".$hp->purify($desc_desc,CODENDI_PURIFIER_LIGHT)."</BR>";
	
	if($descfieldsinfos[$i]["desc_type"]=='line'){
		
		echo "<BR><INPUT type='text' size='70' maxlen='70' name='form_".$descfieldsinfos[$i]["group_desc_id"];
			 
			 
		if(isset($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]])){
			echo "' value='".$hp->purify($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]],CODENDI_PURIFIER_CONVERT_HTML);
		}
		echo "'></BR>" ; ;
		
	}else if($descfieldsinfos[$i]["desc_type"]=='text'){
		
		echo "<BR><TEXTAREA name='form_".$descfieldsinfos[$i]["group_desc_id"].
			 "' wrap='virtual' cols='70' rows='8'>";
			 
		if(isset($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]])){
			echo $hp->purify($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]],CODENDI_PURIFIER_CONVERT_HTML);
		}
		echo "</TEXTAREA></BR>" ;
	}
	echo "</P>";
}

?>