<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\MyTuleapContactSupport\Presenter;

class ConfirmationEmailToUserPresenter
{
    /** @var string */
    public $mytuleap_name;
    /** @var string */
    public $current_user_real_name;
    /** @var string */
    public $message_title;
    /** @var string */
    public $message_content;
    /** @var string */
    public $email_title;
    /** @var string */
    public $hello;
    /** @var string */
    public $well_received;
    /** @var string */
    public $subject_field;
    /** @var string */
    public $message_field;
    /** @var string */
    public $thank_you;
    /** @var string */
    public $team;
    /** @var string */
    public $more_info;

    public function __construct($mytuleap_name, $current_user_real_name, $message_title, $message_content)
    {
        $this->mytuleap_name          = $mytuleap_name;
        $this->current_user_real_name = $current_user_real_name;
        $this->message_title          = $message_title;
        $this->message_content        = $message_content;
        $this->email_title            = dgettext('tuleap-mytuleap_contact_support', 'We have well received your message');
        $this->hello                  = sprintf(dgettext('tuleap-mytuleap_contact_support', 'Hello %s,'), $current_user_real_name);
        $this->well_received          = dgettext('tuleap-mytuleap_contact_support', 'We have well received your message. We will do our best to get back to you as soon as possible. You can find below a copy of your message:');
        $this->subject_field          = dgettext('tuleap-mytuleap_contact_support', 'Subject:');
        $this->message_field          = dgettext('tuleap-mytuleap_contact_support', 'Message:');
        $this->thank_you              = dgettext('tuleap-mytuleap_contact_support', 'Thank you for your confidence,');
        $this->team                   = dgettext('tuleap-mytuleap_contact_support', 'myTuleap Team');
        $this->more_info              = dgettext('tuleap-mytuleap_contact_support', 'More information about Tuleap');
    }
}
