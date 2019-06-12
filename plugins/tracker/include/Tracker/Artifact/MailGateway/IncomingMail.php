<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\MailGateway;

class IncomingMail
{
    /**
     * @var string
     */
    private $raw_mail;
    /**
     * @var \PhpMimeMailParser\Parser
     */
    private $parser;

    public function __construct($raw_mail)
    {
        $this->raw_mail = $raw_mail;
        $this->parser   = new \PhpMimeMailParser\Parser();
        $this->parser->setText($raw_mail);
    }

    /**
     * @return string
     */
    public function getRawMail()
    {
        return $this->raw_mail;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return (string) $this->parser->getHeader('subject');
    }

    /**
     * @return string
     */
    public function getBodyText()
    {
        return $this->parser->getMessageBody('text');
    }

    /**
     * @return string[]
     */
    public function getFrom()
    {
        return $this->getMailAddresses('from');
    }

    /**
     * @return string[]
     */
    public function getTo()
    {
        return $this->getMailAddresses('to');
    }

    /**
     * @return string[]
     */
    public function getCC()
    {
        return $this->getMailAddresses('cc');
    }

    /**
     * @return string[]
     */
    private function getMailAddresses($header_name)
    {
        $addresses = [];

        foreach ($this->parser->getAddresses($header_name) as $address) {
            $addresses[] = $address['address'];
        }

        return $addresses;
    }

    /**
     * @return bool
     */
    public function hasHeader($header_name)
    {
        return $this->parser->getHeader($header_name) !== false;
    }

    /**
     * @return bool|string
     */
    public function getHeaderValue($header_name)
    {
        return $this->parser->getHeader($header_name);
    }
}
