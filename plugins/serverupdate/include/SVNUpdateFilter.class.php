<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SVNUpdateFilter
 */


require_once("SVNCommit.class.php");

define("LEVEL_CRITERIA", "level");

define("LEVEL_VALUE_ANY", "any");
define("LEVEL_VALUE_MINOR", "minor");
define("LEVEL_VALUE_NORMAL", "normal");
define("LEVEL_VALUE_CRITICAL", "critical");

class SVNUpdateFilter {
    
    /**
     * @var array the filter for updates array($key=>$value)
     */
    var $filter;
    
    /**
     * SVNUpdateFilter constructor
     * Set the default filter (no filter)
     */
    function SVNUpdateFilter() {
        $this->filter = array();
        $this->filter[LEVEL_CRITERIA] = LEVEL_VALUE_ANY;
    }
    
    /**
     * Add a criteria to this filter
     *
     * @param string $key the name (key) of the criteria
     * @param string $value the value of the criteria
     */
    function addCriteria($key, $value) {
        $this->filter[$key] = $value;
    }
    
    /**
     * Get the criteria regarding to the the key
     *
     * @param string $key the key of the criteria we want to retrieve the value
     * @return string the value of the criteria corresponding to the key, or null if the key doesn't exist
     */
    function getCriteria($key) {
        if (isset($this->filter[$key])) {
            return $this->filter[$key];
        }
        return null;
    }
    
    /**
     * Apply the filter to the array of commits
     *
     * @param array{SVNCommit} the array of SVNCommits we want to filter.
     * @return array{SVNCommit} the array of SVNCommits that accept the filter
     */
    function apply($commits) {
        $commits_after_filter = array();
        foreach($commits as $commit) {
            if ($this->accept($commit)) {
                $commits_after_filter[] = $commit;
            }
        }
        return $commits_after_filter;
    }
    
    /**
     * Return a boolean syaing if the commit $commit accpet the filter or not
     * This is the method to tune if you want to add a criteria.
     *
     * @param Object{SVNCommit} the commit we want to test
     * @return boolean true if the commit accept the filter, false otherwise
     */
    function accept($commit) {
        $accept = false;
        if ($this->getCriteria(LEVEL_CRITERIA) == LEVEL_VALUE_ANY || $commit->getLevel() == $this->getCriteria(LEVEL_CRITERIA)) {
            $accept = true;
        }
        return $accept;
    }
    
    /**
     * Return the HTML code for the filter form
     */
    function getHtmlForm() {
        $Language =& $GLOBALS['Language'];
        $output = '';
        
        $output .= '<form action="'.$_SERVER['PHP_SELF'].'" method="post" name="serverupdate_formfilter">';
        $output .= ' <fieldset name="filter">';
        $output .= ' <legend>'.$Language->getText('plugin_serverupdate_update', 'filter').'</legend>';
        $output .= ' <input type="hidden" name="action" value="browse" />';
        $output .= ' <input type="hidden" name="sort" value="yes" />';
        $output .= '  '.$Language->getText('plugin_serverupdate_update', 'level');
        $output .= '  <select name="'.LEVEL_CRITERIA.'">';
        $output .= '   <option value="'.LEVEL_VALUE_ANY.'">'.LEVEL_VALUE_ANY.'</option>';
        $output .= '   <option value="'.LEVEL_VALUE_MINOR.'"';
        if (LEVEL_VALUE_MINOR == $this->getCriteria(LEVEL_CRITERIA)) {
            $output .= ' selected="selected"';
        }
        $output .= '>'.LEVEL_VALUE_MINOR.'</option>';
        $output .= '   <option value="'.LEVEL_VALUE_NORMAL.'"';
        if (LEVEL_VALUE_NORMAL == $this->getCriteria(LEVEL_CRITERIA)) {
            $output .= ' selected="selected"';
        }
        $output .= '>'.LEVEL_VALUE_NORMAL.'</option>';
        $output .= '   <option value="'.LEVEL_VALUE_CRITICAL.'"';
        if (LEVEL_VALUE_CRITICAL == $this->getCriteria(LEVEL_CRITERIA)) {
            $output .= ' selected="selected"';
        }
        $output .= '>'.LEVEL_VALUE_CRITICAL.'</option>';
        $output .= '  </select>';
        $output .= '  <input type="submit" value="Go" />';
        $output .= '</fieldset>';
        $output .= '</form>';
        
        return $output;
        
    }
    
}

?>
