<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use GitRepository;

class AdditionalHelpTextPresenter
{

    public function getTemplateName()
    {
        return 'additional-help-text';
    }

    public function git_clone_bar_help_text_title() // phpcs:ignore
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'git_clone_bar_help_text_title');
    }

    public function git_clone_bar_help_text_intro() // phpcs:ignore
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'git_clone_bar_help_text_intro');
    }

    public function git_clone_bar_help_text_create_pull_request() // phpcs:ignore
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'git_clone_bar_help_text_create_pull_request');
    }

    public function new_pull_request_button_title() // phpcs:ignore
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'new_pull_request_button_title');
    }

    public function git_clone_bar_help_text_view_pull_requests() // phpcs:ignore
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'git_clone_bar_help_text_view_pull_requests', array(
            $GLOBALS['Language']->getText('plugin_pullrequest', 'nb_pull_request_badge_plural', array('#'))
        ));
    }

    public function git_clone_bar_help_text_pull_request_actions() // phpcs:ignore
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'git_clone_bar_help_text_pull_request_actions');
    }
}
