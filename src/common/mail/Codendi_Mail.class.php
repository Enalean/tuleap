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
require_once('Codendi_Mail_Interface.class.php');
require_once('Tuleap_Template_Mail.class.php');

/**
 * Class for sending an email using the zend lib.
 * 
 * It allows to send mails in html format
 * 
 */
class Codendi_Mail implements Codendi_Mail_Interface {
    /**
     * @const Use the common look and feel
     *
     * The common look and feel is the pretty one you can see in trackers v3
     */
    const USE_COMMON_LOOK_AND_FEEL = true;

    /**
     * @const DO NOT use the common look and feel
     */
    const DISCARD_COMMON_LOOK_AND_FEEL = false;
    
    protected $mail;
    protected $userDao;

    /**
     * @var Tuleap_Template_Mail 
     */
    protected $look_and_feel_template;
    
    /**
     * Constructor
     */
    function __construct() {
        $this->mail = new Zend_Mail('UTF-8');
    }

    /**
     * Return Zend_Mail object
     *
     * @return Zend_Mail object
     */
    function getMail() {
        return $this->mail;
    }

    /**
     * 
     * Returns an instance of UserDao
     * 
     * @return UserDao
     */
    function getUserDao() {
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
     * Given a standard email definition, split the name and the email address
     * 
     * "name" <email> gives array(email, name).
     * if doesn't match, assume it's only email.
     *
     * @param String $mail
     *
     * @return Array
     */
    function _cleanupMailFormat($mail) {
        $pattern = '/(.*)<(.*)>/';
        if (preg_match ($pattern, $mail, $matches)) {
            // Remove extra spaces and quotes
            $name = trim(trim($matches[1]), '"\'');
            return array($matches[2], $name);
        } else {
            return array($mail, '');
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
        $userManager = UserManager::instance();
        $usersArray = $userManager->retreiveUsersFromMails($mailArray);
        if (!empty($usersArray))  {
            //Recuperate an array of valid Users passed on the users array
            $retArray = $this->_validateRecipient($usersArray['users']);
            //Recuperate an array of mails passed on the emails array
            foreach ($usersArray['emails'] as $ident) {
                $retArray[] = array('email' =>$ident, 'real_name' =>'');
            }
        }
        return $retArray;
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
            $headers = $this->mail->getHeaders();
            if (isset($headers[$recipientType])) {
                unset ($headers[$recipientType]['append']);
                return implode(', ', $headers[$recipientType]);
            }
        }
    }

    function setFrom($email) {
        list($email, $name) = $this->_cleanupMailFormat($email);
        $this->mail->setFrom($email, $name);
    }

    /**
     * Return the sender of the mail
     *
     * @return String
     */
    function getFrom() {
        return $this->mail->getFrom();
    }

    function clearFrom() {
        $this->mail->clearFrom();
    }
    
    function setSubject($subject) {
        $this->mail->setSubject($subject);
    }

    function getSubject() {
        return $this->mail->getSubject();
    }

    /**
     *
     * @param String  $to
     * @param Boolean $raw
     */
    function setTo($to, $raw=false) {
        list($to,) = $this->_cleanupMailFormat($to);
        if(!$raw) {
            $to = $this->_validateRecipientMail($to);
            if (!empty($to)) {
                foreach ($to as $row) {
                    $this->mail->addTo($row['email'], $row['real_name']);
                }
            }
        } else {
            $this->mail->addTo($to , '');
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
            if (!empty($bcc)) {
                foreach ($bcc as $row) {
                    $this->mail->addBcc($row['email'], $row['real_name']);
                }
            }
        } else {
            $this->mail->addBcc($bcc , '');
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
            if (!empty($cc)) {
                foreach ($cc as $row) {
                    $this->mail->addCc($row['email'], $row['real_name']);
                }
            }
        } else {
            $this->mail->addCc($cc , '');
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
     * @param String $message
     */
    function setBodyText($message) {
        $this->mail->setBodyText($message);
    }

    /**
     * Returns the text part of the body mail
     * 
     * @return String
     */
    function getBodyText($textOnly = true) {
        return $this->mail->getBodyText($textOnly);
    }
        
    /**
     * Set hte template to use for look and feel in html mails
     *
     * @param Tuleap_Template_Mail $tpl The template instance
     *
     * @return void
     */
    public function setLookAndFeelTemplate(Tuleap_Template_Mail $tpl) {
        $this->look_and_feel_template = $tpl;
    }
    
    /**
     * Get the template to use for look and feel in html mails
     *
     * @return Tuleap_Template_Mail
     */
    public function getLookAndFeelTemplate() {
        if (!$this->look_and_feel_template) {
            $this->look_and_feel_template = new Tuleap_Template_Mail();
        }
        return $this->look_and_feel_template;
    }
    
    /**
     * Set the html body part.
     *
     * The default is to send it through the use of a template to send pretty html 
     * email in a common format shared across the platform. Some usages require
     * to not use this template (eg: forumml, ...) it can be discarded with the 
     * second parameter $use_common_look_and_feel.
     *
     * @param String $message                  html code to send to the user
     * @param bool   $use_common_look_and_feel self::USE_COMMON_LOOK_AND_FEEL | self::DISCARD_COMMON_LOOK_AND_FEEL (default is USE)
     *
     * @return void
     */
    function setBodyHtml($message, $use_common_look_and_feel = self::USE_COMMON_LOOK_AND_FEEL) {
        if (self::USE_COMMON_LOOK_AND_FEEL == $use_common_look_and_feel) {
            $tpl = $this->getLookAndFeelTemplate();
            $tpl->set('body', $message);
            $message = $tpl->fetch();
        }
        $this->getMail()->setBodyHtml($message);
    }

    /**
     * Returns the Html part of the body mail
     * 
     * @return String
     */
    function getBodyHtml($htmlOnly = true) {
        return $this->mail->getBodyHtml($htmlOnly);
    }

    /**
     * @param String $message
     */
    function setBody($message) {
        $this->setBodyHtml($message);
    }

    /**
     * Return the mail body
     *
     * @return String
     */
    function getBody() {
        return $this->getBodyHtml();
    }

    /**
     *
     * @param Array of User $to
     * 
     * @return Array
     */
    function setToUser($to) {
        $arrayTo = $this->_validateRecipient($to);
        $arrayToRealName = array();
        foreach ($arrayTo as $to) {
            $this->mail->addTo($to['email'], $to['real_name']);
            $arrayToRealName[] = $to['real_name'];
        }
        return $arrayToRealName;
    }

    /**
     *
     * @param array of User $bcc
     * 
     * @return Array;
     */
    function setBccUser($bcc) {
        $arrayBcc = $this->_validateRecipient($bcc);
        $arrayBccRealName = array();
        foreach ($arrayBcc as $user) {
            $this->mail->addBcc($user['email'], $user['real_name']);
            $arrayBccRealName[] = $user['real_name'];
        }
        return $arrayBccRealName;
    }

    /**
     *
     * @param Array $cc
     * 
     * @return Array
     */
    function setCcUser($cc) {
        $arrayCc = $this->_validateRecipient($cc);
        $arrayCcRealName = array();
        foreach ($arrayCc as $user) {
            $this->mail->addCc($user['email'], $user['real_name']);
            $arrayCcRealName[] = $user['real_name'];
        }
        return $arrayCcRealName;
    }

    /**
     * Send the mail
     * 
     * @return Boolean
     */
    function send() {
        $params = array('mail'   => $this,
                        'header' => $this->mail->getHeaders());
        $em = EventManager::instance();
        $em->processEvent('mail_sendmail', $params);
        $status = false;        
        try {
            $status = $this->mail->send();
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('global', 'mail_failed', Config::get('sys_email_admin')), CODENDI_PURIFIER_DISABLED);
        }
        $this->mail->clearRecipients();
        return $status;
    }
    
    public function addAdditionalHeader($name, $value) {
        $this->mail->addHeader($name, $value);
    }
}

?>
