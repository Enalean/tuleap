<?php

require_once 'SVN_Apache.class.php';

class SVN_Apache_ModMysql extends SVN_Apache {

    protected function getProjectSVNApacheConfAuth($row) {
        $conf = '';
        $conf .= "    AuthMYSQLEnable on\n";
        $conf .= $this->getProjectSVNApacheConfDefault($row['group_name']);
        $conf .= "    AuthMySQLUser ".$GLOBALS['sys_dbauth_user']."\n";
        $conf .= "    AuthMySQLPassword ".$GLOBALS['sys_dbauth_passwd']."\n";
        $conf .= "    AuthMySQLDB ".$GLOBALS['sys_dbname']."\n";
        $conf .= "    AuthMySQLUserTable \"user, user_group\"\n";
        $conf .= "    AuthMySQLNameField user.user_name\n";
        $conf .= "    AuthMySQLPasswordField user.unix_pw\n";
        $conf .= "    AuthMySQLUserCondition \"(user.status='A' or (user.status='R' AND user_group.user_id=user.user_id and user_group.group_id=".$row['group_id']."))\"\n";
        return $conf;
    }
    
}

?>
