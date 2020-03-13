<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<!-- $Id: passencrypt.php,v 1.6 2005/09/18 11:14:56 rurban Exp $ -->
<title>Password Encryption Tool</title>
<!--
Copyright 1999, 2000, 2001, 2002 $ThePhpWikiProgrammingTeam

This file is part of PhpWiki.

PhpWiki is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

PhpWiki is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with PhpWiki; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
-->
</head>
<body>
<h1>Password Encryption Tool</h1>
<?php
/**
 * Seed the random number generator.
 *
 * better_srand() ensures the randomizer is seeded only once.
 *
 * How random do you want it? See:
 * http://www.php.net/manual/en/function.srand.php
 * http://www.php.net/manual/en/function.mt-srand.php
 */
function better_srand($seed = '')
{
    static $wascalled = false;
    if (!$wascalled) {
        if ($seed === '') {
            list($usec, $sec) = explode(" ", microtime());
            if ($usec > 0.1) {
                $seed = (double) $usec * $sec;
            } else { // once in a while use the combined LCG entropy
                $seed = (double) 1000000 * substr(uniqid("", true), 13);
            }
        }
        if (function_exists('mt_srand')) {
            mt_srand($seed); // mersenne twister
        } else {
            srand($seed);
        }
        $wascalled = true;
    }
}

function rand_ascii($length = 1)
{
    better_srand();
    $s = "";
    for ($i = 1; $i <= $length; $i++) {
        // return only typeable 7 bit ascii, avoid quotes
        if (function_exists('mt_rand')) {
            // the usually bad glibc srand()
            $s .= chr(mt_rand(40, 126));
        } else {
            $s .= chr(rand(40, 126));
        }
    }
    return $s;
}

////
// Function to create better user passwords (much larger keyspace),
// suitable for user passwords.
// Sequence of random ASCII numbers, letters and some special chars.
// Note: There exist other algorithms for easy-to-remember passwords.
function random_good_password($minlength = 5, $maxlength = 8)
{
    $newpass = '';
    // assume ASCII ordering (not valid on EBCDIC systems!)
    $valid_chars = "!#%&+-.0123456789=@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz";
    $start = ord($valid_chars);
    $end   = ord(substr($valid_chars, -1));
    better_srand();
    if (function_exists('mt_rand')) { // mersenne twister
        $length = mt_rand($minlength, $maxlength);
    } else { // the usually bad glibc rand()
        $length = rand($minlength, $maxlength);
    }
    while ($length > 0) {
        if (function_exists('mt_rand')) {
            $newchar = mt_rand($start, $end);
        } else {
            $newchar = rand($start, $end);
        }
        if (! strrpos($valid_chars, $newchar)) {
            continue; // skip holes
        }
        $newpass .= sprintf("%c", $newchar);
        $length--;
    }
    return $newpass;
}

/** PHP5 deprecated old-style globals if !(bool)ini_get('register_long_arrays').
  *  See Bug #1180115
  * We want to work with those old ones instead of the new superglobals,
  * for easier coding.
  */
foreach (array('SERVER','GET','POST','ENV') as $k) {
    if (!isset($GLOBALS['HTTP_' . $k . '_VARS']) and isset($GLOBALS['_' . $k])) {
        $GLOBALS['HTTP_' . $k . '_VARS'] = $GLOBALS['_' . $k];
    }
}
unset($k);

$posted = $_POST;
if (!empty($posted['create'])) {
    $new_password = random_good_password();
    echo "<p>The newly created random password is:<br />\n<br />&nbsp;&nbsp;&nbsp;\n<tt><strong>",
         htmlentities($new_password),"</strong></tt></p>\n";
    $posted['password'] = $new_password;
    $posted['password2'] = $new_password;
}

if (($posted['password'] != "")
    && ($posted['password'] == $posted['password2'])) {
    $password = $posted['password'];
    /**
     * http://www.php.net/manual/en/function.crypt.php
     */
    // Use the maximum salt length the system can handle.
    $salt_length = max(
        CRYPT_SALT_LENGTH,
        2 * CRYPT_STD_DES,
        9 * CRYPT_EXT_DES,
        12 * CRYPT_MD5,
        16 * CRYPT_BLOWFISH
    );
    // Generate the encrypted password.
    $encrypted_password = crypt($password, rand_ascii($salt_length));
    $debug = $_GET['debug'];
    if ($debug) {
        echo "The password was encrypted using a salt length of: $salt_length<br />\n";
    }
    echo "<p>The encrypted password is:<br />\n<br />&nbsp;&nbsp;&nbsp;\n<tt><strong>",
         htmlentities($encrypted_password),"</strong></tt></p>\n";
    echo "<hr />\n";
} elseif ($posted['password'] != "") {
    echo "The passwords did not match. Please try again.<br />\n";
}
if (empty($REQUEST_URI)) {
    $REQUEST_URI = $_ENV['REQUEST_URI'];
}
if (empty($REQUEST_URI)) {
    $REQUEST_URI = $_SERVER['REQUEST_URI'];
}
?>

<form action="<?php echo $REQUEST_URI ?>" method="post">
<fieldset><legend accesskey="P">Encrypt</legend>
Enter a password twice to encrypt it:<br />
<input type="password" name="password" value="" /><br />
<input type="password" name="password2" value="" /> <input type="submit" value="Encrypt" />
</fieldset>
<br />
or:<br />
<br />
<fieldset><legend accesskey="C">Generate </legend>
Create a new random password: <input type="submit" name="create" value="Create" />
</fieldset>
</form>
</body>
</html>
