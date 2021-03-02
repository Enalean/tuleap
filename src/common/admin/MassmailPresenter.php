<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Admin;

use CSRFSynchronizerToken;

class MassmailPresenter
{
    public $recipients;
    public $title;
    public $subtitle;
    public $send_email_label;
    public $subject_label;
    public $destination_label;
    public $subject_placeholder;
    public $content_label;
    public $content_placeholder;
    public $preview_label;
    public $send_preview_label;
    public $csrf;
    public $confirmation_title;
    public $confirmation_body;
    public $cancel;

    public function __construct($title, array $recipients, CSRFSynchronizerToken $csrf)
    {
        $this->title      = $title;
        $this->recipients = $recipients;
        $this->csrf       = $csrf;

        $this->subtitle            = _('Email');
        $this->send_email_label    = _('Send email');
        $this->destination_label   = _('Send to');
        $this->subject_label       = _('Subject');
        $this->subject_placeholder = _('Catchy email subject line');
        $this->content_label       = _('Content');
        $this->content_placeholder = _('Message...');
        $this->preview_label       = _('Preview');
        $this->send_preview_label  = _('Send preview');
        $this->confirmation_title  = _('Massmail sending');
        $this->confirmation_body   = _('Please confirm your action.');
        $this->cancel              = $GLOBALS['Language']->getText('global', 'btn_cancel');
    }
}
