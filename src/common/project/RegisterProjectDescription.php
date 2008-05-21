<?php
	
	echo "<B><u>".$GLOBALS['Language']->getText('register_desc', 'short_description')."<font color='red'>*</font>:</u></B>";
	echo "<BR>".$GLOBALS['Language']->getText('register_desc', 'short_description_desc')."</BR>";

?>
<br><TEXTAREA name="form_short_description" wrap="virtual" cols="70" rows="3"><?=isset($data['project']['form_short_description']) ? $data['project']['form_short_description'] : '' ?>
</TEXTAREA></br>


<?php

$descfieldsinfos = getProjectsDescFieldsInfos();
$hp = CodeX_HTMLPurifier::instance();
for($i=0;$i<sizeof($descfieldsinfos);$i++){

	echo "<P><B><u>".$hp->purify($descfieldsinfos[$i]["desc_name"],CODEX_PURIFIER_LIGHT);
	if($descfieldsinfos[$i]["desc_required"]==1){
		echo "<font color='red'>*</font>";
	}
	echo ":</u></B><BR>".$hp->purify($descfieldsinfos[$i]["desc_description"],CODEX_PURIFIER_LIGHT)."</BR>";
	
	if($descfieldsinfos[$i]["desc_type"]=='line'){
		
		echo "<BR><TEXTAREA name='form_".$descfieldsinfos[$i]["group_desc_id"].
			 "' wrap='virtual' cols='70' rows='1'>";
			 
		if(isset($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]])){
			echo $hp->purify($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]],CODEX_PURIFIER_LIGHT);
		}
		echo "</TEXTAREA></BR>" ;
		
	}else if($descfieldsinfos[$i]["desc_type"]=='text'){
		
		echo "<BR><TEXTAREA name='form_".$descfieldsinfos[$i]["group_desc_id"].
			 "' wrap='virtual' cols='70' rows='8'>";
			 
		if(isset($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]])){
			echo $hp->purify($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]],CODEX_PURIFIER_LIGHT);
		}
		echo "</TEXTAREA></BR>" ;
	}
	echo "</P>";
	
}

?>