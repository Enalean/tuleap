<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
//
//
// Originally written by Benjamin Ninassi 2008, Codendi Team, Xerox
//

require_once('pre.php');

use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\DescriptionFieldsDao;

session_require(array('group'=>'1','admin_flags'=>'A'));

$delete_desc_id=$request->get('delete_group_desc_id');
if($delete_desc_id){

	$sql="DELETE FROM group_desc where group_desc_id='".db_ei($delete_desc_id)."'";
	$result=db_query($sql);

	    if (!$result) {
	    	list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
	    	exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('admin_desc_fields','del_desc_field_fail',array($host,db_error())));
	    }

	$sql="DELETE FROM group_desc_value where group_desc_id='".db_ei($delete_desc_id)."'";
	$result=db_query($sql);

	if (!$result) {
    	list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
    	exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('admin_desc_fields','del_desc_field_fail',array($host,db_error())));
	}else{
		$GLOBALS['Response']->addFeedback('info', $Language->getText('admin_desc_fields','remove_success'));
	}
}

$make_required_desc_id=$request->get('make_required_desc_id');
$remove_required_desc_id=$request->get('remove_required_desc_id');
if($make_required_desc_id){
	$sql="UPDATE group_desc SET desc_required='1' where group_desc_id='".db_ei($make_required_desc_id)."'";
	$result=db_query($sql);
	if (!$result) {
    	list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
    	exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('admin_desc_fields','update_required_desc_field_fail',array($host,db_error())));
	}else{
		$GLOBALS['Response']->addFeedback('info', $Language->getText('admin_desc_fields','update_success'));
	}
}

if($remove_required_desc_id){
	$sql="UPDATE group_desc SET desc_required='0' where group_desc_id='".db_ei($remove_required_desc_id)."'";
	$result=db_query($sql);
	if (!$result) {
    	list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
    	exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('admin_desc_fields','update_required_desc_field_fail',array($host,db_error())));
	}else{
		$GLOBALS['Response']->addFeedback('info', $Language->getText('admin_desc_fields','update_success'));
	}
}

$update=$request->get('Update');
$add_desc=$request->get('Add_desc');
$desc_name= $request->get('form_name');
$desc_description= $request->get('form_desc');
$desc_type= $request->get('form_type');
$desc_rank= $request->get('form_rank');
$desc_required= $request->get('form_required');

if ($add_desc || $update) {

	//data validation
	$valid_data=1;
	if (!trim($desc_name) || !trim($desc_description)) {
	    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_desc_fields', 'info_missed'));
		$valid_data=0;
	}

	if (!is_numeric($desc_rank)) {
	    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_desc_fields', 'info_rank_noint'));
		$valid_data=0;
	}

	if($valid_data==1){

		if($add_desc){
			$sql="INSERT INTO group_desc (desc_name, desc_description, desc_rank, desc_type, desc_required) ";
			$sql.=	"VALUES ('".db_escape_string($desc_name)."','".db_escape_string($desc_description)."','";
			$sql.= db_escape_string($desc_rank)."','".db_es($desc_type)."','".db_ei($desc_required)."')";
			$result=db_query($sql);

		    if (!$result) {
		    	list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
		    	exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('admin_desc_fields','ins_desc_field_fail',array($host,db_error())));
		    }else{

		    	$GLOBALS['Response']->addFeedback('info', $Language->getText('admin_desc_fields','add_success'));

		    }
		}else{

			$sql="UPDATE group_desc SET ";
			$sql.= "desc_name='".db_escape_string($desc_name)."',";
			$sql.= "desc_description='".db_escape_string($desc_description)."',";
			$sql.= "desc_rank='".db_escape_string($desc_rank)."',";
			$sql.= "desc_type='".db_escape_string($desc_type)."',";
			$sql.= "desc_required='".db_escape_string($desc_required)."'";
			$sql.= " WHERE group_desc_id='".db_ei($request->get('form_desc_id'))."'";

			$result=db_query($sql);

		    if (!$result || db_affected_rows($result) < 1) {
		    	$GLOBALS['Response']->addFeedback('error', $Language->getText('admin_desc_fields','update_desc_field_fail',(db_error() ? db_error() : ' ' )));
		    }else{
		    	$GLOBALS['Response']->addFeedback('info', $Language->getText('admin_desc_fields','update_success'));

		    }


		}
	}
	if(($valid_data==1)||(($valid_data==0)&&($update))){

		$desc_name= '';
		$desc_description= '';
		$desc_type= '';
		$desc_rank= '';
		$desc_required= '';
	}
}


