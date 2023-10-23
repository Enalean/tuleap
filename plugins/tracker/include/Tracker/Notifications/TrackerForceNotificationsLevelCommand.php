<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use InvalidArgumentException;
use ProjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tracker;
use TrackerFactory;

class TrackerForceNotificationsLevelCommand extends Command
{
    public const NAME = 'tracker:force-notifications-level';

    /**
     * @var NotificationsForceUsageUpdater
     */
    private $force_usage_updater;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    private const AUTHORIZED_CONFIGURATION_LEVEL
        = [
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT_LABEL,
            Tracker::NOTIFICATIONS_LEVEL_DISABLED_LABEL,
            Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE_LABEL,
        ];
    /**
     * @var NotificationLevelExtractor
     */
    private $notification_level_extractor;
    /**
     * @var \TrackerDao
     */
    private $tracker_dao;

    public function __construct(
        NotificationsForceUsageUpdater $force_usage_updater,
        ProjectManager $project_manager,
        NotificationLevelExtractor $notification_level_extractor,
        TrackerFactory $tracker_factory,
        \TrackerDao $tracker_dao,
    ) {
        parent::__construct(self::NAME);
        $this->force_usage_updater          = $force_usage_updater;
        $this->project_manager              = $project_manager;
        $this->notification_level_extractor = $notification_level_extractor;
        $this->tracker_factory              = $tracker_factory;
        $this->tracker_dao                  = $tracker_dao;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $notification_level = $this->checkNotificationLevelParameter($input);
        $notification_level = $this->notification_level_extractor->extractNotificationLevelFromString(
            $notification_level
        );

        $project_list = $this->checkProjectsIdsParameter($input);
        foreach ($project_list as $project) {
            foreach ($this->tracker_factory->getTrackersByGroupId($project->getID()) as $tracker) {
                $tracker->setNotificationsLevel($notification_level);
                $this->tracker_dao->save($tracker);

                $this->force_usage_updater->forceUserPreferences($tracker, $notification_level);
            }
        }

        return 0;
    }

    protected function configure()
    {
        $this->setDescription('Force tracker notification level to all trackers of projects')
            ->addArgument(
                'notification_level',
                InputArgument::REQUIRED,
                "Notification level, authorized values: " .
                 implode(", ", self::AUTHORIZED_CONFIGURATION_LEVEL)
            )
            ->addArgument(
                'project_id',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'List of project ids (separate ids by space)'
            );
    }

    /**
     *
     * @return mixed
     */
    private function checkNotificationLevelParameter(InputInterface $input)
    {
        $notification_level = $input->getArgument('notification_level');
        if (! in_array($notification_level, self::AUTHORIZED_CONFIGURATION_LEVEL, true)) {
            throw new InvalidArgumentException(
                "Notification level, only following values are authorized: " .
                implode(", ", self::AUTHORIZED_CONFIGURATION_LEVEL)
            );
        }

        return $notification_level;
    }

    /**
     *
     * @return array
     */
    private function checkProjectsIdsParameter(InputInterface $input)
    {
        $project_list = [];
        $project_ids  = $input->getArgument('project_id');
        assert(is_array($project_ids));
        foreach ($project_ids as $project_id) {
            $project = $this->project_manager->getProject($project_id);
            if (! $project->getGroupId()) {
                throw new InvalidArgumentException("Project not found for id " . $project_id);
            }

            $project_list[] = $project;
        }

        return $project_list;
    }
}
