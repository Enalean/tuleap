<?php
// --src = source file path
// --dst = dest file path

ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once __DIR__ . '/../../../src/www/include/pre.php';

/**
 * Extract parameters from user input
 *
 * This function reassamble user submitted values splited by PHP. PHP transform
 * user input in an array, the cut is done on spaces (each space create a new
 * entry, even when string is encapsulated between double quotes).
 * The separator is -- and each argument must be like "--argname="
 *
 * @param array $argv
 * @return array
 */
function extract_params($argv)
{
    $arguments = array();
    for ($i = 1; $i < count($argv); ++$i) {
        $arg = $argv[$i];
        // If arg start by "--" this is the beginning of a new option
        if (strpos($arg, "--") === 0) {
            $eqpos = strpos($arg, "=");
            $argname = substr($arg, 2, $eqpos - 2);
            $arguments[$argname] = substr($arg, $eqpos + 1);
        } else {
            $arguments[$argname] .= " " . $arg;
        }
    }
    return $arguments;
}



function getLdapFromUserName($username)
{
    static $list;
    if (!isset($list[$username])) {
        $user = UserManager::instance()->getUserByUserName($username);
        if ($user) {
            $res = db_query('SELECT ldap_uid FROM plugin_ldap_user WHERE user_id = ' . $user->getId());
            if (!db_error($res) && db_numrows($res) === 1) {
                $list[$username] = strtolower(db_result($res, 0, 'ldap_uid'));
            } else {
                $list[$username] = false;
            }
        } else {
            $list[$username] = false;
        }
    }
    return $list[$username];
}

/**
 * Copy/paste adapted from svn_utils_parse_access_file in 'www/svn/svn_utils.php'
 */
function svn_utils_convert_access_file_to_ldap(LDAP_UserManager $ldapUm, $srcFileName, $dstFileName)
{
    $newContent = '';

    $f = fopen($srcFileName, "rb");
    if ($f === false) {
        echo "** ERROR: $srcFileName: No such file or directory" . PHP_EOL;
    } else {
        $path_pat    = '/^\s*\[(.*)\]/'; // assume no repo name 'repo:'
        $perm_pat    = '/^\s*([^=]*)\s*=\s*(.*)$/';
        $group_pat   = '/^\s*([^ ]*)\s*=\s*(.*)$/';
        $empty_pat   = '/^\s*$/';
        $comment_pat = '/^\s*#/';

        $ST_START = 0;
        $ST_GROUP = 1;
        $ST_PATH = 2;

        $state = $ST_START;

        $content = file($srcFileName, FILE_IGNORE_NEW_LINES);
        foreach ($content as $line) {
            if (preg_match($comment_pat, $line) || preg_match($empty_pat, $line)) {
                $output = $line;
            } else {
                $m = preg_match($path_pat, $line, $matches);
                if ($m) {
                    $path = $matches[1];
                    if ($path == "groups") {
                        $state = $ST_GROUP;
                    } else {
                        $state = $ST_PATH;
                    }
                }

                if ($state == $ST_GROUP) {
                    $m = preg_match($group_pat, $line, $matches);
                    if ($m) {
                        $group = $matches[1];
                        $users = $matches[2];

                        $uarray = array_map('trim', explode(",", strtolower($users)));
                        $ldapLogins = array();
                        foreach ($uarray as $user) {
                            if (strpos($user, '@') === 0) {
                                $ldapLogins[] = $user;
                            } else {
                                $lr = getLdapFromUserName($user);
                                if ($lr !== false) {
                                    $ldapLogins[] = $lr;
                                }
                            }
                        }
                        $output = $group . ' = ' . implode(', ', $ldapLogins);
                    } else {
                        $output = $line;
                    }
                } elseif ($state == $ST_PATH) {
                    $m = preg_match($perm_pat, $line, $matches);
                    if ($m) {
                        $who = $matches[1];
                        $perm = $matches[2];

                        if (strpos($who, '@') === 0) {
                            $output = $line;
                        } elseif (trim(rtrim($who)) != '*') {
                            $lr = getLdapFromUserName($who);
                            if ($lr !== false) {
                                $output = $lr . ' = ' . $perm;
                            } else {
                                $output = '#' . $line;
                            }
                        } else {
                            $output = $line;
                        }
                    } else {
                        $output = $line;
                    }
                } else {
                    $output = $line;
                }
            }

            $newContent .= $output . "\n";
            //$line = strtok($separator);
        }
        //fclose($f);

        // Write new file
        $fd = fopen($dstFileName, "w");
        if (!$fd) {
            echo "** ERROR: $dstFileName: Not writable" . PHP_EOL;
        } else {
            fwrite($fd, $newContent);
            fclose($fd);
        }
    }
}

$pluginManager = PluginManager::instance();
$ldapPlugin    = $pluginManager->getPluginByName('ldap');
if ($ldapPlugin && $plugin_manager->isPluginAvailable($ldapPlugin)) {
    $ldapUm = $ldapPlugin->getLdapUserManager();

    $args = extract_params($_SERVER['argv']);
    if (isset($args['src']) && isset($args['dst'])) {
        svn_utils_convert_access_file_to_ldap($ldapUm, $args['src'], $args['dst']);
    } elseif (isset($args['all'])) {
        $sql = 'SELECT groups.group_id, unix_group_name FROM groups LEFT JOIN plugin_ldap_svn_repository USING (group_id) WHERE status = "A" AND ldap_auth IS NULL';
        $res = db_query($sql);
        while ($row = db_fetch_array($res)) {
            //foreach (new DirectoryIterator($args['all']) as $dirInfo) {
            //if($dirInfo->isDot()) continue;
            $svnaccessfile = new SplFileInfo('/svnroot/' . $row['unix_group_name'] . '/.SVNAccessFile');
            if ($svnaccessfile->isFile()) {
                echo "Process " . $row['unix_group_name'] . PHP_EOL;
                if (copy($svnaccessfile->getPathname(), $svnaccessfile->getPathname() . '.beforeldap')) {
                    svn_utils_convert_access_file_to_ldap(
                        $ldapUm,
                        $svnaccessfile->getPathname() . '.beforeldap',
                        $svnaccessfile->getPathname()
                    );
                    db_query('INSERT INTO plugin_ldap_svn_repository(group_id, ldap_auth) VALUES(' . $row['group_id'] . ',1)');
                }
            }
        }
    } else {
        echo "** ERROR: either --src or --dst are missing" . PHP_EOL;
    }
}
