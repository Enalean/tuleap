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

class Email extends Record
{
    function launch()
    {
        global $interface;
        global $configArray;

        if (isset($_POST['submit'])) {
            $result = $this->sendEmail($_POST['to'], $_POST['from'], $_POST['message']);
            if (!PEAR::isError($result)) {
                parent::launch();
                exit();
            } else {
                $interface->assign('message', $result->getMessage());
            }
        }
        
        // Display Page
        if (isset($_GET['lightbox'])) {
            $interface->assign('title', $_GET['message']);
            return $interface->fetch('WorldCat/email.tpl');
        } else {
            $interface->setPageTitle('Email Record');
            $interface->assign('subTemplate', 'email.tpl');
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl', 'RecordEmail' . $_GET['id']);
        }
    }
    
    function sendEmail($to, $from, $message)
    {
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
        $title = trim($title);
        
        $subject = translate("Library Catalog Record") . ": " . $title;
        $interface->assign('from', $from);
        $interface->assign('title', $title);
        $interface->assign('recordID', $_GET['id']);
        $interface->assign('message', $message);
        $body = $interface->fetch('Emails/worldcat-record.tpl');

        $mail = new VuFindMailer();
        return $mail->send($to, $from, $subject, $body);
    }
}
?>
