<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('account.php');
require ('timezones.php');

if (!user_isloggedin()) {
	exit_not_logged_in();
}

if ($submit) {	
	if (!$timezone) {
		$feedback .= ' Nothing Updated ';
	} else if ($timezone == 'None') {
		$feedback .= ' Please choose a timezone other than none. ';
	  
	} else {
		// if we got this far, it must be good
		db_query("UPDATE user SET timezone='$timezone' WHERE user_id=" . user_getid());
		session_redirect("/account/");
	}
}

$HTML->header(array('title'=>"Change Timezone"));

?>
<H3>Timezone Change</h3>
<P>
Now, no matter where you live, you can see all dates and times throughout <?php print $GLOBALS['sys_name']; ?>  
as if it were in your neighborhood.
<P>
<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<?php

echo '<H4><span class="feedback">'.$feedback.'</span></H4>';

echo html_get_timezone_popup ('timezone',user_get_timezone());

?>
<input type="submit" name="submit" value="Update">
</form>

<?php

$HTML->footer(array());

?>
