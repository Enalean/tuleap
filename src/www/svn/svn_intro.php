<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Laurent Julliard 2001- 2003 Codendi Team, Xerox
 *
 * This file is a part of Tuleap.
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

$vGroupId = new Valid_UInt('group_id');
$vGroupId->required();

if (! $request->valid($vGroupId)) {
    exit_no_group(); // need a group_id !!!
} else {
    $group_id = $request->get('group_id');
}

$hp = Codendi_HTMLPurifier::instance();

require_once __DIR__ . '/svn_utils.php';

$project = $request->getProject();
svn_header(
    $project,
    \Tuleap\Layout\HeaderConfigurationBuilder::get($Language->getText('svn_intro', 'info'))
        ->inProject($project, Service::SVN)
        ->build(),
    null,
);

// Table for summary info
print '<TABLE width="100%"><TR valign="top"><TD width="65%">' . "\n";

$svn_preamble = $project->getSVNpreamble();

// Show CVS access information
if ($svn_preamble != '') {
    echo $hp->purify(util_unconvert_htmlspecialchars($svn_preamble), CODENDI_PURIFIER_FULL);
} else {
    $svn_url = \Tuleap\ServerHostname::HTTPSUrl();
    // Domain name must be lowercase (issue with some SVN clients)
    $svn_url  = strtolower($svn_url);
    $svn_url .= '/svnroot/' . $project->getUnixNameMixedCase();

    $event_manager       = EventManager::instance();
    $svn_intro_in_plugin = false;
    $svn_intro_info      = null;
    $current_user        = UserManager::instance()->getCurrentUserWithLoggedInInformation();

    $svn_params = [
        'svn_intro_in_plugin' => &$svn_intro_in_plugin,
        'svn_intro_info'      => &$svn_intro_info,
        'group_id'            => $group_id,
        'user_id'             => $current_user->user->getId(),
    ];

    $event_manager->processEvent(Event::SVN_INTRO, $svn_params);

    $template_dir = ForgeConfig::get('codendi_dir') . '/src/templates/svn/';
    $renderer     = TemplateRendererFactory::build()->getRenderer($template_dir);

    $presenter = new SVN_IntroPresenter(
        $current_user,
        $svn_intro_in_plugin,
        $svn_intro_info,
        $svn_url
    );

    $renderer->renderToPage('intro', $presenter);
}

// Summary info
print '</TD><TD width="25%">';
print $HTML->box1_top($Language->getText('svn_intro', 'history'));

echo svn_utils_format_svn_history($group_id);

// SVN Browsing Box
print '<HR><B>' . $Language->getText('svn_intro', 'browse_tree') . '</B>
<P>' . $Language->getText('svn_intro', 'browse_comment') . '
<UL>
<LI><A HREF="/svn/viewvc.php/?roottype=svn&root=' . $hp->purify(urlencode($project->getUnixNameMixedCase())) . '"><B>' . $Language->getText('svn_intro', 'browse_tree') . '</B></A></LI>';

print $HTML->box1_bottom();

print '</TD></TR></TABLE>';

svn_footer([]);
