<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Documentation - Administering Developers"));
?>

<P><B>Site Documentation - Administering Developers for Your Project</B>

<P>One of the most important and powerful features of the SourceForge
Team Development Environment is web-based user administration. As a project
admin, you have complete control over developer permissions in
the bug tracker, message forums, and task manager. 

<P>At this time, all
developers have write access to the CVS Repository and to your
group directory (web site). There are plans to make these permissions
optional as well.

<P><B>Adding developers to your project</B>

<P>As a project administrator, you will control the developer list for your
project. If someone wants to join your project as a developer, they
must first register on the site as a user, then they must contact you
requesting to join.

<P>You control this membership list through the "Group Members" box
on the Project Admin page (available to you in the menu bar when you
are logged in and at your project page).

<P>Simply click "Add Group Member" to add a member. This will require that
you know either their SourceForge UID or their login name.

<P>Click "Remove from Group" to remove a group member. Project administrators
cannot be removed in this manner. To remove or add an administrator,
email admin@sourceforge.net with the request.

<P>IMPORTANT: The cvs and shell account permissions will not be updated
to reflect your changes immediately, but will take effect at the next
sitewide cron update (once per 6 hours).

<P><I>Group Members Box</I><BR>
<?php html_image("docs/groupmembers.png",array()); ?>

<P><B>Editing developer permissions</B>

<P>To control developer permissions within your project, click
"Edit Member Permissions" in the Group Members box, resulting in the
page shown below, where each member's permissions may be set. Don't
forget to click "Update Developer Permissions" after making any changes.

<P><I>Developer Permissions Page</I><BR>
<?php html_image("docs/developerperms.png",array()); ?>



<?php
$HTML->footer(array());

?>
