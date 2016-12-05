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

        $this->subtitle            = $GLOBALS['Language']->getText('admin_massmail', 'header');
        $this->send_email_label    = $GLOBALS['Language']->getText('admin_massmail', 'send_email_label');
        $this->destination_label   = $GLOBALS['Language']->getText('admin_massmail', 'destination_label');
        $this->subject_label       = $GLOBALS['Language']->getText('admin_massmail', 'subject_label');
        $this->subject_placeholder = $GLOBALS['Language']->getText('admin_massmail', 'subject_placeholder');
        $this->content_label       = $GLOBALS['Language']->getText('admin_massmail', 'content_label');
        $this->content_placeholder = $GLOBALS['Language']->getText('admin_massmail', 'content_placeholder');
        $this->preview_label       = $GLOBALS['Language']->getText('admin_massmail', 'preview_label');
        $this->send_preview_label  = $GLOBALS['Language']->getText('admin_massmail', 'send_preview_label');
        $this->confirmation_title  = $GLOBALS['Language']->getText('admin_massmail', 'confirmation_title');
        $this->confirmation_body   = $GLOBALS['Language']->getText('admin_massmail', 'confirmation_body');
        $this->cancel              = $GLOBALS['Language']->getText('global', 'btn_cancel');
    }
}
