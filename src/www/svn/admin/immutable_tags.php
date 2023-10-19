<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$immutable_tags_handler = new SVN_Immutable_Tags_Handler(new SVN_Immutable_Tags_DAO());

$request->valid(new Valid_String('post_changes'));
$request->valid(new Valid_String('SUBMIT'));
if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
    $vImmutableTagsWhitelist = new Valid_Text('immutable-tags-whitelist');
    $vImmutableTagsPath      = new Valid_Text('immutable-tags-path');

    if (
        $request->valid($vImmutableTagsWhitelist) &&
        $request->valid($vImmutableTagsPath)
    ) {
        $immutable_tags_whitelist = trim($request->get('immutable-tags-whitelist'));
        $immutable_tags_path      = trim($request->get('immutable-tags-path'));

        $immutable_tags_handler->saveImmutableTagsForProject($group_id, $immutable_tags_whitelist, $immutable_tags_path);
    } else {
        $GLOBALS['Response']->addFeedback('error', '');
    }
    $GLOBALS['Response']->redirect('/svn/admin/?func=immutable_tags&group_id=' . $group_id);
}

// Display the form
svn_header_admin($Language->getText('svn_admin_immutable_tags', 'title'));

$pm      = ProjectManager::instance();
$project = $pm->getProject($group_id);

$template_dir = ForgeConfig::get('codendi_dir') . '/src/templates/svn/';
$renderer     = TemplateRendererFactory::build()->getRenderer($template_dir);
$svnlook      = new SVN_Svnlook();
try {
    $existing_tree = $svnlook->getTree($project);
} catch (SVN_SvnlookException $exception) {
    $existing_tree = SVN_ImmutableTagsPresenter::$SO_MUCH_FOLDERS;
}

$presenter = new SVN_ImmutableTagsPresenter(
    $project,
    $immutable_tags_handler->getImmutableTagsWhitelistForProject($group_id),
    $immutable_tags_handler->getImmutableTagsPathForProject($group_id),
    $existing_tree
);

$renderer->renderToPage(
    'immutable-tags',
    $presenter
);

svn_footer([]);
