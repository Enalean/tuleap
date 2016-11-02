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

namespace Tuleap\Git;

use Codendi_Request;
use CSRFSynchronizerToken;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Git\Permissions\RegexpFineGrainedDisabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;

class GeneralSettingsController
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    /**
     * @var RegexpFineGrainedEnabler
     */
    private $regexp_enabler;

    /**
     * @var AdminPageRenderer
     */
    private $renderer;

    /**
     * @var RegexpFineGrainedDisabler
     */
    private $regexp_disabler;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        RegexpFineGrainedRetriever $regexp_retriever,
        RegexpFineGrainedEnabler $regexp_enabler,
        AdminPageRenderer $renderer,
        RegexpFineGrainedDisabler $regexp_disabler
    ) {
        $this->csrf             = $csrf;
        $this->regexp_retriever = $regexp_retriever;
        $this->regexp_enabler   = $regexp_enabler;
        $this->renderer         = $renderer;
        $this->regexp_disabler  = $regexp_disabler;
    }

    public function process(Codendi_Request $request)
    {
        if ($request->get('action') === 'enable-regexp-usage') {
            if ($request->get('activate-regexp')) {
                $this->enableRegexp();
            } else {
                $this->disableRegexp();
            }
        }
    }

    public function display()
    {
        $title     = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        $presenter = new GeneralSettingsPresenter(
            $title,
            $this->csrf,
            $this->isRegexpAuthorizedForPlateform()
        );

        $this->renderer->renderANoFramedPresenter($title, GIT_TEMPLATE_DIR, 'admin-plugin', $presenter);
    }

    private function isRegexpAuthorizedForPlateform()
    {
        return $this->regexp_retriever->areRegexpActivatedAtSiteLevel();
    }

    private function enableRegexp()
    {
        if (! $this->regexp_enabler->enable()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git', 'update_error'));
        }
    }

    private function disableRegexp()
    {
        if (! $this->regexp_disabler->disableAtSiteLevel()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git', 'update_error'));
        }
    }
}
