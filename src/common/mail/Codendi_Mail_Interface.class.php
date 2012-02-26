<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

interface Codendi_Mail_Interface {
    /**
     * User preference that stores the mail format
     */
    const PREF_FORMAT = 'user_tracker_mailformat';
    
    /**
     * Send message in HTML
     */
    const FORMAT_HTML = 'html';
    
    /**
     * Send message in Text
     */
    const FORMAT_TEXT = 'text';
    
    public function send();
    public function _validateRecipient($list);

    public function setCc($cc);
    public function setBcc($bcc);
    public function setFrom($email);
    public function setSubject($subject);
    public function setTo($to);
    public function setBody($body);

    public function getBcc();
    public function getCc();
    public function getBody();
    public function getTo();
    public function getSubject();

    public function addAdditionalHeader($name, $value);
}
?>