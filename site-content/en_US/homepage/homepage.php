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
    echo stripcslashes($Language->getText('homepage', 'introduction', array($GLOBALS['sys_org_name'], $GLOBALS['sys_name'])));
    return;
}

$main_content_span = 'span12';
$login_input_span  = 'span3';
if ($display_homepage_news) {
    $main_content_span = 'span8';
    $login_input_span  = 'span2';
}

?>

<style>
#homepage .hero-unit {
    background:white;
}
.contenttable {
    width: auto;
}
.main_body_row {
    width: 1200px;
    margin: 0 auto;
}
.homepage_speech {
    width: auto;
}
.homepage-feature {
    margin-bottom: 6em;
}
</style>
<div class="hero-unit">
    <div class="row-fluid">
        <div class="span3"><?= $GLOBALS['HTML']->getImage('organization_logo.png', array('width' => 200)); ?></div>
        <div class="span9"><img src="/banner.png" width="700" /></div>
    </div>
    
    <p>Here users, developers and all contributors gather to create Tuleap, the full open source ALM</p>
    <ul>
        <li><a href="https://tuleap.net/plugins/tracker/?tracker=140">Report a bug </a></li>
        <li><a href="https://tuleap.net/plugins/tracker/?tracker=140">Suggest a new feature</a></li>
        <li><a href="https://tuleap.net/wiki/?group_id=101&pagename=DeveloperGuide">Participate to developments</a></li>
    </ul>

    <a class="btn btn-primary btn-large" href="https://tuleap.net/wiki/?group_id=101&pagename=Installation+%26+Administration%2FHow+to+install">Join now</a>
</div>

<div class="row-fluid">
    <div class="span3"><h3>What is Tuleap?</h3>
    <p>Tuleap is a <b>full free Open Source Suite for <a href="http://en.wikipedia.org/wiki/Application_lifecycle_management">Application Lifecycle Management</a></b>.</b><br>
          Traditional development, Requirement Management, Agile Development, IT Service management...Tuleap makes software projects more productive, collaborative and industrialized.
          </p>
          </div>
    
    <div class="span3" style="text-align:left">
			<h2><img src="images/play.png" alt="Getting started with Tuleap" width="48px"> 
			Get started</h2>
			<ul>
			<li>Feature Overview</li>
			<li><a href="https://tuleap.net/wiki/?group_id=101&pagename=Installation+%26+Administration%2FHow+to+install">
			Try it!</a></li>
			<li><a href="https://tuleap.net/documentation/user_guide/html/en_US/User_Guide.html">Documentation</a> & videos</li>
			<li><a href="https://tuleap.net/plugins/forumml/message.php?group_id=101&list=1">Ask questions</a></li>
			</ul>
			<p>
			<a class="btn btn-primary btn-large" href="https://tuleap.net/wiki/?group_id=101&pagename=Installation+%26+Administration%2FHow+to+install">
			<i class="icon-download-alt icon-white"></i> Get Tuleap now!</a>
			</p>				         
			</div>
    
    <div class="span3" style="text-align:left">
          <h2><img src="images/help.png" alt="Contribute to Tuleap" width="48px">Get Help</h2>
			<ul>
			<li><a href="https://tuleap.net/documentation/user_guide/html/en_US/User_Guide.html">Documentation</a> 
			<li><a href="http://tuleap.com/resources/videos">videos</a></li>
			<li><a href="https://tuleap.net/plugins/forumml/message.php?group_id=101&list=1">Ask questions</a></li>
			<li><a href="http://tuleap.com/?q=services/support">Professional Support</a></li>

			</div>
			
    <div class="span3">
        <h2><?= $Language->getText('homepage', 'news_title') ?></h2>
        <?= news_show_latest($GLOBALS['sys_news_group'], 1, true, false, true, 3) ?>
    </div>
</div>

<div class="row-fluid">
    <hr />
</div>

<div class="row">
    <div class="span4 offset1"><h3>Who is it for?</h3>
    <p>CEO, Quality managers, Project managers, Developers, Businesses, Agilers. All stackholers creating innovative applications.</p>
    <p>Large companies, SMEs, free projects, public organizations.</p>
    </div>
    
    <div class="span4 offset1"><h3>What you get downloading Tuleap?</h3>
    <img src="images/open-source-logo.png" alt="Tuleap Open Source" width="50px"><b>ALL Tuleap capabilities in Open Source</b> 
    <p>Unlimited users, unlimited projects, unlimited period.</p>
    </p><b> We don’t distinguish between a “free” and an “enterprise” version.</b>  </p>
    
     </div>
</div>

<div class="row-fluid">
    <hr />
</div>

<div class="row-fluid">
    <h1>Features overview</h1>
