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

namespace Tuleap\Admin\SystemEvents;

use Event;
use EventManager;
use SystemEvent;
use SystemEventDao;
use SystemEventManager;
use SystemEventQueue;

class HomepagePanePresenterBuilder
{
    /**
     * @var SystemEventDao
     */
    private $dao;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var SystemEventManager
     */
    private $system_event_manager;

    public function __construct(
        SystemEventDao $dao,
        EventManager $event_manager,
        SystemEventManager $system_event_manager
    ) {
        $this->dao                  = $dao;
        $this->event_manager        = $event_manager;
        $this->system_event_manager = $system_event_manager;
    }

    public function build()
    {
        $sections = $this->getAllSectionPresenters();

        return new HomepagePanePresenter($sections);
    }

    private function getAllSectionPresenters()
    {
        $available_queues = [
            SystemEventQueue::NAME => new SystemEventQueue()
        ];
        $this->event_manager->processEvent(
            Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES,
            ['queues' => &$available_queues]
        );

        $section_presenters = [];
        foreach ($available_queues as $queue) {
            $this->addQueueSectionPresenter($section_presenters, $queue);
        }

        return $section_presenters;
    }

    private function addQueueSectionPresenter(array &$section_presenters, SystemEventQueue $queue)
    {
        $stats_by_status = [
            SystemEvent::STATUS_NEW     => 0,
            SystemEvent::STATUS_RUNNING => 0,
            SystemEvent::STATUS_DONE    => 0,
            SystemEvent::STATUS_WARNING => 0,
            SystemEvent::STATUS_ERROR   => 0,
        ];

        $types = $this->system_event_manager->getTypesForQueue($queue->getName());
        foreach ($this->dao->searchQueueStatsForLastDay($types) as $row) {
            $stats_by_status[$row['status']] = $row['nb'];
        }

        $section_presenters[] = new HomepagePaneSectionPresenter(
            $queue->getLabel(),
            $queue->getName(),
            $stats_by_status[SystemEvent::STATUS_NEW],
            $stats_by_status[SystemEvent::STATUS_RUNNING],
            $stats_by_status[SystemEvent::STATUS_DONE],
            $stats_by_status[SystemEvent::STATUS_WARNING],
            $stats_by_status[SystemEvent::STATUS_ERROR]
        );
    }
}
