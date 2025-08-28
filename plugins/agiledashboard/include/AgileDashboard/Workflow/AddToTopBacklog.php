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

use Feedback;
use Override;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Transition;
use Transition_PostAction;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactAlreadyPlannedException;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\Tracker\FormElement\Field\TrackerField;
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

    #[Override]
    public function getShortName()
    {
        return self::SHORT_NAME;
    }

    #[Override]
    public static function getLabel()
    {
        // Not implemented. We do not support the legacy UI for this new post action
        return '';
    }

    #[Override]
    public function isDefined()
    {
        // Since we do not support the legacy UI, it is always well defined
        return true;
    }

    #[Override]
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        $root->addChild(self::XML_TAG_NAME);
    }

    #[Override]
    public function bypassPermissions(TrackerField $field)
    {
        return false;
    }

    #[Override]
    public function accept(Visitor $visitor)
    {
        $visitor->visitExternalActions($this);
    }

    /**
     * Execute actions after transition happens
     *
     * @return void
     */
    #[Override]
    public function after(Tracker_Artifact_Changeset $changeset)
    {
        try {
            $this->unplanned_artifacts_adder->addArtifactToTopBacklog($changeset->getArtifact());

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext(
                    'tuleap-agiledashboard',
                    'This artifact has been successfully added to the backlog of the project.',
                )
            );
        } catch (ArtifactAlreadyPlannedException $exception) {
            //Do nothing
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                dgettext(
                    'tuleap-agiledashboard',
                    "This artifact has not been added to the backlog of the project because it's already planned in sub milestone of the project."
                )
            );
        }
    }
}
