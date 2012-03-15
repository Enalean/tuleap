<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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
 
require_once 'Record.php';
require_once 'sys/Mailer.php';

class SMS extends Record
{
    private $sms;
    
    function __construct()
    {
        parent::__construct();
        $this->sms = new SMSMailer();
    }
    
    function launch()
    {
        global $interface;

        if (isset($_POST['submit'])) {
            $result = $this->sendSMS();
            if (PEAR::isError($result)) {
                $interface->assign('error', $result->getMessage());
            }
            $interface->assign('subTemplate', '../Record/sms-status.tpl');
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl');
        } else {
            return $this->display();
        }
    }
    
    function display()
    {
        global $interface;

        $interface->assign('carriers', $this->sms->getCarriers());
        $interface->assign('formTargetPath',
            '/WorldCat/SMS?id=' . urlencode($_GET['id']));
        
        if (isset($_GET['lightbox'])) {
            // Use for lightbox
            $interface->assign('title', $_GET['message']);
            return $interface->fetch('Record/sms.tpl');
        } else {
            // Display Page
            $interface->setPageTitle('Text this');
            $interface->assign('subTemplate', '../Record/sms.tpl');
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl', 'RecordSMS' . $_GET['id']);
        }
    }
    
    // Email SMS
    function sendSMS()
    {
        global $configArray;
        global $interface;

        $title = '';
        if ($field = $this->record->getField('245')) {
            if ($sfield = $field->getSubfield('a')) {
                $title .= $sfield->getData() . ' ';
            }
            if ($sfield = $field->getSubfield('b')) {
                $title .= $sfield->getData();
            }
        }

        $interface->assign('title', trim($title));
        $interface->assign('recordID', $_GET['id']);
        $message = $interface->fetch('Emails/worldcat-sms.tpl');

        return $this->sms->text($_REQUEST['provider'], $_REQUEST['to'],
            $configArray['Site']['email'], $message);
    }
}
?>