<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('../snippet/snippet_utils.php');

if (user_isloggedin()) {

	if ($post_changes) {
		/*
			Create a new snippet entry, then create a new snippet version entry
		*/
		if ($name && $description && $language != 0 && $category != 0 && $version) {
			/*
				Create the new package
			*/
			$sql="INSERT INTO snippet_package (category,created_by,name,description,language) ".
				"VALUES ('$category','".user_getid()."','".htmlspecialchars($name)."','".htmlspecialchars($description)."','$language')";
			$result=db_query($sql);
			if (!$result) {
				//error in database
				$feedback .= ' ERROR DOING SNIPPET PACKAGE INSERT! ';
				snippet_header(array('title'=>'Submit A New Snippet Package'));
				echo db_error();
				snippet_footer(array());
				exit;
			} else {
				$feedback .= ' Snippet Package Added Successfully. ';
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
					$feedback .= ' ERROR DOING SNIPPET PACKAGE VERSION INSERT! ';
					snippet_header(array('title'=>'Submit A New Snippet Package'));
					echo db_error();
					snippet_footer(array());
					exit;
				} else {
					//so far so good - now add snippets to the package
					$feedback .= ' Snippet Package Version Added Successfully. ';

					//id for this snippet_package_version
					$snippet_package_version_id=db_insertid($result);
					snippet_header(array('title'=>'Add Snippets to Package'));

/*
	This raw HTML allows the user to add snippets to the package
*/

					?>

<SCRIPT LANGUAGE="JavaScript">
<!--
function show_add_snippet_box() {
	newWindow = open("","occursDialog","height=500,width=300,scrollbars=yes,resizable=yes");
	newWindow.location=('/snippet/add_snippet_to_package.php?suppress_nav=1&snippet_package_version_id=<?php 
			echo $snippet_package_version_id; ?>');
}
// -->
</script>
<BODY onLoad="show_add_snippet_box()">

<H2>Now add snippets to your package</H2>
<P>
<span class="highlight"><B>IMPORTANT!</B></span>
<P>
If a new window opened, use it to add snippets to your package. 
If a new window did not open, use the following link to add to your package BEFORE you leave this page.
<P>
<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id=<?php echo $snippet_package_version_id; ?>" TARGET="_blank">Add Snippets To Package</A>
<P>
<B>Browse the library</B> to find the snippets you want to add, 
then add them using the new window link shown above.
<P>

					<?php

					snippet_footer(array());
					exit;
				}
			}
		} else {
			exit_error('Error','Error - Go back and fill in all the information');
		}

	}
	snippet_header(array('title'=>'Submit A New Snippet Package',
			     'header'=>'Create a New Snippet Package',
			     'help' => 'TheCodeXMainMenu.html#GroupingCodeSnippets'));

	?>
	<P>
	You can group together existing snippets into a package using this interface. Before 
	creating your package, make sure all your snippets are in place and you have made a note 
	of the snippet ID's.
	<P>
	<OL>
	<LI>Create the package using this form.
	<LI><B>Then</B> use the "Add Snippets to Package" link to add files to your package.
	</OL>
	<P>
	<span class="highlight"><B>Note:</B></span> You can submit a new version of an existing package by 
	browsing the library and using the link on the existing package. You should only use this 
	page if you are submitting an entirely new package.
	<P>
	<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<INPUT TYPE="HIDDEN" NAME="changes" VALUE="First Posted Version">

	<TABLE>

	<TR><TD COLSPAN="2"><B>Title:</B><BR>
		<INPUT TYPE="TEXT" NAME="name" SIZE="45" MAXLENGTH="60">
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Description:</B><BR>
		<TEXTAREA NAME="description" ROWS="5" COLS="45" WRAP="SOFT"></TEXTAREA>
	</TD></TR>

	<TR>
	<TD><B>Language:</B><BR>
		<?php echo html_build_select_box (snippet_data_get_all_languages(),'language'); ?>
		<BR>
		<A HREF="/support/?func=addsupport&group_id=1">Suggest a Language</A>
	</TD>

	<TD><B>Category:</B><BR>
		<?php echo html_build_select_box (snippet_data_get_all_categories(),'category'); ?>
		<BR>
		<A HREF="/support/?func=addsupport&group_id=1">Suggest a Category</A>
	</TD>
	</TR>
 
	<TR><TD COLSPAN="2"><B>Version:</B><BR>
		<INPUT TYPE="TEXT" NAME="version" SIZE="10" MAXLENGTH="15">
	</TD></TR>
  
	<TR><TD COLSPAN="2" ALIGN="center">
		<B>Make sure all info is complete and accurate</B>
		<BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	</TD></TR>

	</TABLE>
	<?php
	snippet_footer(array());

} else {

	exit_not_logged_in();

}

?>
