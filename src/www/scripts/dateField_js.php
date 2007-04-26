<?php
header("content-type: application/x-javascript");
require_once('pre.php');
$GLOBALS['Language']->loadLanguageMsg('calendar/calendar');
$description = $GLOBALS['Language']->getText('date_field', 'description');
$first_value = $GLOBALS['Language']->getText('date_field', 'first_value');
?>

// Title: date field picker
// Description: allow to choose a date field 
// Author: Mahmoud MAALEJ  at STMicroelectronics
//    header lines are left unchanged. Feel free

function show_cmb(arr1,arr2,form_object,date_field,css_theme_file){
    var array1 = arr1.split("-");
    var array2 = arr2.split("-");
    window.selecter = window.open('','_blank','width=270,height=40,status=no,resizable=yes,top=200,left=200');
    window.selecter.opener = self;
    window.selecter.focus();
    var str_buffer  = '<HTML>\n';
    str_buffer += '<HEAD>\n';
    str_buffer += '<link rel="stylesheet" href="'+css_theme_file+'" type="text/css" />\n';
    str_buffer += '<TITLE>field chooser</TITLE>\n';
    str_buffer += '</HEAD>\n';
    str_buffer += '<script>\n';
    str_buffer += 'var name;\n';
    str_buffer += 'var label;\n';
    str_buffer += 'function cmb_change () {\n';
    str_buffer += '    var cmb = document.choice.CMB_'+date_field+';\n';
    str_buffer += '    window.name = cmb.value;\n';
    str_buffer += '    window.label = cmb.options[cmb.selectedIndex].text;\n';
    str_buffer += '    window.opener.before_close("'+date_field+'");\n';
    str_buffer += '    window.close();\n';
    str_buffer += '    return(window.name);\n';
    str_buffer += '}\n';
    
    str_buffer += '</script>\n';
    str_buffer += '<BODY>\n';
    
    str_buffer += '<FORM name="choice">\n';
    str_buffer += '<?php echo $description;?><SELECT ID="CMB_'+date_field+'" NAME="CMB_'+date_field+'" onchange="javascript:cmb_change();">\n';
    
    str_buffer += '    <OPTION VALUE="'+'0'+'">'+'<?php echo $first_value;?>'+'</OPTION>\n';
    for(i=0;i<array1.length;i++){
        if ((array1[i] != '') && (array2[i] != '')) {
	    str_buffer += '    <OPTION VALUE="'+array1[i]+'">'+array2[i]+'</OPTION>\n';
	}
    }
    
    str_buffer += '</SELECT>\n';
    str_buffer += '</FORM>\n';
    str_buffer += '</BODY>\n';
    str_buffer += '</HTML>\n';
    window.selecter_doc = window.selecter.document;
    window.selecter_doc.write (str_buffer);
    window.selecter_doc.close();
}

function before_close(date_field){
    var txt    = document.artifact_form[date_field];
    var hidden = document.artifact_form["DTE_"+date_field+"_name"];
    txt.value  = window.selecter.label;
    hidden.value= window.selecter.name;
}
