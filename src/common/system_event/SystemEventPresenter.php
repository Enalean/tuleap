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

namespace Tuleap\SystemEvent;

use SystemEvent;
use Codendi_HTMLPurifier;

class SystemEventPresenter
{
    private static $NULL_DATE = '0000-00-00 00:00:00';

    private static $BADGES_PER_STATUS = array(
        SystemEvent::STATUS_RUNNING => 'info',
        SystemEvent::STATUS_DONE    => 'success',
        SystemEvent::STATUS_WARNING => 'warning',
        SystemEvent::STATUS_ERROR   => 'danger',
        SystemEvent::STATUS_NONE    => 'secondary',
        SystemEvent::STATUS_NEW     => 'secondary'
    );

    public $id;
    public $full_type;
    public $namespace;
    public $type;
    public $badge;
    public $status;
    public $purified_parameters;
    public $time_taken;
    public $can_be_replayed;
    public $priority;
    public $owner;
    public $create_date;
    public $end_date;
    public $is_high;
    public $is_low;
    public $status_need_attention;
    public $is_started;
    public $is_ended;
    public $is_replayed;
    public $start_date;
    public $not_processed_yet;
    public $create_time;
    public $start_time;
    public $end_time;
    public $raw_create_date;

    public function __construct(SystemEvent $sysevent)
    {
        $this->id        = $sysevent->getId();
        $this->full_type = $sysevent->getType();
        $this->owner     = $sysevent->getOwner();
        $this->log       = $sysevent->getLog() ? $sysevent->getLog() : '';

        if ($sysevent->getPriority() === SystemEvent::PRIORITY_HIGH) {
            $this->priority = $GLOBALS['Language']->getText('admin_system_events', 'priority_1');
        } elseif ($sysevent->getPriority() === SystemEvent::PRIORITY_MEDIUM) {
            $this->priority = $GLOBALS['Language']->getText('admin_system_events', 'priority_2');
        } else {
            $this->priority = $GLOBALS['Language']->getText('admin_system_events', 'priority_3');
        }

        $this->is_high  = (int) $sysevent->getPriority() === SystemEvent::PRIORITY_HIGH;
        $this->is_low   = (int) $sysevent->getPriority() === SystemEvent::PRIORITY_LOW;

        $this->is_started = $sysevent->getProcessDate() !== self::$NULL_DATE;
        $this->is_ended   = $sysevent->getEndDate() !== self::$NULL_DATE;

        $this->raw_create_date = $sysevent->getCreateDate();

        $this->create_date = $this->getLocalizedDatetime($sysevent->getCreateDate());
        $this->start_date  = $this->getLocalizedDatetime($sysevent->getProcessDate());
        $this->end_date    = $this->getLocalizedDatetime($sysevent->getEndDate());
        $this->create_time = substr($sysevent->getCreateDate(), 11);
        $this->start_time  = substr($sysevent->getProcessDate(), 11);
        $this->end_time    = substr($sysevent->getEndDate(), 11);
        $this->time_taken  = $sysevent->getTimeTaken();

        $this->extractNamespaceFromType($sysevent->getType());

        $this->status                = ucfirst(strtolower($sysevent->getStatus()));
        $this->status_need_attention = $sysevent->getStatus() !== SystemEvent::STATUS_DONE;
        $this->badge                 = self::$BADGES_PER_STATUS[$sysevent->getStatus()];
        $this->is_replayed           = $this->is_started && $sysevent->getStatus() === SystemEvent::STATUS_NEW;

        $this->purified_parameters = Codendi_HTMLPurifier::instance()->purify(
            $sysevent->verbalizeParameters(true),
            CODENDI_PURIFIER_LIGHT
        );

        $this->can_be_replayed = $sysevent->getStatus() === SystemEvent::STATUS_ERROR;

        $this->not_ended_yet   = $GLOBALS['Language']->getText('admin_system_events', 'not_ended_yet');
        $this->not_started_yet = $GLOBALS['Language']->getText('admin_system_events', 'not_started_yet');
    }

    private function getLocalizedDatetime($datetime)
    {
        return date($GLOBALS['Language']->getText('system', 'datefmt_full'), strtotime($datetime));
    }

    private function extractNamespaceFromType($type)
    {
        $this->namespace = '';
        $namespaced_type = substr(strrchr($type, '\\'), 1);
        if ($namespaced_type !== false) {
            $this->namespace = substr($type, 0, strpos($type, $namespaced_type));
            $type            = $namespaced_type;
        }
        $this->type = str_replace('SystemEvent_', '', $type);
    }
}
