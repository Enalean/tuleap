<?php
/**
 * Copyright (c) Enalean 2011. All rights reserved
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

// For backward compatibility: if the introduction speech was 
// customized in etc/site-content homepage.tab, we display him
// instead of following text.
if ($Language->hasText('homepage', 'introduction')) {
    echo stripcslashes($Language->getText('homepage', 'introduction',array($GLOBALS['sys_org_name'],$GLOBALS['sys_name'])));
    return;
}

if (isset($GLOBALS['sys_exchange_policy_url'])) {
    $exchangePolicyUrl = $GLOBALS['sys_exchange_policy_url'];
} else {
    $exchangePolicyUrl = "/plugins/docman/?group_id=1";
}
?>

<div class="slogan">Collaborative Software Development at <?= $GLOBALS['sys_org_name']?></div>

<br><?= $GLOBALS['sys_name']?> is a <B>service to all <?= $GLOBALS['sys_org_name']?> software development teams</B>. <A href="/docs/site/about_codendi.php">[&nbsp;More about <?= $GLOBALS['sys_name']?>&nbsp;]</A>

<P><?= $GLOBALS['sys_name']?> offers an easy access to a full featured and totally web-based project management environment.
Using <?= $GLOBALS['sys_name']?> project teams can better focus on software development while making their community of users
and developers grow. <A href="/plugins/docman/?group_id=1">[&nbsp;More on <?= $GLOBALS['sys_name']?> Services&nbsp;]</a><P>

<u><B>Site Participation</B></u>

<BR>In order to get the most out of <?= $GLOBALS['sys_name']?>, you should
<A href="/account/register.php">register as a site user</A>. It's easy and fast and it allows you to participate fully in all we have to offer.
Also make sure you read the <b><A href="<?php echo $exchangePolicyUrl ?>"><?= $GLOBALS['sys_org_name']?> Code Exchange Policy</a></b> before using this site.

<P><u><B>Set Up Your Own Project</B></u><BR>After you <A href="/account/register.php">register as a site user</A>, you can <A HREF="/account/login.php">login</A> and <A HREF="/project/register.php">register your project</A>.
It only takes a couple of minutes to get a fully working environment to share your code.

<P><B><U>CLI</U></B><BR />This site provides a Command Line Interface based on the <a href="/soap/index.php">SOAP API</a> to access it through a command line client.
You can download the <a href="/downloads/Codendi_CLI.zip">CLI client</a> and its <a href="documentation/cli/pdf/en_US/Codendi_CLI.pdf">documentation</a>.

<p>Thanks... and enjoy the site.</p>

<?php
// Because of to aggressive continuous integration check on closing tags...
?>