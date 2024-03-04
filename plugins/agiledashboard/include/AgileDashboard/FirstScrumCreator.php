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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\XML\Importer\TrackerExtraConfiguration;

class AgileDashboard_FirstScrumCreator
{
    /** @var PlanningFactory */
    private $planning_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var ProjectXMLImporter */
    private $xml_importer;

    /** @var string */
    private $template_path;

    /** @var string[] */
    private $reserved_names = [
        'epic', 'rel', 'sprint', 'task', 'story',
    ];

    public function __construct(
        PlanningFactory $planning_factory,
        TrackerFactory $tracker_factory,
        ProjectXMLImporter $xml_importer,
    ) {
        $this->xml_importer     = $xml_importer;
        $this->planning_factory = $planning_factory;
        $this->tracker_factory  = $tracker_factory;
        $this->template_path    = __DIR__ . '/../../resources/templates/scrum_dashboard_template.xml';
    }

    public function createFirstScrum(Project $project): NewFeedback
    {
        if ($this->areThereConfiguredPlannings($project)) {
            return NewFeedback::info(dgettext('tuleap-agiledashboard', 'Backlog is already setup'));
        }

        $already_existing_tracker = $this->getAlreadyExistingTracker($project);
        if ($already_existing_tracker) {
            return NewFeedback::warn(
                sprintf(
                    dgettext(
                        'tuleap-agiledashboard',
                        'We tried to create an initial backlog configuration for you but an existing tracker (%1$s) prevented it.',
                    ),
                    $already_existing_tracker,
                )
            );
        }

        $config              = new ImportConfig();
        $extra_configuration = new TrackerExtraConfiguration(['bug']);
        $config->addExtraConfiguration($extra_configuration);

        try {
            return $this->xml_importer->import($config, $project->getId(), $this->template_path)
                ->match(
                    function (): NewFeedback {
                        return NewFeedback::success(
                            dgettext(
                                'tuleap-agiledashboard',
                                'We created an initial backlog configuration based on the scrum methodology for you. Enjoy!',
                            ),
                        );
                    },
                    function (\Tuleap\NeverThrow\Fault $fault): NewFeedback {
                        return NewFeedback::error((string) $fault);
                    }
                );
        } catch (Exception) {
            return NewFeedback::warn(
                dgettext(
                    'tuleap-agiledashboard',
                    'We tried to create an initial backlog configuration for you but an internal error prevented it.',
                )
            );
        }
    }

    private function areThereConfiguredPlannings(Project $project): bool
    {
        return count($this->planning_factory->getPlanningTrackerIdsByGroupId((int) $project->getId())) > 0;
    }

    private function getAlreadyExistingTracker(Project $project): ?string
    {
        foreach ($this->reserved_names as $itemname) {
            if ($this->tracker_factory->isShortNameExists($itemname, (int) $project->getId())) {
                return $itemname;
            }
        }

        return null;
    }
}
