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

use Tuleap\Mail\MailAttachment;

// I am responsible for additional decorations to add to a mail
class MailEnhancer // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /** @var Array */
    private $additional_headers = [];

    /** @var Array */
    private $additional_properties = [];

    /** @var int */
    private $message_id;

    /**
     * @var MailAttachment[]
     */
    private array $attachments = [];

    /**
     * @param string $header_name
     * @param string $header_value
     */
    public function addHeader($header_name, $header_value)
    {
        $this->additional_headers[strtolower($header_name)] = $header_value;
    }

    public function addAttachment(MailAttachment $attachment): void
    {
        $this->attachments[] = $attachment;
    }

    /**
     * @return Array
     */
    private function getAdditionalHeaders()
    {
        return $this->additional_headers;
    }

    /**
     * @param string $property_name
     * @param mixed  $property_value
     */
    public function addPropertiesToLookAndFeel($property_name, $property_value)
    {
        $this->additional_properties[$property_name] = $property_value;
    }

    /**
     * @return Array
     */
    private function getAdditionalPropertiesForLookAndFeel()
    {
        return $this->additional_properties;
    }

    /**
     * @param int $id
     */
    public function setMessageId($id)
    {
        $this->message_id = $id;
    }

    private function getMessageId()
    {
        return $this->message_id;
    }

    public function enhanceMail(Codendi_Mail $mail)
    {
        $headers   = $this->getAdditionalHeaders();
        $from_mail = null;

        if (array_key_exists('from', $headers)) {
            $from_mail = $headers['from'];
            unset($headers['from']);
        }
        if ($from_mail === null && array_key_exists('reply-to', $headers)) {
            $from_mail = $headers['reply-to'];
        }

        if ($from_mail !== null) {
            $mail->clearFrom();
            $mail->setFrom($from_mail);
        }
        foreach ($headers as $name => $value) {
            $mail->addAdditionalHeader($name, $value);
        }

        foreach ($this->attachments as $attachment) {
            $mail->addAttachment($attachment->content, $attachment->filename, $attachment->mime_type);
        }

        foreach ($this->getAdditionalPropertiesForLookAndFeel() as $property => $value) {
            $mail->getLookAndFeelTemplate()->set($property, $value);
        }

        if ($this->getMessageId()) {
            $mail->setMessageId($this->getMessageId());
        }
    }
}
