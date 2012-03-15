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

class AJAX extends Action {

    function launch()
    {
        header('Content-type: text/xml');
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        echo '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
        echo "<AJAXResponse>\n";
        if (is_callable(array($this, $_GET['method']))) {
            $this->$_GET['method']();
        } else {
            echo '<Error>Invalid Method</Error>';
        }
        echo '</AJAXResponse>';
    }
    
    function IsLoggedIn()
    {
        require_once 'services/MyResearch/lib/User.php';

        echo "<result>" .
            (UserAccount::isLoggedIn() ? "True" : "False") . "</result>";
    }

    /**
     * Support method for getItemStatuses() -- when presented with multiple values,
     * pick which one(s) to send back via AJAX.
     *
     * @access  private
     * @param   array       $list       Array of values to choose from.
     * @param   string      $mode       config.ini setting -- first, all or msg
     * @param   string      $msg        Message to display if $mode == "msg"
     * @return  string
     */
    private function pickValue($list, $mode, $msg)
    {
        // Make sure array contains only unique values:
        $list = array_unique($list);

        // If there is only one value in the list, or if we're in "first" mode,
        // send back the first list value:
        if ($mode == 'first' || count($list) == 1) {
            return $list[0];
        // Empty list?  Return a blank string:
        } else if (count($list) == 0) {
            return '';
        // All values mode?  Return comma-separated values:
        } else if ($mode == 'all') {
            return implode(', ', $list);
        // Message mode?  Return the specified message, translated to the
        // appropriate language.
        } else {
            return translate($msg);
        }
    }

    /**
     * Get Item Statuses
     *
     * This is responsible for printing the holdings information for a
     * collection of records in XML format.
     *
     * @access  public
     * @author  Chris Delis <cedelis@uillinois.edu>
     */
    function getItemStatuses()
    {
        global $configArray;

        require_once 'CatalogConnection.php';

        // Try to find a copy that is available
        $catalog = new CatalogConnection($configArray['Catalog']['driver']);

        $result = $catalog->getStatuses($_GET['id']);

        // In order to detect IDs missing from the status response, create an 
        // array with a key for every requested ID.  We will clear keys as we
        // encounter IDs in the response -- anything left will be problems that
        // need special handling.
        $missingIds = array_flip($_GET['id']);

        // Loop through all the status information that came back
        foreach ($result as $record) {
            // If we encountered errors, skip those problem records.
            if (PEAR::isError($record)) {
                continue;
            }
            $callNumbers = array();
            $locations = array();
            $available = false;

            if (count($record)) {
                foreach ($record as $info) {
                    // Find an available copy
                    if ($info['availability']) {
                        $available = true;
                    }
                    // Store call number/location info:
                    $callNumbers[] = $info['callnumber'];
                    $locations[] = $info['location'];
                }
                
                // The current ID is not missing -- remove it from the missing list.
                unset($missingIds[$record[0]['id']]);
                
                // Determine call number string based on findings:
                $callNumber = $this->pickValue($callNumbers,
                    isset($configArray['Item_Status']['multiple_call_nos']) ?
                        $configArray['Item_Status']['multiple_call_nos'] : 'first',
                    'Multiple Call Numbers');
                
                // Determine location string based on findings:
                $location = $this->pickValue($locations,
                    isset($configArray['Item_Status']['multiple_locations']) ?
                        $configArray['Item_Status']['multiple_locations'] : 'msg',
                    'Multiple Locations');

                echo ' <item id="' . htmlspecialchars($record[0]['id']) . '">';
                echo '  <availability>' . ($available ? 'true' : 'false') . '</availability>';
                echo '  <location>' . htmlspecialchars($location) . '</location>';
                echo '  <reserve>' . htmlspecialchars($record[0]['reserve']) . '</reserve>';
                echo '  <callnumber>' . htmlspecialchars($callNumber) . '</callnumber>';
                echo ' </item>';
            }
        }

        // If any IDs were missing, send back appropriate dummy data
        foreach($missingIds as $missingId => $junk) {
            echo ' <item id="' . htmlspecialchars($missingId) . '">';
            echo '   <availability>false</availability>';
            echo '   <location>Unknown</location>';
            echo '   <reserve>N</reserve>';
            echo '   <callnumber></callnumber>';
            echo ' </item>';
        }
    }
    
    function GetSuggestion()
    {
        global $configArray;
        
        // Setup Search Engine Connection
        $class = $configArray['Index']['engine'];
        $db = new $class($configArray['Index']['url']);

        $query = 'titleStr:"' . $_GET['phrase'] . '*"';
        $result = $db->query($query, 0, 10);

        $resultList = '';
        if (isset($result['record'])) {
            foreach ($result['record'] as $record) {
                if (strlen($record['title']) > 40) {
                    $resultList .= htmlspecialchars(substr($record['title'], 0, 40)) . ' ...|';
                } else {
                    $resultList .= htmlspecialchars($record['title']) . '|';
                }
            }
            echo '<result>' . $resultList . '</result>';
        }
    }
    
