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

require_once 'Action.php';
require_once 'sys/Mailer.php';

class Email extends Action
{
    function launch()
    {
        global $interface;
        global $configArray;

        if (isset($_POST['submit'])) {
            $result = $this->sendEmail($_POST['url'], $_POST['to'], $_POST['from'], $_POST['message']);
            if (!PEAR::isError($result)) {
                header('Location: ' . $_POST['url']);
                exit();
            } else {
                $interface->assign('message', $result->getMessage());
            }
        }
        
        // Display Page
        if (isset($_GET['lightbox'])) {
            $interface->assign('title', $_GET['message']);
            return $interface->fetch('Search/email.tpl');
        } else {
            // If the user has disabled HTTP referer, we can't email their search
            // link without Javascript.
            if (!isset($_POST['url']) && !isset($_SERVER['HTTP_REFERER'])) {
                PEAR::raiseError(new PEAR_Error('HTTP Referer missing.'));
                exit();
            }
            // If the user resubmits the form after an error, the $_POST url
            // variable will be set and we should use that.  If this is the first
            // time through, we need to rely on the referer to find out the target.
            $searchURL = isset($_POST['url']) ? 
                $_POST['url'] : $_SERVER['HTTP_REFERER'];
            $interface->setPageTitle('Email This Search');
            $interface->assign('subTemplate', 'email.tpl');
            // For form POST:
            $interface->assign('searchURL', $searchURL);
            // For "back to search" link:
            $interface->assign('lastsearch', $searchURL);
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl');
        }
    }
    
    function sendEmail($url, $to, $from, $message)
    {
        global $interface;

        $subject = translate('Library Catalog Search Result');
        $interface->assign('from', $from);
        $interface->assign('message', $message);
        $interface->assign('msgUrl', $url);
        $body = $interface->fetch('Emails/share-link.tpl');

        $mail = new VuFindMailer();
        return $mail->send($to, $from, $subject, $body);
    }
}
?>