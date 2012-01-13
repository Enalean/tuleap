<?php

class SVN_Apache_Configuration {
    private $projects = array();
    
    public function __construct($projects) {
        $this->projects = $projects;
    }
    
    public function getFullConf() {
        $conf = $this->getApacheConfHeaders();
        foreach ($this->projects as $row) {
            $conf .= $this->getProjectSVNApacheConf($row);
        }
        return $conf;
    }
    
    /**
     *  Define specific log file for SVN queries
     * @return string 
     */
    protected function getApacheConfHeaders() {
        $ret = "# Codendi SVN repositories\n\n";
        $ret = "# Custom log file for SVN queries\n";
        $ret = 'CustomLog logs/svn_log "%h %l %u %t %U %>s \"%{SVN-ACTION}e\"" env=SVN-ACTION'."\n\n";
        return $ret;
    }
    
    /**
     * Replace double quotes by single quotes in project name (conflict with Apache realm name)
     * 
     * @param String $str
     * @return String
     */
    protected function escapeStringForApacheConf($str) {
        return strtr($str, "\"", "'");
    }
    
    protected function getProjectSVNApacheConf($row) {
        $conf = '';
        $conf .= "<Location /svnroot/".$row['unix_group_name'].">\n";
        $conf .= "    DAV svn\n";
        $conf .= "    SVNPath ".$GLOBALS['svn_prefix']."/".$row['unix_group_name']."\n";
        $conf .= "    SVNIndexXSLT \"/svn/repos-web/view/repos.xsl\"\n";
        $conf .= $this->getProjectSVNApacheConfAuthz($row);
        $conf .= $this->getProjectSVNApacheConfAuth($row);
        $conf .= "</Location>\n\n";
        return $conf;
    }

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

    protected function getProjectSVNApacheConfDefault($projectName) {
        $conf = '';
        $conf .= "    Require valid-user\n";
        $conf .= "    AuthType Basic\n";
        $conf .= "    AuthName \"Subversion Authorization (".$this->escapeStringForApacheConf($projectName).")\"\n";
        return $conf;
    }
    
    protected function getProjectSVNApacheConfAuthz($row) {
        $conf = "    AuthzSVNAccessFile ".$GLOBALS['svn_prefix']."/".$row['unix_group_name']."/.SVNAccessFile\n";
        return $conf;
    }
}

?>
