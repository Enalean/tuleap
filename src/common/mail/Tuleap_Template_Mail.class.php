<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

/**
 * Template class to send beautiful html emails in tuleap
 *
 * Usage is transparent through Codendi_Mail
 * <pre>
 * $mail = new Codendi_Mail();
 * $mail->getHtmlTemplate()->set('breadcrumbs', $breadcrumbs);
 * $mail->setBodyHtml($html);
 * $mail->send();
 * </pre>
 */
class Tuleap_Template_Mail extends Tuleap_Template
{
    public function __construct()
    {
        parent::__construct($GLOBALS['Language']->getContent('mail/html_template', 'en_US', null, '.php'));
        $this->set('txt_display_not_correct', $GLOBALS['Language']->getText('mail_html_template', 'display_not_correct'));
        $this->set('txt_update_prefs', $GLOBALS['Language']->getText('mail_html_template', 'update_prefs'));
        $this->set('txt_can_update_prefs', $GLOBALS['Language']->getText('mail_html_template', 'can_update_prefs'));
        $this->set('http_url', 'http://' . $GLOBALS['sys_default_domain']);
        $this->set('img_path', 'http://' . $GLOBALS['sys_default_domain'] . '/images');
        $this->set('title', '');
    }
}
