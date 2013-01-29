<?php
//
// Copyright (c) Enalean, 2013. All rights reserved
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// 
//

$summary_page_link = get_server_url().'/projects/'.$project->getUnixName();

// Message preamble with Web pointers and server names
$message = 
'<p align="center"><strong>Your project '.$project->getPublicName().' has been approved.</strong></p>

<p>Project main informations:
<ul>
  <li><strong>Full name</strong>:    '.$project->getPublicName().'</li>
  <li><strong>Short name</strong>:    '.$project->getUnixName().'</li>
  <li><strong>Summary page</strong>: <a href="'.$summary_page_link.'">'.$summary_page_link.'</a></li>
</ul>
</p>

<p>Please take some time to read the <a href="'.get_server_url().'/site">site documentation</a> about the tools
and services offered by '.$GLOBALS['sys_name'].' to project administrators (including a detailed User Guide).</p>

<p>We now invite you to visit the <a href="'.$summary_page_link.'">Summary page</a> of your project,
create a short public description for your project and categorize it in the Software Map if it hasn\'t been done.</p>

<p>As project administrator, you can <a href="'.get_server_url().'/project/admin/?group_id='.$project->getID().'">fully administrate your project</a>
environment. You can create task or defect trackers, mailing lists,
forums, git repositories, etc.</p>

<p><em>Remark</em>: if you already have a CVS or Subversion repository of your
own and want to transfer it as is on '.$GLOBALS['sys_name'].' then
contact the '.$GLOBALS['sys_name'].' team.</li>
</p>

<p>Let us know if there is anything we can do to help you.</p>

<p>-- The '.$GLOBALS['sys_name'].' Team</p>';

?>
