<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\SOAP\SOAPRequestValidator;

/**
 * Wrapper for statistics related SOAP methods
 */
class Statistics_SOAPServer
{

    /**
     * @var SOAPRequestValidator
     */
    private $soap_request_validator;

    /**
     * @var Statistics_DiskUsageManager
     */
    private $disk_usage_manager;

    /**
     * @var ProjectQuotaManager
     */
    private $project_quota_manager;

    public function __construct(SOAPRequestValidator $soap_request_validator, Statistics_DiskUsageManager $disk_usage_manager, ProjectQuotaManager $project_quota_manager)
    {
        $this->soap_request_validator = $soap_request_validator;
        $this->disk_usage_manager     = $disk_usage_manager;
        $this->project_quota_manager  = $project_quota_manager;
    }

    /**
     * Returns the amount of disk space used by the project.
     *
     *  Returned format:
     *  <code>
     *  array(
     *      "services" => array(
     *          'service name' => total size in bytes
     *      ),
     *      "total"    => total size in bytes,
     *      "quota"    => allowed size in bytes
     *  )
     *  </code>
     *
     *  Example:
     *  <code>
     *  array(
     *      "services" => array(
     *          'svn' => 60,
     *          'docman' => 2500
     *      ),
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
    public function getProjectDiskStats($sessionKey, $group_id)
    {
        try {
            $user = $this->soap_request_validator->continueSession($sessionKey);
            $this->assertUserCanAccessProject($user, $group_id);

            return $this->getDiskStatsForUser($user, $group_id);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    private function getDiskStatsForUser(PFUser $user, $group_id)
    {
        $disk_stats = array(
            'services' => array(),
            'total'    => $this->disk_usage_manager->returnTotalProjectSize($group_id),
            'quota'    => $this->getAllowedQuotaInBytes($group_id),
        );

        if ($this->userHasAdminPrivileges($user, $group_id)) {
            $disk_stats['services'] = $this->disk_usage_manager->returnTotalServiceSizeByProject($group_id);
        }

        return $disk_stats;
    }

    private function assertUserCanAccessProject(PFUser $user, $group_id)
    {
        $project = $this->soap_request_validator->getProjectById($group_id, 'statistics');
        $this->soap_request_validator->assertUserCanAccessProject($user, $project);
    }

    private function getAllowedQuotaInBytes($group_id)
    {
        $allowed_quota_in_GB = $this->project_quota_manager->getProjectCustomQuota($group_id);
        if (! $allowed_quota_in_GB) {
            $allowed_quota_in_GB = $this->disk_usage_manager->getProperty('allowed_quota');
        }
        return $this->gigabytesToBytes($allowed_quota_in_GB);
    }

    private function gigabytesToBytes($gigabytes)
    {
        return $gigabytes * 1024 * 1024 * 1024;
    }

    private function userHasAdminPrivileges($user, $group_id)
    {
        return ($user->isSuperUser() || $user->isMember($group_id, 'A'));
    }
}
