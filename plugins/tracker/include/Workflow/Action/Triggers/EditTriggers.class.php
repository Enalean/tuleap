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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Workflow_Action_Triggers_EditTriggers extends Tracker_Workflow_Action
{

    private $template_renderer;
    private $token;
    private $rule_manager;
    /**
     * @var string
     */
    private $url_query;

    public function __construct(
        Tracker $tracker,
        CSRFSynchronizerToken $token,
        TemplateRenderer $renderer,
        Tracker_Workflow_Trigger_RulesManager $rule_manager
    ) {
        parent::__construct($tracker);

        $this->url_query = TRACKER_BASE_URL . '/?' . http_build_query(
            array(
                'tracker' => (int) $this->tracker->id,
                'func'    => Workflow::FUNC_ADMIN_TRANSITIONS,
            )
        );
        $this->template_renderer = $renderer;
        $this->token             = $token;
        $this->rule_manager      = $rule_manager;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user)
    {
        $this->displayPane($layout);
    }

    private function displayPane(Tracker_IDisplayTrackerLayout $layout)
    {
        $this->displayHeader($layout, $GLOBALS['Language']->getText('workflow_admin', 'title_define_triggers'));

        $presenter = new Tracker_Workflow_Action_Triggers_TriggersPresenter(
            $this->tracker->getId(),
            $this->url_query,
            $this->token
        );

        $this->template_renderer->renderToPage('trigger-pane', $presenter);

        $GLOBALS['HTML']->appendJsonEncodedVariable(
            'tuleap.trackers.trigger.existing',
            $this->rule_manager->getForTargetTracker($this->tracker)->fetchFormattedForJson()
        );

        $this->displayFooter($layout);
    }
}