$HTML->header(array('title'=>$Language->getText('admin_desc_fields', 'title'), 'main_classes' => array('tlp-framed')));

echo "<H2>".$Language->getText('admin_desc_fields','header')."</H2>";

$hp = Codendi_HTMLPurifier::instance();

$update_fields_desc_id=$request->getValidated('update_fields_desc_id', 'uint', 0);
if($update_fields_desc_id){

	$sql = "SELECT * FROM group_desc WHERE group_desc_id='".db_ei($update_fields_desc_id)."'";
	$result_update = db_query($sql);
	$row_update = db_fetch_array($result_update);

	echo "<form action='desc_fields_edit.php' method='post'>";

	echo "<INPUT TYPE='HIDDEN' NAME='form_desc_id' VALUE='".$update_fields_desc_id."'>";

	echo "<p>".$Language->getText('admin_desc_fields','desc_name')." ; ";
	echo "<br><input type='text' size='71' maxlen='255' name='form_name' value='".$hp->purify($row_update['desc_name'],CODENDI_PURIFIER_CONVERT_HTML)."'></br></p>";

	echo "<p>".$Language->getText('admin_desc_fields','desc_description')." ; ";
	echo "<br><TEXTAREA cols='70' rows='5' wrap='virtual' name='form_desc'>".$hp->purify($row_update['desc_description'],CODENDI_PURIFIER_CONVERT_HTML)."</TEXTAREA></br></p>";



	echo "<p>".$Language->getText('admin_desc_fields','rank_on_screen')." : ";
	echo "<input type='text' size='5'  maxlen='5' name='form_rank' value='".$hp->purify($row_update['desc_rank'],CODENDI_PURIFIER_LIGHT)."'></p>";


	echo "<p>".$Language->getText('admin_desc_fields','desc_type')." : ";

	if($row_update['desc_type']=="text"){
		echo "<SELECT name='form_type'>
			<OPTION selected value='text'>".$Language->getText('admin_desc_fields','desc_text')."
			<OPTION value='line'>".$Language->getText('admin_desc_fields','desc_line')."
			</SELECT></p>";
	}else{
		echo "<SELECT name='form_type'>
			<OPTION selected value='line'>".$Language->getText('admin_desc_fields','desc_line')."
			<OPTION value='text'>".$Language->getText('admin_desc_fields','desc_text')."
			</SELECT></p>";
	}

	echo "<p>".$Language->getText('admin_desc_fields','desc_required')." : ";
	echo "<INPUT TYPE='HIDDEN' NAME='form_required' VALUE='0'>";
	echo "<INPUT TYPE='CHECKBOX' NAME='form_required' VALUE='1'";
	echo (($row_update['desc_required']==1) ? " CHECKED" : "" )."></p>";

		echo "<INPUT type='submit' name='Update' class='tlp-button-primary' value='".$Language->getText('global','btn_update')."'>";
	echo "</form>";
}else{

        $fields_factory  = new DescriptionFieldsFactory(new DescriptionFieldsDao());
        $descfieldsinfos = $fields_factory->getAllDescriptionFields();

	echo "<HR>";
	$title_arr=array();
	$title_arr[]=$Language->getText('admin_desc_fields','desc_name');
	$title_arr[]=$Language->getText('admin_desc_fields','desc_description');
	$title_arr[]=$Language->getText('admin_desc_fields','desc_required');
	$title_arr[]=$Language->getText('admin_desc_fields','desc_type');

	$title_arr[]=$Language->getText('admin_desc_fields','rank_on_screen');
	$title_arr[]=$Language->getText('admin_desc_fields','del?');


	echo html_build_list_table_top($title_arr);



	for($i=0;$i<sizeof($descfieldsinfos);$i++){

		$desc_name_inst=$descfieldsinfos[$i]["desc_name"];
                $matches =array();
		if(preg_match('/(.*):(.*)/', $desc_name_inst, $matches)) {

			if ($Language->hasText($matches[1], $matches[2])) {
	    		$desc_name_inst = $Language->getText($matches[1], $matches[2]);
			}
		}
		$desc_desc=$descfieldsinfos[$i]["desc_description"];
		if(preg_match('/(.*):(.*)/', $desc_desc, $matches)) {

			if ($Language->hasText($matches[1], $matches[2])) {
	    		$desc_desc = $Language->getText($matches[1], $matches[2]);
			}
		}


		echo "<TR class='".util_get_alt_row_color($i)."'>";

		echo "<TD><a href='desc_fields_edit.php?update_fields_desc_id=".$descfieldsinfos[$i]['group_desc_id']."'>".$hp->purify($desc_name_inst,CODENDI_PURIFIER_BASIC)."</a></TD>";
		echo "<TD>".$hp->purify($desc_desc,CODENDI_PURIFIER_LIGHT)."</TD>";
		if($descfieldsinfos[$i]['desc_required']==0){
			echo "<TD><a href='desc_fields_edit.php?make_required_desc_id=".$descfieldsinfos[$i]['group_desc_id']."'>".$Language->getText('admin_desc_fields','desc_no')."</a></TD>";
		}else{
			echo "<TD><a href='desc_fields_edit.php?remove_required_desc_id=".$descfieldsinfos[$i]['group_desc_id']."'>".$Language->getText('admin_desc_fields','desc_yes')."</a></TD>";
		}
		if($descfieldsinfos[$i]['desc_type']=='line'){
			echo "<TD>".$Language->getText('admin_desc_fields','desc_line')."</TD>";
		}else{
			echo "<TD>".$Language->getText('admin_desc_fields','desc_text')."</TD>";
		}
		echo "<TD>".$hp->purify($descfieldsinfos[$i]['desc_rank'],CODENDI_PURIFIER_LIGHT)."</TD>";
		echo "<TD><a href='desc_fields_edit.php?delete_group_desc_id=".$descfieldsinfos[$i]['group_desc_id']."'><IMG SRC='".util_get_image_theme("ic/trash.png")."' HEIGHT='16' WIDTH='16' BORDER='0' ALT='DELETE'></IMG></a></TD>";
		echo "</TR>";
	}

	echo "</TABLE>";


	echo "<HR>";

	echo "<H2>".$Language->getText('admin_desc_fields','header_add')."</H2>";

	echo "<form action='desc_fields_edit.php' method='post'>";
	echo "<p>".$Language->getText('admin_desc_fields','desc_name')." : ";
	echo "<br><input type='text' size='71' maxlen='255' name='form_name' value='". $hp->purify($desc_name, CODENDI_PURIFIER_CONVERT_HTML) ."'></br></p>";

	echo "<p>".$Language->getText('admin_desc_fields','desc_description')." : ";
	echo "<br><TEXTAREA name='form_desc' wrap='virtual' cols='70' rows='5'>". $hp->purify($desc_description, CODENDI_PURIFIER_CONVERT_HTML) ."</TEXTAREA></br></p>";

	echo "<p>".$Language->getText('admin_desc_fields','rank_on_screen')." : ";
	echo "<input type='text' size='5'  maxlen='5' name='form_rank'' value='". $hp->purify($desc_rank, CODENDI_PURIFIER_CONVERT_HTML) ."'></p>";
	echo "<p>".$Language->getText('admin_desc_fields','desc_type')." : ";

	echo "<SELECT name='form_type'>";
	if($desc_type=="text"){

		echo "<OPTION selected value='text'>".$Language->getText('admin_desc_fields','desc_text')."
		      <OPTION value='line'>".$Language->getText('admin_desc_fields','desc_line');
	}else{
		echo "<OPTION selected value='line'>".$Language->getText('admin_desc_fields','desc_line')."
		      <OPTION value='text'>".$Language->getText('admin_desc_fields','desc_text');
	}
	echo "</SELECT></p>";

	echo "<INPUT TYPE='HIDDEN' NAME='form_required' VALUE='0'>
		  <p>".$Language->getText('admin_desc_fields','desc_required')." : ";
	echo "<INPUT TYPE='CHECKBOX' NAME='form_required' VALUE='1')";
	echo (($desc_required==1) ? " CHECKED" : "" )."></p>";

	echo "<HR>
		  <p><input type='submit' name='Add_desc' class='tlp-button-primary' value=".$Language->getText('global','btn_submit')."></p>";
	echo "</form>";
}
$HTML->footer(array());

?>
