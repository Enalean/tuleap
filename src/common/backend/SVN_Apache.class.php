<?php

abstract class SVN_Apache {
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
    
    abstract protected function getProjectSVNApacheConfAuth($row);
    
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
    
    /**
     * Replace double quotes by single quotes in project name (conflict with Apache realm name)
     * 
     * @param String $str
     * @return String
     */
    protected function escapeStringForApacheConf($str) {
        return strtr($str, "\"", "'");
    }
}

?>