</div>
<div class="row-fluid homepage-feature">
    <div class="span6">
        <img src="http://tuleap.com/sites/default/files/Tuleap-personal-dashboard.png" class="img-polaroid" width="570px" />
    </div>
    <div class="span6">
        <h2>Plan and monitor project</h2>
        <ul><li><strong>Plan releases, sprints, tasks and assign</strong> them to project members</li>
        <li>Track <strong>progress</strong> and <strong>remaining work</strong> </li>
        <li>Map <strong>backlog, stories and tasks</strong> </li>
        <li>Get a handle on project status and <strong>real-time progress</strong> with dashboards </li>
        <li>Know exactly what you have to do on your personal <strong>dashboard</strong></li>
        <li>Run <strong>personalized searches</strong> and generate <strong>adhoc graphs</strong></li>
        <li>Create <strong>public and private reports</strong></li>
        </ul>
    </div>
</div>

<div class="row-fluid homepage-feature">
    <div class="span6">
        <h2>Track, trace, link everything</h2>
        <p>A powerful tracking system with extensive configuration capabilities for all your project items</p>
        <ul><li>Track <strong> any type of project artifacts</strong>: risks, requirements, tasks, bugs,… </li>
        <li>Trace and link artifacts back to code, build, document, discussion, release &amp; more</li>
        <li>Easily <strong> customize trackers</strong>  to match your activity</li>
        <li>Configure<strong> workflow</strong>  to set up automatic actions</li>
        <li>Normalize process with your own trackers <strong>templates</strong></li>
        <li>Set <strong>fine-grained permissions</strong>, on each tracker, on each field</li>
        <li>Generate reports and add them to your <strong> dashboards</strong> </li>
        <li><strong>Import-export</strong> data from or to third tool</li>
        <li>Keep up-to-date with <strong>email notifications</strong></li>
        </ul>
    </div>
    <div class="span6">
        <img src="http://tuleap.com/sites/default/files/Tuleap-bug-tracker-search%20area.png" class="img-polaroid" width="570px" height="356px"/>
    </div>
</div>
<div class="row-fluid homepage-feature">
    <div class="span6">
        <img src="http://tuleap.com/sites/default/files/Tuleap-svn-version-differences_0.png" class="img-polaroid" width="570px" />
    </div>
    <div class="span6">
        <h2>Code &amp; build with famous tools</h2>
        <ul><li><strong>Browse repositories</strong> and <strong>view differences</strong> between versions</li>
        <li><strong>Link commits back to artifacts</strong>, documents,  files…</li>
        <li>Improve <strong>traceability</strong> forcing references in commit messages</li>
        <li>Follow latest commits with <strong>version control widgets on dashboards</strong></li>
        <li>Assign <strong>granular, path</strong> based access rights, on trunck and branches</li>
        <li>Receive <strong>email notifications</strong> when changes occur</li>
        <li>Search in commits</li>
        </ul>
        git, svn, cvs, jenkins logos
    </div>
</div>

<div class="row-fluid homepage-feature">
    <div class="span6">
        <h2>Create, store, version documents</h2>
        <ul><li> Centralize project documentation in a <strong>single documentation space</strong></li>
        <li>Create new <strong>versions</strong> and <strong>compare</strong> them with previous ones</li>
        <li>Organize  <strong>documents reviews </strong> with approval workflow</li>
        <li>Keep your content  <strong>safe and secure </strong> and decide exactly who can modify what</li>
        <li>Run personalized <b>searches in documents</b> and save your queries</li>
        <li>Archive all actions made on documents in an <strong>auditable history</strong></li>
        <li>Stay up-to-date about updates with <strong>notifications</strong></li>
        </ul>
    </div>
    <div class="span6">
        <img src="http://tuleap.com/sites/default/files/Tuleap-document-manager_0.png" class="img-polaroid" width="570px"/>
    </div>
</div>
<div class="row-fluid homepage-feature">
    <div class="span6">
        <img src="http://tuleap.com/sites/default/files/Tuleap-forum-discussion.png" class="img-polaroid" width="570px" />
    </div>
    <div class="span6">
        <h2>Stay tuned, Collaborate &amp; Exchange</h2>
        <ul><li>For each project, choose between integrated collaboration tools: forums, instant messaging, mailing-lists, news, RSS feeds</li>
        <li><strong>Discuss in real-time</strong> with  team members and partners with instant messaging</li>
        <li>Receive <strong>notifications</strong> on your dashboard or by email on document changes, commits or artifacts modifications</li>
        <li><strong>Discuss</strong> ideas in forums with flexible subscription and management</li>
        <li>With the wiki, <strong>share</strong> mockups, <strong>write</strong> documentation together, view <strong>modifications</strong> between versions</li>
        <li><strong>Create links back to artifacts,document, file, etc.from discussions</strong></li>
        </ul>
    </div>
</div>

<?php

//tell the upper script that it should'nt display boxes
$display_homepage_boxes = false;
$display_homepage_news  = false;

?>