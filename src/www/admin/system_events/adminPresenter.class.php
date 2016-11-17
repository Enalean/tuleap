<?php
/**
 * Copyright (c) Enalean, 2014 — 2016. All Rights Reserved.
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

class SystemEvents_adminPresenter
{

    /**
     * @var Tuleap\Layout\PaginationPresenter
     */
    public $pagination;

    /**
     * @var Tuleap\SystemEvent\SystemEventSearchPresenter
     */
    public $search;
    public $status_label;
    public $parameters_label;
    public $time_taken_label;
    public $details_label;
    public $replay_label;

    /** @var Codendi_HTMLPurifier */
    private $purifier;

    /** @var array */
    public $queue_links;

    /** @var CSRFSynchronizerToken */
    public $csrf;

    /** @var array */
    public $status;

    public $types;

    /** @var string */
    public $events;

    /** @var array */
    public $system_event_followers;

    /** @var boolean */
    public $request_is_edit;

    /** @var array */
    public $status_new_followers;

    /** @var string */
    public $queue;
    public $title;
    public $has_events;
    public $empty_state;

    public function __construct(
        $title,
        Codendi_HTMLPurifier $purifier,
        array $queue_links,
        CSRFSynchronizerToken $csrf,
        array $events,
        array $system_event_followers,
        $request_is_edit,
        array $status_new_followers,
        $queue,
        Tuleap\SystemEvent\SystemEventSearchPresenter $search,
        Tuleap\Layout\PaginationPresenter $pagination
    ) {
        $this->purifier               = $purifier;
        $this->queue_links            = $queue_links;
        $this->csrf                   = $csrf;
        $this->events                 = $events;
        $this->has_events             = count($events) > 0;
        $this->system_event_followers = $system_event_followers;
        $this->request_is_edit        = $request_is_edit;
        $this->status_new_followers   = $status_new_followers;
        $this->queue                  = $queue;
        $this->title                  = $title;
        $this->pagination             = $pagination;
        $this->search                 = $search;

        $this->empty_state      = $GLOBALS['Language']->getText('admin_system_events', 'empty_state');
        $this->status_label     = $GLOBALS['Language']->getText('admin_system_events', 'status_label');
        $this->parameters_label = $GLOBALS['Language']->getText('admin_system_events', 'parameters_label');
        $this->time_taken_label = $GLOBALS['Language']->getText('admin_system_events', 'time_taken_label');
        $this->details_label    = $GLOBALS['Language']->getText('admin_system_events', 'details_label');
        $this->replay_label     = $GLOBALS['Language']->getText('admin_system_events', 'replay_label');
        $this->type_label       = $GLOBALS['Language']->getText('admin_system_events', 'type_label');
        $this->id_label         = $GLOBALS['Language']->getText('admin_system_events', 'id_label');
        $this->created_label    = $GLOBALS['Language']->getText('admin_system_events', 'created_label');
        $this->owner_label      = $GLOBALS['Language']->getText('admin_system_events', 'owner_label');
        $this->priority_label   = $GLOBALS['Language']->getText('admin_system_events', 'priority_label');
        $this->start_label      = $GLOBALS['Language']->getText('admin_system_events', 'start_label');
        $this->end_label        = $GLOBALS['Language']->getText('admin_system_events', 'end_label');
        $this->log_label        = $GLOBALS['Language']->getText('admin_system_events', 'log_label');
        $this->replayed_label   = $GLOBALS['Language']->getText('admin_system_events', 'replayed_label');
        $this->details_title    = $GLOBALS['Language']->getText('admin_system_events', 'details_title');
        $this->close_label      = $GLOBALS['Language']->getText('global', 'btn_close');
    }

    public function btn_submit_label() {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }

    public function btn_cancel_label() {
        return $GLOBALS['Language']->getText('global', 'btn_cancel');
    }

    public function notification_title() {
        return $GLOBALS['Language']->getText('admin_system_events', 'notifications');
    }

    public function send_mail_section_title() {
        return $GLOBALS['Language']->getText('admin_system_events', 'send_email');
    }

    public function has_follower() {
        return count($this->system_event_followers) > 0;
    }

    public function nobody_label() {
        return $GLOBALS['Language']->getText('admin_system_events', 'nobody');
    }

    public function edit_icon() {
        return $GLOBALS['HTML']->getImage('ic/edit.png');
    }

    public function cross_icon() {
        return $GLOBALS['HTML']->getImage('ic/cross.png');
    }

    public function js_content() {
        return $this->purifier->purify('Are you sure that you want to delete?', CODENDI_PURIFIER_JS_QUOTE);
    }

    public function default_new_followers_email() {
        return $this->purifier->purify(
            'Type logins, emails or mailing lists. Multiple values separated by coma.',
            CODENDI_PURIFIER_CONVERT_HTML
        );
    }
}