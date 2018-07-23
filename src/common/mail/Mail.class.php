<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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


class Mail implements Codendi_Mail_Interface {
    
    function __construct() {
        $this->setHeaderCharset('UTF-8');
        $this->setBodyCharset('UTF-8');
        $this->setMimeType('text/plain');
        $this->setTo('', true);
        $this->setBcc('', true);
        $this->setCc('', true);
        $this->setBody('', true);
        $this->clearAdditionalHeaders();
    }
    
    var $_headerCharset;
    function setHeaderCharset($charset) { 
        $this->_headerCharset = $charset; 
    }
    function getHeaderCharset() { 
        return $this->_headerCharset; 
    }
    
    var $_bodyCharset;
    function setBodyCharset($charset) { 
        $this->_bodyCharset = $charset; 
    }
    function getBodyCharset() { 
        return $this->_bodyCharset; 
    }
    
    var $_subject;
    function setSubject($subject) {
        $this->_subject = $subject;
    }
    function getSubject() {
        return $this->_subject;
    }
    function getEncodedSubject() {
        return $this->_encodeHeader($this->_subject, $this->getHeaderCharset());
    }
    
    /**
     * Function to encode a header if necessary
     * according to RFC2047
     * Filename.......: class.html.mime.mail.inc
     * Project........: HTML Mime mail class
     * Last Modified..: Date: 2002/07/24 13:14:10 
     * CVS Revision...: Revision: 1.4 
     * Copyright......: 2001, 2002 Richard Heyes
     */
    function _encodeHeader($input, $charset)
    {
        preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $input, $matches);
        foreach ($matches[1] as $value) {
            $replacement = preg_replace_callback(
                '/([\x80-\xFF])/',
                function (array $matches) {
                    return '=' . strtoupper(dechex(ord($matches[1])));
                },
                $value
            );
            $input      = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input);
        }

        return $input;
    }
    
    /**
     * Given a header, this function will decode it
     * according to RFC2047. Probably not *exactly*
     * conformant, but it does pass all the given
     * examples (in RFC2047).
     *
     * @param string Input header value to decode
     * @return string Decoded header value
     * @access private
     */
    function _decodeHeader($input)
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);

        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {

            $encoded  = $matches[1];
            $charset  = $matches[2];
            $encoding = $matches[3];
            $text     = $matches[4];

            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }

            $input = str_replace($encoded, $text, $input);
        }

        return $input;
    }    
    
    var $_body;
    function setBody($body) {
        $this->_body = $body;
    }
    function getBody() {
        return $this->_body;
    }
    
    var $_from;
    function setFrom($from) {
        $this->_from = $from;
    }
    function getFrom() {
        return $this->_from;
    }

    /**
     * Check if given mail is a valid (Ie. Active or Restricted) user.
     *
     * The given mail can by both user_name or email. Return form is always the
     * user email.
     *
     * @param $list (IN) list of email addresses separated by , or ;
     * @return list of email separated by ,
     */
    function _validateRecipient($list) {
        $recipArray = preg_split('/[;,]/D', $list);
        $retArray = array();
        $user_dao = $this->_getUserDao();
        foreach($recipArray as $email) {
            $email = trim($email);
            if(!empty($email)) {
                $dar = $user_dao->searchStatusByEmail($email);
                if ($dar->rowCount() > 0) {
                    $allowed_status = array('A', 'R', 'P', 'V', 'W');
                    $one_with_status_allowed_found = false;
                    while (($row = $dar->getRow()) && !$one_with_status_allowed_found) {
                        if (in_array($row['status'], $allowed_status)) {
                            $retArray[] = '"'.$this->_encodeHeader($row['realname'], $this->getHeaderCharset()).'" <'.$row['email'].'>';
                            $one_with_status_allowed_found = true;
                        }
                    }
                } else {
                    $retArray[] = $email;
                }
            }
        }
        return implode(', ', $retArray);
    }

    public function setToUser($to) {
        foreach ($to as $user) {
            $this->_to = array($user->getEmail());
        }
    }
    
    var $_to;
    function setTo($to, $raw=false) {
        if($raw)
            $this->_to = $to;
        else
            $this->_to = $this->_validateRecipient($to);
    }
    function getTo()  {
        return $this->_to;
    }
    
    var $_bcc;
    function setBcc($bcc, $raw=false) {
        if($raw)
            $this->_bcc = $bcc;
        else
            $this->_bcc = $this->_validateRecipient($bcc);
    }
    function getBcc()  {
        return $this->_bcc;
    }
    
    var $_cc;
    function setCc($cc, $raw=false) {
        if($raw)
            $this->_cc = $cc;
        else
            $this->_cc = $this->_validateRecipient($cc);

    }
    function getCc()  {
        return $this->_cc;
    }
        
    var $_mimeType;
    function setMimeType($mimeType) {
        $this->_mimeType = $mimeType;
    }
    function getMimeType() {
        return $this->_mimeType;
    }
    
    var $_additionalHeaders;
    function clearAdditionalHeaders() {
        $this->_additionalHeaders = array();
    }
    function addAdditionalHeader($name, $value) {
        $this->_additionalHeaders[$name] = $value;
    }
    function removeAdditionalHeader($name) {
        if (isset($this->_additionalHeaders[$name])) {
            unset($this->_additionalHeaders[$name]);
        }
    }
    
    /**
     * @returns TRUE if the mail was successfully accepted for delivery, FALSE otherwise.
     *          It is important to note that just because the mail was accepted for delivery, 
     *          it does NOT mean the mail will actually reach the intended destination. 
    **/
    function send() {
        if($this->getTo() === ''
           && $this->getCc() === '' 
           && $this->getBcc() === '') {
            return false;
        }

        $header = "From: ".$this->getFrom().$GLOBALS['sys_lf'];
    	$header .= "Content-type: ".$this->getMimeType()."; charset=".$this->getBodyCharset().$GLOBALS['sys_lf'];
        $cc = $this->getCc();
        if (strlen($cc) > 0) {
            $header .= "Cc: ".$cc.$GLOBALS['sys_lf'];
        }
        $bcc = $this->getBcc();
        if (strlen($bcc) > 0) {
            $header .= "Bcc: ".$bcc.$GLOBALS['sys_lf'];
        }
        foreach($this->_additionalHeaders as $name => $value) {
            $header .= $name.": ".$value.$GLOBALS['sys_lf'];
        }
        return $this->_sendmail($header);
    }

    /**
     * Perform effective email send.
     * @access protected
     */
    function _sendmail($header) {
        $params = array('mail' => $this,
                        'header' => $header);
        $em = EventManager::instance();
        $em->processEvent('mail_sendmail', $params);
        
        return mail($this->getTo(),
            $this->getEncodedSubject(),
            $this->getBody(),
            $header
        );
    }

    var $userDao;
    function &_getUserDao() {
        if (!is_a($this->userDao, 'UserDao')) {
            $this->userDao = new UserDao(CodendiDataAccess::instance());
        }
        return $this->userDao;
    }
    

}

?>
