<?php
/**
 *
 * Copyright (C) Villanova University 2009.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'Mail.php';
require_once 'Mail/RFC822.php';

/**
 * VuFind Mailer Class
 *
 * This is a wrapper class to load configuration options and perform email
 * functions.  See the comments in web/conf/config.ini for details on how 
 * email is configured.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class VuFindMailer
{
    protected $settings;      // settings for PEAR Mail object
    
    /**
     * Constructor
     *
     * Sets up mailing functionality using settings from config.ini.
     *
     * @access  public
     */
    public function __construct()
    {
        global $configArray;

        // Load settings from the config file into the object; we'll do the
        // actual creation of the mail object later since that will make error
        // detection easier to control.
        $this->settings = array('host' => $configArray['Mail']['host'],
                                'port' => $configArray['Mail']['port']);
        if (isset($configArray['Mail']['username']) && 
            isset($configArray['Mail']['password'])) {
            $this->settings['auth'] = true;
            $this->settings['username'] = $configArray['Mail']['username'];
            $this->settings['password'] = $configArray['Mail']['password'];
        }
    }

    /**
     * Send an email message.
     *
     * @access  public
     * @param   string  $to         Recipient email address
     * @param   string  $from       Sender email address
     * @param   string  $subject    Subject line for message
     * @param   string  $body       Message body
     * @return  mixed               PEAR error on error, boolean true otherwise
     */
    public function send($to, $from, $subject, $body)
    {
        // Validate sender and recipient
        if (!Mail_RFC822::isValidInetAddress($to)) {
            return new PEAR_Error('Invalid Recipient Email Address');
        }
        if (!Mail_RFC822::isValidInetAddress($from)) {
            return new PEAR_Error('Invalid Sender Email Address');
        }
        
        // Get mail object
        $mail =& Mail::factory('smtp', $this->settings);
        if (PEAR::isError($mail)) {
            return $mail;
        }
        
        // Send message
        $headers = array('From' => $from, 'To' => $to, 'Subject' => $subject,
            'Date' => date('D, d M Y H:i:s O'),
            'Content-Type' => 'text/plain; charset="UTF-8"');
        return $mail->send($to, $headers, $body);
    }
}

/**
 * SMS Mailer Class
 *
 * This class extends the VuFindMailer to send text messages.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class SMSMailer extends VuFindMailer
{
    // Defaults, usually overridden by contents of web/conf/sms.ini:
    private $carriers = array(
        'virgin' => array('name' => 'Virgin Mobile', 'domain' => 'vmobl.com'),
        'att' => array('name' => 'AT&T', 'domain' => 'txt.att.net'),
        'verizon' => array('name' => 'Verizon', 'domain' => 'vtext.com'),
        'nextel' => array('name' => 'Nextel', 'domain' => 'messaging.nextel.com'),
        'sprint' => array('name' => 'Sprint', 'domain' => 'messaging.sprintpcs.com'),
        'tmobile' => array('name' => 'T Mobile', 'domain' => 'tmomail.net'),
        'alltel' => array('name' => 'Alltel', 'domain' => 'message.alltel.com'),
        'Cricket' => array('name' => 'Cricket', 'domain' => 'mms.mycricket.com')
    );

    /**
     * Constructor
     *
     * Sets up SMS carriers and other settings from sms.ini.
     *
     * @access  public
     */
    public function __construct()
    {
        global $configArray;

        // if using sms.ini, then load the carriers from it
        // otherwise, fall back to the default list of US carriers
        if (isset($configArray['Extra_Config']['sms'])) {
            $smsConfig = getExtraConfigArray('sms');
            if (isset($smsConfig['Carriers']) && !empty($smsConfig['Carriers'])) {
                $this->carriers = array();
                foreach ($smsConfig['Carriers'] as $id=>$config) {
                    list($domain, $name) = explode(':', $config, 2);
                    $this->carriers[$id] = array('name'=>$name, 'domain'=>$domain);
                }
            }
        }
        
        parent::__construct();
    }

    /**
     * Get a list of carriers supported by the module.  Returned as an array of
     * associative arrays indexed by carrier ID and containing "name" and "domain"
     * keys.
     *
     * @access  public
     * @return  array
     */
    public function getCarriers()
    {
        return $this->carriers;
    }

    /**
     * Send a text message to the specified provider.
     *
     * @param   string      $provider       The provider ID to send to
     * @param   string      $to             The phone number at the provider
     * @param   string      $from           The email address to use as sender
     * @param   string      $message        The message to send
     * @access  public
     * @return  mixed               PEAR error on error, boolean true otherwise
     */
    public function text($provider, $to, $from, $message)
    {
        $knownCarriers = array_keys($this->carriers);
        if (empty($provider) || !in_array($provider, $knownCarriers)) {
            return new PEAR_Error('Unknown Carrier');
        }
        
        $to = $to . '@' . $this->carriers[$provider]['domain'];
        $mail = new VuFindMailer();
        $subject = '';
        return $this->send($to, $from, $subject, $message);
    }    
}
?>