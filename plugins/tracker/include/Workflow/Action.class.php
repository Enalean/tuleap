<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Webhook\Actions\AdminWebhooks;
use Tuleap\Tracker\Workflow\FeatureFlag;

/**
 * Base class to manage action that can be done on a workflow
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
abstract class Tracker_Workflow_Action
{
    public const PANE_RULES                  = 'rules';
    public const PANE_TRANSITIONS            = 'transitions';
    public const PANE_CROSS_TRACKER_TRIGGERS = 'triggers';
    public const PANE_WEBHOOKS               = 'webhooks';

    /** @var Tracker */
    protected $tracker;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    protected function displayHeader(Tracker_IDisplayTrackerLayout $engine)
    {
        $this->tracker->displayAdminItemHeader($engine, 'editworkflow');

        echo '<div class="tabbable">';
        echo '<div class="tab-content">';
    }

    protected function displayFooter(Tracker_IDisplayTrackerLayout $engine)
    {
        echo '</div>';
        echo '</div>';
        $this->tracker->displayFooter($engine);
    }

    /**
     * @return string eg: rules, transitions
     */
    abstract protected function getPaneIdentifier();

    /**
     * Process the request
     */
    abstract public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user);
}
