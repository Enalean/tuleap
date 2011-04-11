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
require_once 'Zend/Mail.php';

class Codendi_Mail {

    const FORMAT_TEXT  = 0;
    const FORMAT_HTML  = 1;

    var $mailHtml;
    var $userDao;

    /**
     * Constructor
     */
    function __construct() {
        $this->mailHtml = new Zend_Mail('UTF-8');
        $this->userDao = $this->getUserDao();
    }

    /**
     * Return Zend_Mail object
     *
     * @return Zend_Mail object
     */
    function getMailHtml() {
        return $this->mailHtml;
    }

    /**
     * @return UserDao
     */
    protected function getUserDao() {
        if (!$this->userDao) {
          $this->userDao = new UserDao(CodendiDataAccess::instance());
        }
        return $this->userDao;
    }

    /**
     * Check if given user is autorised to get mails (Ie. Active or Restricted) user.
     *
     * @param Array of users $recipArray
     *
     * @return Array of user_name and mail
     */
    function _validateRecipient($recipArray) {
        $retArray = array();
        $allowedStatus = array('A', 'R', 'P', 'V', 'W');
        foreach($recipArray as $user) {
            if (in_array($user->getStatus(), $allowedStatus)) {
                $retArray[] = array('real_name' => $user->getRealName(), 'email' => $user->getEmail());
            }
        }
        return $retArray;
    }

    /**
     *
     * @param Array of User $to
     */
    function setToUser($to) {
        $arrayTo = $this->_validateRecipient($to);
        foreach ($arrayTo as $to) {
            $this->mailHtml->addTo($to['email'], $to['real_name']);
        }
    }

    function setFrom($email) {
        $this->mailHtml->setFrom($email);
    }

    function setSubject($subject) {
        $this->mailHtml->setSubject($subject);
    }

    function getSubject() {
        return $this->mailHtml->getSubject();
    }

    /**
     * Return Array of uses given their emails
     *
     * @param Array of usernames and mails $mailArray
     * 
     * @return Array of User
     */
    function validateMailList($mailArray) {
        $userManager = UserManager::instance();
        $userArray  = array();
        foreach($mailArray as $key => $ident) {
            $ident = trim($ident);
            $user = $userManager->getUserByEmail($ident);
            if (!$user) {
                $user = $userManager->findUser($ident);
            }
            if ($user) {
                $userArray[] = $user;
            }
        }
        return $userArray;
    }

    /**
     *
     * @param array of User $bcc
     */
    function setBccUser($bcc) {
        $arrayBcc = $this->_validateRecipient($cc);
        foreach ($arrayBcc as $user) {
            $this->mailHtml->addBcc($user['email'], $user['real_name']);
        }
    }

    /**
     *
     * @param Array $cc
     */
    function setCcUser($cc) {
        $arrayCc = $this->_validateRecipient($cc);
        foreach ($arrayCc as $user) {
            $this->mailHtml->addCc($user['email'], $user['real_name']);
        }
    }

    /**
     * Check if given mail/user_name is valid (Ie. Active or Restricted) user.
     *
     * @param list of emails/user_name $mailList
     *
     * @return Array of real_name and mail
     */
    function _validateRecipientMail($mailList) {
        $mailArray = split('[;,]', $mailList);
        $retArray = array();
        $allowedStatus = array('A', 'R', 'P', 'V', 'W');
        $userManager = UserManager::instance();
        foreach($mailArray as $ident) {
            $ident = trim($ident);
            if(!empty($ident)) {
                if (validate_email($ident)) {
                    $user = $userManager->getUserByEmail($ident);
                } else {
                    $user = $userManager->findUser($ident);
                }
            }
            if ($user) {
                if (in_array($user->getStatus(), $allowedStatus)) {
                    $retArray[] = array('email' => $user->getEmail(), 'real_name' => $user->getRealName());
                }
            } else {
                $retArray[] = array('email' =>$ident, 'real_name' =>'');
            }
        }
        return $retArray;
    }

    /**
     *
     * @param String  $to
     * @param Boolean $raw
     */
    function setTo($to, $raw=false) {
        if(!$raw) {
            $to = $this->_validateRecipientMail($to);
            $this->mailHtml->addTo($to['email'], $to['real_name']);
        } else {
            $this->mailHtml->addTo($to , '');
        }
    }

    /**
     * Return list of destination mail addresses separated by comma
     *
     * @return String
     */
    function getTo() {
        return $this->_getRecipientsFromHeader('To');
    }

    /**
     *
     * @param String  $bcc
     * @param Boolean $raw
     */
    function setBcc($bcc, $raw=false) {
        if(!$raw) {
            $bcc = $this->_validateRecipientMail($bcc);
            $this->mailHtml->addBcc($bcc['email'], $bcc['real_name']);
        } else {
            $this->mailHtml->addBcc($bcc , '');
        }
    }

    /**
     * Return list of mail addresses in BCC separated by comma
     *
     * @return String
     */
    function getBcc() {
        return $this->_getRecipientsFromHeader('Bcc');
    }

    /**
     *
     * @param String  $cc
     * @param Boolean $raw
     */
    function setCc($cc, $raw=false) {
        if(!$raw) {
            $cc = $this->_validateRecipientMail($cc);
            $this->mailHtml->addCc($cc['email'], $cc['real_name']);
        } else {
            $this->mailHtml->addCc($cc , '');
        }
    }

    /**
     * Return list of mail addresses in CC separated by comma
     *
     * @return String
     */
    function getCc() {
        return $this->_getRecipientsFromHeader('Cc');
    }

    /**
     * Return list of mail addresses separated by comma, from the headers, depending on the type
     *
     * @param String $recipientType Allowed values are "To", "Cc" and "Bcc"
     *
     * @return String
     */
    function _getRecipientsFromHeader($recipientType) {
        $allowed = array('To', 'Cc', 'Bcc');
        if (in_array($recipientType, $allowed)) {
            $headers = $this->mailHtml->getHeaders();
            if (isset($headers[$recipientType])) {
                unset ($headers[$recipientType]['append']);
                return implode(', ', $headers[$recipientType]);
            }
        }
    }

    function setBodyHtml($message) {
        $this->_body = $message;
        $this->mailHtml->setBodyHtml($message);
    }

    /**
     * Return the mails body
     *
     * @return String
     */
    function getBody() {
        return $this->_body;
    }

    function setBodyText($message) {
        $this->_body = $message;
        $this->mailHtml->setBodyText($message);
    }

    function send() {
        $params = array('mail'   => $this,
                        'header' => $this->mailHtml->getHeaders());
        $em = EventManager::instance();
        $em->processEvent('mail_sendmail', $params);

        return $this->mailHtml->send();
    }
}

?>
