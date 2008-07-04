<?php
	
	echo "<B><u>".$GLOBALS['Language']->getText('register_desc', 'short_description')."<font color='red'>*</font>:</u></B>";
	echo "<BR>".$GLOBALS['Language']->getText('register_desc', 'short_description_desc')."</BR>";

?>
<br><TEXTAREA name="form_short_description" wrap="virtual" cols="70" rows="3"><?=isset($data['project']['form_short_description']) ? $data['project']['form_short_description'] : '' ?>
</TEXTAREA></br>


<?php
global $Language;
$Language->loadLanguageMsg('project/project');

$descfieldsinfos = getProjectsDescFieldsInfos();
$hp = CodeX_HTMLPurifier::instance();
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
	
	echo "<P><B><u>".$hp->purify($desc_name,CODEX_PURIFIER_LIGHT);
	if($descfieldsinfos[$i]["desc_required"]==1){
		echo "<font color='red'>*</font>";
	}
	echo ":</u></B><BR>".$hp->purify($desc_desc,CODEX_PURIFIER_LIGHT)."</BR>";
	
	if($descfieldsinfos[$i]["desc_type"]=='line'){
		
		echo "<BR><INPUT type='text' size='70' maxlen='70' name='form_".$descfieldsinfos[$i]["group_desc_id"];
			 
			 
		if(isset($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]])){
			echo "' value='".$hp->purify($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]],CODEX_PURIFIER_CONVERT_HTML);
		}
		echo "'></BR>" ; ;
		
	}else if($descfieldsinfos[$i]["desc_type"]=='text'){
		
		echo "<BR><TEXTAREA name='form_".$descfieldsinfos[$i]["group_desc_id"].
			 "' wrap='virtual' cols='70' rows='8'>";
			 
		if(isset($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]])){
			echo $hp->purify($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]],CODEX_PURIFIER_CONVERT_HTML);
		}
		echo "</TEXTAREA></BR>" ;
	}
	echo "</P>";
}

?>