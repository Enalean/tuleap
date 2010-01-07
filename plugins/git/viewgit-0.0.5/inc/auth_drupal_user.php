<?php
/** @file
 * Restrict usage, based on drupal db.
 * @author David Konsumer <konsumer@jetboystudio.com>
 */

require_once($conf['drupal_site_config']);

// bad user, no cookie.
function auth_norights($message = 'You need to be logged in to use this site.')
{
	global $conf;
	header('WWW-Authenticate: Basic realm="'.$conf['app_title'].'"');
	header('HTTP/1.0 401 Unauthorized');
	die($message);
}

// do authentication.
// your auth function should get the user/pass for itself ($_REQUEST, $_SERVER['PHP_AUTH_USER'], etc)
// and should do the denying itself (die(), maybe a redirect to a registration page, etc)

// only this function will be called, with no arguments.
function auth_check()
{
	global $conf;
	global $db_url, $db_prefix;
	@$login_user = $_SERVER['PHP_AUTH_USER'];
	@$login_pass = $_SERVER['PHP_AUTH_PW'];

	if (empty($login_user)){
		auth_norights();
	}

	// parse drupal connect string
	preg_match_all("|(.+)://(.+)@(.+)/(.+)|", $db_url, $out);

	$type = $out[1][0];
	$user = $out[2][0];
	$passwd = '';
	$host = $out[3][0];
	$db = $out[4][0];

	$u = explode(':',$user);
	if (count($u)){
		$user = $u[0];
		@$passwd = $u[1];
	}

	if($type =='mysqli'){
		$type = 'mysql';
	}

	try {
		$dbh = new PDO("$type:host=$host;dbname=$db", $user, $passwd);
		$sth = $dbh->prepare("SELECT * FROM {$db_prefix}users WHERE name=? AND pass=?");
		$sth->execute(array($login_user, md5($login_pass)));
		$users = $sth->fetchAll();
		if (!count($users)){
			auth_norights();
		}
	} catch (Exception $e) {
		auth_norights('A database exception occured!');
	}

}
?>
