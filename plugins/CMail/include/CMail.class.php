<?php
/**
 * Copyright (c) JTekt SAS, 2012. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2012
 *
 */
require_once('common/mail/Codendi_Mail.class.php');
require_once('Zend/Mail.php');
require_once("Zend/Mail/Transport/Smtp.php");

/**
 * Class for sending an email using the zend lib.
 * 
 * It allows to send mails in html format
 * 
 */
class CMail extends Codendi_Mail {

    protected $config;
    protected $mail;

    function setConf($config) {
    	$this->config=$config; 
    }
    /**
     * Send the mail
     * 
     * @return Boolean
     */
     
     public function __construct($mail=null, $config = null) {
        $this->mail = $mail;
        $this->config = $config;
    }
    
    
    function send() {
        $params = array('mail'   => $this->mail,
                        'header' => $this->mail->mail->getHeaders());

        
        $tp = new Zend_Mail_Transport_Smtp($this->config['host'], $this->config);

        $status = false;        
        try {
            $status = $this->mail->mail->send($tp);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', Config::get('sys_email_admin')), CODENDI_PURIFIER_DISABLED);
            $GLOBALS['Response']->addFeedback('error', '>'.$e->getMessage());
        }
        $this->mail->mail->clearRecipients();
        return $status;
    }
}

?>
