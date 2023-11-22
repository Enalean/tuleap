<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Notification;

class Notification
{
    /** @var array */
    private $emails;

    /** @var string */
    private $subject;

    /** @var string */
    private $body_html;

    /** @var string */
    private $body_text;

    /** @var string */
    private $goto_link;

    /** @var string */
    private $service_name;

    public function __construct(array $emails, $subject, $body_html, $body_text, $goto_link, $service_name)
    {
        $this->emails       = $emails;
        $this->subject      = $subject;
        $this->body_html    = $body_html;
        $this->body_text    = $body_text;
        $this->goto_link    = $goto_link;
        $this->service_name = $service_name;
    }

    public function getEmails()
    {
        return $this->emails;
    }

    public function addEmail($email)
    {
        $this->emails[] = $email;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getHTMLBody()
    {
        return $this->body_html;
    }

    public function getTextBody()
    {
        return $this->body_text;
    }

    public function hasTextBody()
    {
        return $this->body_text != '';
    }

    public function hasHTMLBody()
    {
        return $this->body_html != '';
    }

    public function getGotoLink()
    {
        return $this->goto_link;
    }

    public function getServiceName()
    {
        return $this->service_name;
    }
}
