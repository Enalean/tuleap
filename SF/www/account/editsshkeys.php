<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "account.php";
session_require(array(isloggedin=>1));

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{

	if (!$GLOBALS["Update"]) {
		return 0;
	}

	$GLOBALS[form_authorized_keys] = trim($GLOBALS[form_authorized_keys]);
	$GLOBALS[form_authorized_keys] = ereg_replace("(\r\n)|(\n)","###",$GLOBALS[form_authorized_keys]);
	
	// if we got this far, it must be good
	db_query("UPDATE user SET authorized_keys='$GLOBALS[form_authorized_keys]' WHERE user_id=" . user_getid());
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	session_redirect("/account/");
} else { // not valid registration, or first time to page
	$HTML->header(array(title=>"Change Authorized Keys"));

?>

<h2>SSH Shared Keys <?php echo help_button('OtherServices.html#ShellAccount'); ?></h2>
<P>To avoid having to type your password every time for your SSH
developer account, you may upload your public key(s) here and they
will be placed on the server in your ~/.ssh/authorized_keys file.
<P>To generate a public key, run the program 'ssh-keygen' (or ssh-keygen1) on your desktop machine.
Then look at the file '~/.ssh/identity.pub' where the generated public key has been stored. Read the ssh
documentation for further information on sharing keys.

<?php
	$date = getdate(time());
	$hoursleft = ($sys_crondelay - 1) - ($date[hours] % $sys_crondelay);
	$minutesleft = 60 - $date[minutes];
?>
<P><b>IMPORTANT:</b>SSH Shared Keys Updates will be reflected in the next cron update in approximately <B><?php print $hoursleft.'</B> hours, <B>'.$minutesleft.'</B> minutes from now'; ?>. 

<?php if ($register_error) print "<p>$register_error"; ?>
<form action="editsshkeys.php" method="post">
<p>Authorized keys:
<BR><I>Important: Make sure there are no line breaks except between keys.
After submitting, verify that the number of keys in your file is what you expected.</I>
<br><TEXTAREA rows=10 cols=60 name="form_authorized_keys">
<?php
	$res_keys = db_query("SELECT authorized_keys FROM user WHERE user_id=".user_getid());
	$row_keys = db_fetch_array($res_keys);
	$authorized_keys = ereg_replace("###","\n",$row_keys[authorized_keys]);
	print $authorized_keys;
?>
</TEXTAREA>
<p><input type="submit" name="Update" value="Update">
</form>

<?php
}
$HTML->footer(array());

?>
