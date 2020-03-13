<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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


// CAUTION!!
// Make the changes before calling svn_header_admin because
// svn_header_admin caches the project object in memory and
// the form values are therefore not updated.
$request->valid(new Valid_String('post_changes'));
$request->valid(new Valid_String('SUBMIT'));
if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
    $vTracked = new Valid_WhiteList('form_tracked', array('0', '1'));
    $vTracked->required();
    $vMandatoryRef = new Valid_WhiteList('form_mandatory_ref', array('0', '1'));
    $vMandatoryRef->required();
    $vPreamble = new Valid_Text('form_preamble');
    $vCanChangeSVNLog = new Valid_WhiteList('form_can_change_svn_log', array('0', '1'));
    $vCanChangeSVNLog->required();

    if ($request->valid($vTracked) &&
        $request->valid($vPreamble) &&
        $request->valid($vMandatoryRef) &&
        $request->valid($vCanChangeSVNLog)
    ) {
        // group_id was validated in index.
        $form_tracked = $request->get('form_tracked');
        $form_preamble = $request->get('form_preamble');
        $form_mandatory_ref = $request->get('form_mandatory_ref');
        $form_can_change_svn_log = $request->get('form_can_change_svn_log');

        $ret = svn_data_update_general_settings(
            $group_id,
            $form_tracked,
            $form_preamble,
            $form_mandatory_ref,
            $form_can_change_svn_log
        );

        if ($ret) {
            EventManager::instance()->processEvent(Event::SVN_UPDATE_HOOKS, array('group_id' => $group_id));
            $GLOBALS['Response']->addFeedback('info', $Language->getText('svn_admin_general_settings', 'upd_success'));
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_general_settings', 'upd_fail'));
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_admin_general_settings', 'upd_fail'));
    }
    $GLOBALS['Response']->redirect('/svn/admin/?func=general_settings&group_id=' . $group_id);
}

// Note: no need to purify the output since the svn preamble is stored
// htmlcharized and displayed with the entities.

// Display the form
svn_header_admin(array ('title' => $Language->getText('svn_admin_general_settings', 'gen_settings'),
                        'help' => 'svn.html#general-settings'));

$pm = ProjectManager::instance();
$project = $pm->getProject($group_id);

$template_dir = ForgeConfig::get('codendi_dir') . '/src/templates/svn/';
$renderer     = TemplateRendererFactory::build()->getRenderer($template_dir);

$presenter = new SVN_GeneralSettingsPresenter(
    $project
);

$renderer->renderToPage(
    'general-settings',
    $presenter
);

svn_footer(array());
