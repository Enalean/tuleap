<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../snippet/snippet_utils.php');

$LANG->loadLanguageMsg('snippet/snippet');

if (user_isloggedin()) {

    if ($post_changes) {
        /*
			Create a new snippet entry, then create a new snippet version entry
        */
        if ($name && $description && $language != 0 && $category != 0 && $version) {
            if ($category==100) {
                $feedback .= ' '.$LANG->getText('snippet_details','select_category').' ';
            } else if ($language==100) {
                $feedback .= ' '.$LANG->getText('snippet_details','select_language').' ';
            } else {
                /*
				Create the new package
                */
                $sql="INSERT INTO snippet_package (category,created_by,name,description,language) ".
                    "VALUES ('$category','".user_getid()."','".htmlspecialchars($name)."','".htmlspecialchars($description)."','$language')";
                $result=db_query($sql);
                if (!$result) {
                    //error in database
                    $feedback .= ' '.$LANG->getText('snippet_package','error_p_insert').' ';
                    snippet_header(array('title'=>$LANG->getText('snippet_addversion','submit_p')));
                    echo db_error();
                    snippet_footer(array());
                    exit;
                } else {
                    $feedback .= ' '.$LANG->getText('snippet_package','p_add_success').' ';
                    $snippet_package_id=db_insertid($result);
                    /*
					create the snippet package version
                    */
                    $sql="INSERT INTO snippet_package_version ".
                        "(snippet_package_id,changes,version,submitted_by,date) ".
                        "VALUES ('$snippet_package_id','".htmlspecialchars($changes)."','".
                        htmlspecialchars($version)."','".user_getid()."','".time()."')";
                    $result=db_query($sql);
                    if (!$result) {
                        //error in database
                        $feedback .= ' '.$LANG->getText('snippet_addversion','errir_insert').' ';
                        snippet_header(array('title'=>$LANG->getText('snippet_addversion','submit_p')));
                        echo db_error();
                        snippet_footer(array());
                        exit;
                    } else {
                        //so far so good - now add snippets to the package
                        $feedback .= ' '.$LANG->getText('snippet_addversion','p_add_success').' ';

                        //id for this snippet_package_version
                        $snippet_package_version_id=db_insertid($result);
                        snippet_header(array('title'=>$LANG->getText('snippet_addversion','add')));

                        /*
                        This raw HTML allows the user to add snippets to the package
                        */

echo '
<SCRIPT LANGUAGE="JavaScript">
<!--
function show_add_snippet_box() {
	newWindow = open("","occursDialog","height=500,width=300,scrollbars=yes,resizable=yes");
	newWindow.location=(\'/snippet/add_snippet_to_package.php?suppress_nav=1&snippet_package_version_id='.$snippet_package_version_id.'\');
}
// -->
</script>
<BODY onLoad="show_add_snippet_box()">

<H2>'.$LANG->getText('snippet_addversion','now_add').'</H2>
<P>
<span class="highlight"><B>'.$LANG->getText('snippet_addversion','important').'</B></span>
<P>
'.$LANG->getText('snippet_addversion','important_comm').'
<P>
<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id=<?php echo $snippet_package_version_id; ?>" TARGET="_blank">'.$LANG->getText('snippet_addversion','add').'</A>
<P>
'.$LANG->getText('snippet_addversion','browse_lib').'
<P>';

                        snippet_footer(array());
                        exit;
                    }
                }
            }
        } else {
            exit_error($LANG->getText('global','error'),$LANG->getText('snippet_add_snippet_to_package','error_fill_all_info'));
        }

    }
    snippet_header(array('title'=>$LANG->getText('snippet_addversion','submit_p'),
			     'header'=>$LANG->getText('snippet_package','create_p'),
			     'help' => 'TheCodeXMainMenu.html#GroupingCodeSnippets'));


    echo '
	<P>
	'.$LANG->getText('snippet_package','group_s_into_p').'
	<P>
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<INPUT TYPE="HIDDEN" NAME="changes" VALUE="'.$LANG->getText('snippet_package','first_posted_v').'">

	<TABLE>

	<TR><TD COLSPAN="2"><B>'.$LANG->getText('snippet_browse','title').':</B><BR>
		<INPUT TYPE="TEXT" NAME="name" SIZE="45" MAXLENGTH="60">
	</TD></TR>

	<TR><TD COLSPAN="2"><B>'.$LANG->getText('snippet_package','description').'</B><BR>
		<TEXTAREA NAME="description" ROWS="5" COLS="45" WRAP="SOFT"></TEXTAREA>
	</TD></TR>

	<TR>
	<TD><B>'.$LANG->getText('snippet_package','language').'</B><BR>
		'.html_build_select_box (snippet_data_get_all_languages(),'language').'
	</TD>

	<TD><B>'.$LANG->getText('snippet_package','category').'</B><BR>
		'.html_build_select_box (snippet_data_get_all_categories(),'category').'
	</TD>
	</TR>
 
	<TR><TD COLSPAN="2"><B>'.$LANG->getText('snippet_addversion','version').'</B><BR>
		<INPUT TYPE="TEXT" NAME="version" SIZE="10" MAXLENGTH="15">
	</TD></TR>
  
	<TR><TD COLSPAN="2" ALIGN="center">
		<B>'.$LANG->getText('snippet_add_snippet_to_package','all_info_complete').'</B>
		<BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$LANG->getText('global','btn_submit').'">
	</TD></TR>

	</TABLE>';

    snippet_footer(array());

} else {

	exit_not_logged_in();

}

?>
