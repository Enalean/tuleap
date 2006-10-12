<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id: CodexDataAccess.class 1882 2005-08-16 10:48:39Z nterray $
//

require_once('pre.php');
$title = $Language->getText('include_layout','Help');
site_header(array('title' => $title));
echo '<h2>',$title,'</h2>';
echo '<ul>';
echo '<li><a href="/documentation/user_guide/html/en_US/"><b>'.$Language->getText('include_menu','help_index').'</b></a></li>';
echo '<li><a href="/plugins/docman/?group_id=1">'.$Language->getText('include_menu','site_doc').'</b></a></li>';
echo '<li><a href="/mail/?group_id=1">'.$Language->getText('include_menu','dev_channel').'</b></a></li>';
echo '<li><a href="/forum/?group_id=1">'.$Language->getText('include_menu','discussion_forum').'</b></a><br /></li>';
echo '</ul>';
echo '<ul>';
echo '<li><a href="/contact.php">'.$Language->getText('include_menu','contact_us').'</b></a></li>';
echo '</ul>';
site_footer(array());
?>