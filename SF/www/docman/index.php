<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

/*
        Docmentation Manager
        by Quentin Cregan, SourceForge 06/2000
*/

require($DOCUMENT_ROOT.'/include/pre.php');
require('./doc_utils.php');

if (!$group_id) {
    exit_no_group();
}

$usermem = user_ismember($group_id);
$params=array('title'=>'Documentation for '.group_getname($group_id),
              'help'=>'DocumentManager.html',
              'pv'=>$pv);
docman_header($params);

if ($pv) {
    echo "<h3>Project Documentation</h3>";
} else {
    echo "<TABLE width='100%'><TR><TD>";
    echo '<H3>Project Documentation</H3>';
    echo "</TD>";
    echo "<TD align='left'> ( <A HREF='".$PHP_SELF."?group_id=$group_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;Printer version</A> ) </TD>";
    echo "</TR></TABLE>";    

}

//get a list of group numbers that this project owns
$query = "select * "
."from doc_groups "
."where group_id = $group_id "
."order by groupname";
$result = db_query($query); 

//otherwise, throw up an error
if (db_numrows($result) < 1) {
    print "<b>This project has no categorized data.</b><p>";
} else { 
    // get the groupings and display them with their members.
    while ($row = db_fetch_array($result)) {
        $query = "select description, docid, title, doc_group "
            ."from doc_data "
            ."where doc_group = '".$row['doc_group']."' "
            ."and stateid ='1'";
        //state 1 == 'active'
        if ($usermem == true) {
            $query .= " or stateid = '5' "
                ." and doc_group = '".$row['doc_group']."' ";
        } //state 5 == 'private' 
        
        $subresult = db_query($query); 
        
        if (!(db_numrows($subresult) < 1)) {
            print "<p><b>".$row['groupname']."</b>\n<ul>\n";
            while ($subrow = db_fetch_array($subresult)) {
                // LJ We want the title and the description to
                // possibly contain HTML and php code so unconvert
                // the initially encoded HTML chars and eval the text
                print "<li><a href=\"display_doc.php?docid=".$subrow['docid']."&group_id=".$group_id."\">";
                eval('?>'.util_unconvert_htmlspecialchars($subrow['title']));
                print "</a>";
                print "<BR><i>Description:</i> ";
                eval('?>'.util_unconvert_htmlspecialchars($subrow['description'])); 
                
            }
            print "</ul>\n\n";
            
        }
    }
}

docman_footer($params);

?>
