<?php
/**
 * Copyright (c) JTekt SAS, 2012. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2012
 *
 */
require_once 'common/plugin/Plugin.class.php';
require_once 'ExchangeMailPluginInfo.class.php';
require_once 'ExchangeMail.class.php';


/**
 * ExchangeMailPlugin
 */
class ExchangeMailPlugin extends Plugin {


    private  $mail_param;
    /**
     * Plugin constructor
     */
    function __construct($id) {
        parent::__construct($id);
        $this->_addHook('mail_sendmail', 'sendmail', false);
        
    }
    
    
    /**
     * Hook
     * 
     * IN  $params['mail']
     * IN  $params['header']
     * 
     * @param Array $params
     * 
     * @return void
     */
    function sendmail($params) {
        
        if($params['mail'] instanceof Codendi_Mail) {//HTML mail
            $theMail=$params['mail'];
        } elseif($params['mail'] instanceof Mail){//Text mail
                    
            $theMail = new Codendi_Mail();
            
            $theMail->setFrom($params['mail']->getFrom());
            $theMail->setSubject($params['mail']->getSubject());
            $theMail->setTo($params['mail']->getTo());
            if(trim($params['mail']->getBcc())!= "") {
                $theMail->setBcc($params['mail']->getBcc());
            }
            if(trim($params['mail']->getCc())!= "") {
                $theMail->setCc($params['mail']->getCc());
            }
            $Mailbody="<pre>".$params['mail']->getBody()."</pre>";
            $params['mail']->setBody($Mailbody);
            $theMail->setBody($params['mail']->getBody());        
        }   
        $config = $this->getParam();
        $ZendMail= new ExchangeMail($theMail,$config);
        $ZendMail->send();
    }
    
    /**
     * Get the parameters from .inc file
     *
     * @return array(string=>string)
     */
    function getParam() {
        if (!$this->mail_param) {
            $this->mail_param = array();
            $keys = $this->getPluginInfo()->propertyDescriptors->getKeys()->iterator();
            foreach ($keys as $k) {
                $nk = str_replace('sys_ExchangeMail_', '', $k);
                $this->mail_param[$nk] = $this->getPluginInfo()->getPropertyValueForName($k);
            }
         }
        return $this->mail_param;
        
    }

    /**
     * @return ExchangeMailPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo) {
 
            $this->pluginInfo = new ExchangeMailPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    
}

?>
