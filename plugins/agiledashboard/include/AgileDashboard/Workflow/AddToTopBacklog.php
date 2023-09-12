<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Workflow;

use EventManager;
use Feedback;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Transition;
use Transition_PostAction;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactAlreadyPlannedException;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\Kanban\CheckSplitKanbanConfiguration;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

class AddToTopBacklog extends Transition_PostAction
{
    public const SHORT_NAME   = 'add_to_top_backlog';
    public const XML_TAG_NAME = 'postaction_add_to_top_backlog';

    /**
     * @var UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    public function __construct(Transition $transition, $id, UnplannedArtifactsAdder $unplanned_artifacts_adder)
    {
        parent::__construct($transition, $id);

        $this->unplanned_artifacts_adder = $unplanned_artifacts_adder;
    }

    public function getShortName()
    {
        return self::SHORT_NAME;
    }

    public static function getLabel()
    {
        // Not implemented. We do not support the legacy UI for this new post action
        return '';
    }

    public function isDefined()
    {
        // Since we do not support the legacy UI, it is always well defined
        return true;
    }

    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        $root->addChild(self::XML_TAG_NAME);
    }

    public function bypassPermissions(Tracker_FormElement_Field $field)
    {
        return false;
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitExternalActions($this);
    }

    /**
     * Execute actions after transition happens
     *
     * @return void
     */
    public function after(Tracker_Artifact_Changeset $changeset)
    {
        $is_split_feature_flag_enabled = (new CheckSplitKanbanConfiguration(EventManager::instance()))->isProjectAllowedToUseSplitKanban($changeset->getTracker()->getProject());
        try {
            $this->unplanned_artifacts_adder->addArtifactToTopBacklog($changeset->getArtifact());

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $is_split_feature_flag_enabled ? dgettext(
                    'tuleap-agiledashboard',
                    'This artifact has been successfully added to the backlog of the project.',
                ) : dgettext(
                    'tuleap-agiledashboard',
                    'This artifact has been successfully added to the top backlog of the project.',
                )
            );
        } catch (ArtifactAlreadyPlannedException $exception) {
            //Do nothing
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $is_split_feature_flag_enabled ? dgettext(
                    'tuleap-agiledashboard',
                    "This artifact has not been added to the backlog of the project because it's already planned in sub milestone of the project."
                ) : dgettext(
                    'tuleap-agiledashboard',
                    "This artifact has not been added to the top backlog of the project because it's already planned in sub milestone of the project."
                )
            );
        }
    }
}
