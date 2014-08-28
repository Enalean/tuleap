<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class SystemEvents_adminPresenter {

    /** @var Codendi_HTMLPurifier */
    private $purifier;

    /** @var array */
    public $queue_links;

    /** @var CSRFSynchronizerToken */
    private $token;

    /** @var array */
    public $status;

    /** @var string */
    public $selectbox;

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

    public function __construct(
        Codendi_HTMLPurifier $purifier,
        array $queue_links,
        CSRFSynchronizerToken $token,
        array $status,
        $selectbox,
        $events,
        array $system_event_followers,
        $request_is_edit,
        array $status_new_followers,
        $queue
    ) {
        $this->purifier               = $purifier;
        $this->queue_links            = $queue_links;
        $this->token                  = $token;
        $this->status                 = $status;
        $this->selectbox              = $selectbox;
        $this->events                 = $events;
        $this->events                 = $events;
        $this->system_event_followers = $system_event_followers;
        $this->request_is_edit        = $request_is_edit;
        $this->status_new_followers   = $status_new_followers;
        $this->queue                  = $queue;
    }

    public function page_title() {
        return $this->purifier->purify(
            $GLOBALS['Language']->getText('admin_system_events', 'title'),
            CODENDI_PURIFIER_CONVERT_HTML
        );
    }

    public function token_input() {
        return $this->token->fetchHTMLInput();
    }

    public function system_events_filter_classname() {
        return Toggler::getClassname('system_events_filter');
    }

    public function status_none_label() {
        return $this->purifier->purify(SystemEvent::STATUS_NONE, CODENDI_PURIFIER_CONVERT_HTML);
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