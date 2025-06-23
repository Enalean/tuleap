<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Project;
use TrackerFactory;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;
use Tuleap\TestManagement\Administration\TrackerNotInProjectException;

class FirstConfigCreator
{
    /** @var Config */
    private $config;

    /** @var TrackerFactory */
    private $tracker_factory;
    /**
     * @var TestmanagementTrackersConfigurator
     */
    private $trackers_configurator;
    /**
     * @var TestmanagementTrackersCreator
     */
    private $testmanagement_trackers_creator;
    /**
     * @var TrackerChecker
     */
    private $tracker_checker;

    public function __construct(
        Config $config,
        TrackerFactory $tracker_factory,
        TrackerChecker $tracker_checker,
        TestmanagementTrackersConfigurator $testmanagement_trackers_configurator,
        TestmanagementTrackersCreator $testmanagement_trackers_creator,
    ) {
        $this->config                          = $config;
        $this->tracker_factory                 = $tracker_factory;
        $this->trackers_configurator           = $testmanagement_trackers_configurator;
        $this->testmanagement_trackers_creator = $testmanagement_trackers_creator;
        $this->tracker_checker                 = $tracker_checker;
    }

    /**
     * @throws Administration\TrackerDoesntExistException
     * @throws Administration\TrackerHasAtLeastOneHiddenFieldsetsPostActionException
     * @throws Administration\TrackerIsDeletedException
     * @throws TrackerComesFromLegacyEngineException
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerNotCreatedException
     * @throws TrackerNotInProjectException
     */
    public function createConfigForProjectFromTemplate(
        Project $project,
        Project $template,
        array $tracker_mapping,
    ): void {
        if (! $this->config->isConfigNeeded($project)) {
            return;
        }

        $template_trackers = $this->config->getTrackersFromTemplate($template);

        if (empty($template_trackers)) {
            return;
        }

        foreach ($template_trackers as $template_tracker) {
            if (! isset($tracker_mapping[$template_tracker->getTrackerId()])) {
                $tracker = $this->getTracker($project, $template_tracker->getTrackerShortname());
                if ($tracker) {
                    $this->trackers_configurator->configureTestmanagementTracker(
                        $template_tracker->getTrackerShortname(),
                        $tracker->getId()
                    );
                }
            } elseif ($template_tracker->getTrackerId()) {
                $this->trackers_configurator->configureTestmanagementTracker(
                    $template_tracker->getTrackerShortname(),
                    $tracker_mapping[$template_tracker->getTrackerId()]
                );
            }
        }

        $this->saveConfiguration($project, $this->trackers_configurator->getTrackersConfiguration());
    }

    /**
     * @throws Administration\TrackerDoesntExistException
     * @throws Administration\TrackerHasAtLeastOneHiddenFieldsetsPostActionException
     * @throws Administration\TrackerIsDeletedException
     * @throws MissingArtifactLinkException
     * @throws TrackerComesFromLegacyEngineException
     * @throws TrackerDefinitionNotValidException
     * @throws TrackerExecutionNotValidException
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerNotCreatedException
     * @throws TrackerNotInProjectException
     */
    public function createConfigForProjectFromXML(Project $project): void
    {
        $tracker_itemnames = [
            CAMPAIGN_TRACKER_SHORTNAME,
            DEFINITION_TRACKER_SHORTNAME,
            EXECUTION_TRACKER_SHORTNAME,
            ISSUE_TRACKER_SHORTNAME,
        ];

        if (! $this->config->isConfigNeeded($project)) {
            return;
        }

        foreach ($tracker_itemnames as $tracker_itemname) {
            $tracker = $this->getTracker($project, $tracker_itemname);

            if (! $tracker) {
                continue;
            }

            $this->trackers_configurator->configureTestmanagementTracker(
                $tracker_itemname,
                $tracker->getId()
            );
        }

        $this->saveConfiguration($project, $this->trackers_configurator->getTrackersConfiguration());
    }

    /**
     * @throws Administration\TrackerDoesntExistException
     * @throws Administration\TrackerHasAtLeastOneHiddenFieldsetsPostActionException
     * @throws Administration\TrackerIsDeletedException
     * @throws MissingArtifactLinkException
     * @throws TrackerDefinitionNotValidException
     * @throws TrackerExecutionNotValidException
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerNotInProjectException
     */
    private function saveConfiguration(Project $project, TestmanagementTrackersConfiguration $config_trackers): void
    {
        $this->tracker_checker->checkTrackers($project, $config_trackers);
        $issue_tracker = $config_trackers->getIssue();

        $this->config->setProjectConfiguration(
            $project,
            $config_trackers->getCampaign()->getTrackerId(),
            $config_trackers->getTestDefinition()->getTrackerId(),
            $config_trackers->getTestExecution()->getTrackerId(),
            $issue_tracker ? $issue_tracker->getTrackerId() : null
        );
    }

    /**
     * @return \Tuleap\Tracker\Tracker|null
     *
     * @throws TrackerComesFromLegacyEngineException
     * @throws TrackerNotCreatedException
     */
    private function getTracker(Project $project, string $tracker_itemname)
    {
        $tracker = null;
        if ($this->isTrackerAlreadyCreated($project, $tracker_itemname)) {
            $tracker = $this->tracker_factory->getTrackerByShortnameAndProjectId(
                $tracker_itemname,
                $project->getId()
            );

            if (! $tracker) {
                // Tracker using this shortname is from TrackerEngine v3
                throw new TrackerComesFromLegacyEngineException($tracker_itemname);
            }
        } else {
            $tracker = $this->testmanagement_trackers_creator->createTrackerFromXML($project, $tracker_itemname);
        }

        return $tracker;
    }

    /**
     * @return bool
     */
    private function isTrackerAlreadyCreated(Project $project, string $tracker_itemname)
    {
        $is_tracker_already_created = $this->tracker_factory->isShortNameExists(
            $tracker_itemname,
            $project->getId()
        );

        return $is_tracker_already_created;
    }
}
