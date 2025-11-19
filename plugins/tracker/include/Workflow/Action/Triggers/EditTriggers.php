<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\Action\Triggers\TriggersPresenter;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class Tracker_Workflow_Action_Triggers_EditTriggers extends Tracker_Workflow_Action
{
    private $template_renderer;
    private $rule_manager;

    public function __construct(
        Tracker $tracker,
        private readonly \Tuleap\Request\CSRFSynchronizerTokenInterface $token,
        TemplateRenderer $renderer,
        Tracker_Workflow_Trigger_RulesManager $rule_manager,
    ) {
        parent::__construct($tracker);

        $this->template_renderer = $renderer;
        $this->rule_manager      = $rule_manager;
    }

    #[\Override]
    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user)
    {
        $this->displayPane($layout);
    }

    private function displayPane(Tracker_IDisplayTrackerLayout $layout): void
    {
        $GLOBALS['Response']->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../../scripts/tracker-admin-triggers/frontend-assets',
                    '/assets/trackers/tracker-admin-triggers',
                ),
                'src/tracker-admin-trigger.ts',
            ),
        );

        $this->displayHeaderBurningParrot($layout, dgettext('tuleap-tracker', 'Define cross-tracker triggers'));

        $presenter = new TriggersPresenter(
            $this->tracker->getId(),
            $this->token,
            \Psl\Json\encode($this->rule_manager->getForTargetTracker($this->tracker)->fetchFormattedForJson()),
        );

        $this->template_renderer->renderToPage('trigger-pane', $presenter);

        $this->displayFooterBurningParrot($layout);
    }
}
