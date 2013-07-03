<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
//	Originally written by Laurent Julliard 2001- 2003 Codendi Team, Xerox
//

require_once('common/include/URL.class.php');
require_once('common/event/EventManager.class.php');


$vGroupId = new Valid_UInt('group_id');
$vGroupId->required();

if (!$request->valid($vGroupId)) {
    exit_no_group(); // need a group_id !!!
} else {
    $group_id = $request->get('group_id');
}

$hp =& Codendi_HTMLPurifier::instance();

svn_header(array ('title'=>$Language->getText('svn_intro','info')));

// Table for summary info
print '<TABLE width="100%"><TR valign="top"><TD width="65%">'."\n";

// Get group properties
$res_grp = db_query("SELECT * FROM groups WHERE group_id=".db_ei($group_id));
$row_grp = db_fetch_array($res_grp);
$pm = ProjectManager::instance();
$p = $pm->getProject($group_id);

// Show CVS access information
if ($row_grp['svn_preamble'] != '') {
    echo $hp->purify(util_unconvert_htmlspecialchars($row_grp['svn_preamble']), CODENDI_PURIFIER_FULL);
} else {
    $host = $GLOBALS['sys_default_domain'];
    if ($GLOBALS['sys_force_ssl']) {
       $svn_url = 'https://'. $host;
    } else if (isset($GLOBALS['sys_disable_subdomains']) && $GLOBALS['sys_disable_subdomains']) {
      $svn_url = 'http://'.$host;
    } else {
       $svn_url = 'http://svn.'. $row_grp['unix_group_name'] .'.'. $host;
    }
    // Domain name must be lowercase (issue with some SVN clients)
    $svn_url = strtolower($svn_url);
    $svn_url .= '/svnroot/'. $row_grp['unix_group_name'];

    // Hook to replace the default information about subversion
    // If no plugin set '$svn_intro_in_plugin' to true, the default message is
    // displayed.
    $em =& EventManager::instance();
    $svn_intro_in_plugin = false;
    $svnParams = array('svn_intro_in_plugin' => &$svn_intro_in_plugin,
                       'group_id'            => $group_id,
                       'svn_url'             => $svn_url,
                       'user_id'             => user_getid());
    $em->processEvent('svn_intro', $svnParams);
    if(!$svn_intro_in_plugin) {
        include($Language->getContent('svn/intro'));
    }
}

// Summary info
print '</TD><TD width="25%">';
print $HTML->box1_top($Language->getText('svn_intro','history'));

echo svn_utils_format_svn_history($group_id);

// SVN Browsing Box
print '<HR><B>'.$Language->getText('svn_intro','browse_tree').'</B>
<P>'.$Language->getText('svn_intro','browse_comment').'
<UL>
<LI><A HREF="/svn/viewvc.php/?roottype=svn&root='.$row_grp['unix_group_name'].'"><B>'.$Language->getText('svn_intro','browse_tree').'</B></A></LI>';

print $HTML->box1_bottom();

print '</TD></TR></TABLE>';

svn_footer(array());
?>
