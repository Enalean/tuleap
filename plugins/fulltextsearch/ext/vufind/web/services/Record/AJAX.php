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
require_once 'sys/Proxy_Request.php';

global $configArray;

class AJAX extends Action {

    function AJAX()
    {
    }
    
    function launch()
    {
        header ('Content-type: text/xml');
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

        $xmlResponse = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
        $xmlResponse .= "<AJAXResponse>\n";
        if (is_callable(array($this, $_GET['method']))) {
            $xmlResponse .= $this->$_GET['method']();
        } else {
            $xmlResponse .= '<Error>Invalid Method</Error>';
        }
        $xmlResponse .= '</AJAXResponse>';
        
        echo $xmlResponse;
    }

    function IsLoggedIn()
    {
        require_once 'services/MyResearch/lib/User.php';

        return "<result>" .
            (UserAccount::isLoggedIn() ? "True" : "False") . "</result>";
    }

    // Saves a Record to User's Account
    function SaveRecord()
    {
        require_once 'services/Record/Save.php';

        if (UserAccount::isLoggedIn()) {
            $saveService = new Save();
            $result = $saveService->saveRecord();
            if (!PEAR::isError($result)) {
                return "<result>Done</result>";
            } else {
                return "<result>Error</result>";
            }
        } else {
            return "<result>Unauthorized</result>";
        }
    }

    function GetSaveStatus()
    {
        require_once 'services/MyResearch/lib/User.php';
        require_once 'services/MyResearch/lib/Resource.php';

        // check if user is logged in
        if ((!$user = UserAccount::isLoggedIn())) {
            return "<result>Unauthorized</result>";
        }

        // Check if resource is saved to favorites
        $resource = new Resource();
        $resource->record_id = $_GET['id'];
        if ($resource->find(true)) {
            if ($user->hasResource($resource)) {
                return '<result>Saved</result>';
            } else {
                return '<result>Not Saved</result>';
            }
        } else {
            return '<result>Not Saved</result>';
        }
    }

    // Email Record
    function SendEmail()
    {
        require_once 'services/Record/Email.php';

        $emailService = new Email();
        $result = $emailService->sendEmail($_GET['to'], $_GET['from'], $_GET['message']);

        if (PEAR::isError($result)) {
            return '<result>Error</result><details>' . 
                htmlspecialchars($result->getMessage()) . '</details>';
        } else {
            return '<result>Done</result>';
        }
    }

    // SMS Record
    function SendSMS()
    {
        require_once 'services/Record/SMS.php';
        $sms = new SMS();
        $result = $sms->sendSMS();
        
        if (PEAR::isError($result)) {
            return '<result>Error</result>';
        } else {
            return '<result>Done</result>';
        }
    }

    function GetTags()
    {
        require_once 'services/MyResearch/lib/Resource.php';

        $return = "<result>\n";

        $resource = new Resource();
        $resource->record_id = $_GET['id'];
        if ($resource->find(true)) {
            $tagList = $resource->getTags();
            foreach ($tagList as $tag) {
                $return .= "  <Tag count=\"" . $tag->cnt . "\">" . htmlspecialchars($tag->tag) . "</Tag>\n";
            }
        }
        
        $return .= '</result>';
        return $return;
    }

    function SaveTag()
    {
        $user = UserAccount::isLoggedIn();
        if ($user === false) {
            return "<result>Unauthorized</result>";
        }

        require_once 'AddTag.php';
        AddTag::save();

        return '<result>Done</result>';
    }
    
    function SaveComment()
    {
        require_once 'services/MyResearch/lib/Resource.php';
        
        $user = UserAccount::isLoggedIn();
        if ($user === false) {
            return "<result>Unauthorized</result>";
        }

        $resource = new Resource();
        $resource->record_id = $_GET['id'];
        if (!$resource->find(true)) {
            $resource->insert();
        }
        $resource->addComment($_REQUEST['comment'], $user);

        return '<result>Done</result>';
    }

    function GetComments()
    {
        global $interface;

        require_once 'services/MyResearch/lib/Resource.php';
        require_once 'services/MyResearch/lib/Comments.php';

        $interface->assign('id', $_GET['id']);

        $resource = new Resource();
        $resource->record_id = $_GET['id'];
        if ($resource->find(true)) {
            $interface->assign('commentList', $resource->getComments());
        }

        $html = $interface->fetch('Record/view-comments-list.tpl');
        $output = '<result>' . htmlspecialchars($html ) . '</result>';

        return $output;
    }

}
?>
