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

class ModalPresenter
{
    /** @var string */
    public $modal_title;
    /** @var string */
    public $form_title;
    /** @var string */
    public $subject_label;
    /** @var string */
    public $subject_placeholder;
    /** @var string */
    public $your_message_label;
    /** @var string */
    public $your_message_placeholder;
    /** @var string */
    public $submit_label;
    /** @var string */
    public $sending_submit_label;
    /** @var string */
    public $thank_you_submit_label;
    /** @var string */
    public $form_submit_success;
    /** @var string */
    public $form_submit_error;
    /** @var string */
    public $help_page_content;

    public function __construct($administrator_email, $help_page_content)
    {
        $this->modal_title              = dgettext('tuleap-mytuleap_contact_support', 'Help');
        $this->form_title               = dgettext('tuleap-mytuleap_contact_support', 'Send a message to the myTuleap team');
        $this->subject_label            = dgettext('tuleap-mytuleap_contact_support', 'Subject');
        $this->subject_placeholder      = dgettext('tuleap-mytuleap_contact_support', 'I have a question about the tracker reports');
        $this->your_message_label       = dgettext('tuleap-mytuleap_contact_support', 'Message');
        $this->your_message_placeholder = dgettext('tuleap-mytuleap_contact_support', 'I need help to configure my own report in order to...');
        $this->submit_label             = dgettext('tuleap-mytuleap_contact_support', 'Send my message');
        $this->sending_submit_label     = dgettext('tuleap-mytuleap_contact_support', 'Sending...');
        $this->thank_you_submit_label   = dgettext('tuleap-mytuleap_contact_support', 'Thank you');
        $this->form_submit_success      = dgettext('tuleap-mytuleap_contact_support', 'Your message has been sent to the myTuleap team. You will receive an answer as soon as possible.');
        $this->form_submit_error        = sprintf(dgettext('tuleap-mytuleap_contact_support', 'Ooops, an error has occured. Please contact your administrator %s.'), $administrator_email);

        $this->help_page_content        = $help_page_content;
    }
}
