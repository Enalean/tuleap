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
<div class="hero-unit">
    <h1>Collaborative Software Development at <?= $GLOBALS['sys_org_name']?></h1>
    <p><a href="/docs/site/about_codendi.php"><?= $GLOBALS['sys_name']?></a> is a service to all 
    <?= $GLOBALS['sys_org_name']?> software development teams.i</p>

    <P><?= $GLOBALS['sys_name']?> offers an easy access to a full featured and totally web-based project management environment.
    Using <?= $GLOBALS['sys_name']?> project teams can better focus on software development while making their community of users
    and developers grow.</p>
    <p><a href="/plugins/docman/?group_id=1" class="btn btn-large">Learn more on <?= $GLOBALS['sys_name']?> Services »</a></p>
</div>
<div class="row-fluid">
    <div class="span4">
        <h2>Site Participation</h2>
        <p>In order to get the most out of <?= $GLOBALS['sys_name']?>, you should 
        <a href="/account/register.php">register as a site user</a>. It's easy and 
        fast and it allows you to participate fully in all we have to offer. Also 
        make sure you read the <b><A href="<?php echo $exchangePolicyUrl ?>">
        <?= $GLOBALS['sys_org_name']?> Code Exchange Policy</a></b> before using this site.</p>
        <p><a href="/account/login.php" class="btn btn-primary btn-large">Login to <?= $GLOBALS['sys_name']?> <i class="icon-chevron-right icon-white"></i></a><br>
        Or <a href="/account/register.php">register a new account</a>
        </p>
    </div>

    <div class="span4">
        <h2>Set Up Your Own Project</h2>
        <p>After you <A href="/account/register.php">register as a site user</A>, you can 
        <A HREF="/account/login.php">login</A> and <A HREF="/project/register.php">register your project</A>.
        It only takes a couple of minutes to get a fully working environment to share your code.</p>
        <p><a HREF="/project/register.php" class="btn">Register your project »</a></p>
   </div>

   <div class="span4">
        <h2>CLI</h2>
        <p>This site provides a Command Line Interface based on the <a href="/soap/index.php">SOAP API</a> 
        to access it through a command line client. You can download the 
        <a href="/downloads/Codendi_CLI.zip">CLI client</a> and its 
        <a href="documentation/cli/pdf/en_US/Codendi_CLI.pdf">documentation</a>.</p>
        <p><a HREF="/downloads/Codendi_CLI.zip" class="btn">Download the CLI client <i class="icon-download-alt"></i></a></p>
   </div>
</div>

<?php
// Because of to aggressive continuous integration check on closing tags...
?>
