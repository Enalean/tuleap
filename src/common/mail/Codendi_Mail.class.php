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
    function setTo($to) {
        $arrayTo = $this->_validateRecipient($to);
        foreach ($arrayTo as $to) {
            $this->mailHtml->addTo($to['email'], $to['user_name']);
        }
    }

    function setFrom($email) {
        $this->mailHtml->setFrom($email);
    }

    function setSubject($subject) {
        $this->mailHtml->setSubject($subject);
    }

    /**
     * Return Array of uses given their emails
     *
     * @param Array of mails $mailArray
     * 
     * @return Array of User
     */
    function validateMailList($mailArray) {
        $userManager = UserManager::instance();
        $userArray  = array();
        foreach($mailArray as $key => $cc) {
            $cc = trim($cc);
            $user = $userManager->findUser($cc);
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
    function setBcc($bcc) {
        $arrayBcc = $this->_validateRecipient($cc);
        foreach ($arrayBcc as $user) {
            $this->mailHtml->addBcc($user['email'], $user['user_name']);
        }
    }

    /**
     *
     * @param Array $cc
     */
    function setCc($cc) {
        //if (is_a($to, 'User')) {
        $arrayCc = $this->_validateRecipient($cc);
        //}
        foreach ($arrayCc as $user) {
            $this->mailHtml->addCc($user['email'], $user['user_name']);
        }
    }

    function setBodyHtml($message) {
        $this->mailHtml->setBodyHtml($message);
    }

    function setBodyText($message) {
        $this->mailHtml->setBodyText($message);
    }

    function send() {
        $this->mailHtml->send();
    }
}

?>