    // Saves a search to User's Account
    function SaveSearch()
    {
        require_once 'services/MyResearch/lib/User.php';
        require_once 'services/MyResearch/lib/Search.php';
        
        //check if user is logged in
        if (!($user = UserAccount::isLoggedIn())) {
            echo "<result>Please Log in.</result>";
            return;
        }

        $lookfor = $_GET['lookfor'];
        $limitto = urldecode($_GET['limit']);
        $type = $_GET['type'];
        
        $search = new SearchEntry();
        $search->user_id = $user->id;
        $search->limitto = $limitto;
        $search->lookfor = $lookfor;
        $search->type = $type;
        if(!$search->find()) {
            $search = new SearchEntry();
            $search->user_id = $user->id;
            $search->lookfor = $lookfor;
            $search->limitto = $limitto;
            $search->type = $type;
            $search->created = date('Y-m-d');
            
            $search->insert();
        }
        echo "<result>Done</result>";
    }
    
    // Email Search Results
    function SendEmail()
    {
        require_once 'services/Search/Email.php';

        $emailService = new Email();
        $result = $emailService->sendEmail($_GET['url'], $_GET['to'], $_GET['from'], $_GET['message']);

        if (PEAR::isError($result)) {
            echo '<result>Error</result>';
            echo '<details>' . htmlspecialchars(translate($result->getMessage())) . '</details>';
        } else {
            echo '<result>Done</result>';
        }
    }
    
    function GetSaveStatus()
    {
        require_once 'services/MyResearch/lib/User.php';
        require_once 'services/MyResearch/lib/Resource.php';

        // check if user is logged in
        if (!($user = UserAccount::isLoggedIn())) {
            echo "<result>Unauthorized</result>";
            return;
        }

        // Check if resource is saved to favorites
        $resource = new Resource();
        $resource->record_id = $_GET['id'];
        if ($resource->find(true)) {
            if ($user->hasResource($resource)) {
                echo '<result>Saved</result>';
            } else {
                echo '<result>Not Saved</result>';
            }
        } else {
            echo '<result>Not Saved</result>';
        }
    }
    
    /**
     * Get Save Statuses
     *
     * This is responsible for printing the save status for a collection of
     * records in XML format.
     *
     * @access  public
     * @author  Chris Delis <cedelis@uillinois.edu>
     */
    function GetSaveStatuses()
    {
        require_once 'services/MyResearch/lib/User.php';
        require_once 'services/MyResearch/lib/Resource.php';

        // check if user is logged in
        if (!($user = UserAccount::isLoggedIn())) {
            echo "<result>Unauthorized</result>";
            return;
        }

        for ($i=0; ; $i++) {
            if (! isset($_GET['id' . $i])) break;
            $id = $_GET['id' . $i];
            echo '<item id="' . htmlspecialchars($id) . '">';

            // Check if resource is saved to favorites
            $resource = new Resource();
            $resource->record_id = $id;
            if ($resource->find(true)) {
                $data = $user->getSavedData($id);
                if ($data) {
                    echo '<result>';
                    // Convert the resource list into JSON so it's easily readable
                    // by the calling Javascript code.  Note that we have to entity
                    // encode it so it can embed cleanly inside our XML response.
                    $json = array();
                    foreach ($data as $list) {
                        $json[] = array('id' => $list->id, 'title' => $list->list_title);
                    }
                    echo htmlspecialchars(json_encode($json));
                    echo '</result>';
                } else {
                    echo '<result>False</result>';
                }
            } else {
                echo '<result>False</result>';
            }

            echo '</item>';
        }
    }
    
    function GetSavedData()
    {
        require_once 'services/MyResearch/lib/User.php';
        require_once 'services/MyResearch/lib/Resource.php';

        // check if user is logged in
        if ((!$user = UserAccount::isLoggedIn())) {
            echo "<result>Unauthorized</result>";
            return;
        }

        echo "<result>\n";

        $saved = $user->getSavedData($_GET['id']);
        if ($saved->notes) {
            echo "  <Notes>$saved->notes</Notes>\n";
        }

        $myTagList = $user->getTags($_GET['id']);
        if (count($myTagList)) {
            foreach ($myTagList as $tag) {
                echo "  <Tag>" . $tag->tag . "</Tag>\n";
            }
        }

        echo '</result>';
    }

}

?>
