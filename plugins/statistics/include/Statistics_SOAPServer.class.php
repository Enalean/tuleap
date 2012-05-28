<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
 
/**
 * Wrapper for statistics related SOAP methods
 */
class Statistics_SOAPServer {
    
    /**
     * @var SOAP_RequestValidator
     */
    private $soap_request_validator;

    /**
     * @var Statistics_DiskUsageManager 
     */
    private $disk_usage_manager;

    public function __construct(SOAP_RequestValidator $soap_request_validator, Statistics_DiskUsageManager $disk_usage_manager) {
        $this->soap_request_validator = $soap_request_validator;
        $this->disk_usage_manager     = $disk_usage_manager;
    }
    
    /**
     * Returns the amount of disk space used by the project.
     *  
     *  Returned format:
     *  <code>
     *  array(
     *      "total" => total size in bytes,
     *      "quota" => allowed size in bytes
     *  )
     *  </code>
     *  
     *  Example:
     *  <code>
     *  array(
     *      "total" => 2560,
     *      "quota" => 52428800,
     *  )
     *  </code>
     *  -> On a quota of 50MB (52428800 bytes), 2.5kB are used.
     *
     *
     * Error codes:
     * * 3001, Invalid session (wrong $sessionKey)
     * * 3002, User do not have access to the project
     *
     * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
     * @param int    $group_id   the ID of the group we want statistics
     *
     * @return ArrayOfStatistics
     */
    function getProjectDiskStats($sessionKey, $group_id) {
        try {
            $user    = $this->soap_request_validator->continueSession($sessionKey);
            $project = $this->soap_request_validator->getProjectById($group_id);
            $this->soap_request_validator->assertUserCanAccessProject($user, $project);
            
            $this->disk_usage_manager = new Statistics_DiskUsageManager();
            
            $total = $this->disk_usage_manager->returnTotalProjectSize($group_id);
            
            $allowed_quota_in_GB = $this->disk_usage_manager->getProperty('allowed_quota');
            $allowed_quota_in_B  = $this->gigabytesToBytes($allowed_quota_in_GB);
            
            return array(
                'total' => $total,
                'quota' => $allowed_quota_in_B
            );
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }
    
    private function gigabytesToBytes($gigabytes) {
        return $gigabytes * 1024 * 1024 * 1024;
    }
}
?>
