<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Mail;

use ForgeConfig;
use Psr\Log\LoggerInterface;
use Project;
use Project_AccessDeletedException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Project_AccessRestrictedException;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\ProjectAccessSuspendedException;
use UserManager;

class MailFilter
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        UserManager $user_manager,
        ProjectAccessChecker $project_access_checker,
        LoggerInterface $logger
    ) {
        $this->user_manager           = $user_manager;
        $this->project_access_checker = $project_access_checker;
        $this->logger                 = $logger;
    }

    public function filter(Project $project, array $mails)
    {
        $mails = $this->deduplicateEmails($mails);

        foreach ($mails as $email) {
            $this->logger->debug("Deduplicated email: " . $email);
        }

        if ((bool) ForgeConfig::get('sys_mail_secure_mode') === false) {
            $this->logger->info("Platform is in insecure send mail mode. All notifications sent");
            return $mails;
        }

        if ($project->isPublic()) {
            $this->logger->info("Project " . $project->getPublicName() . " is public. All notifications sent");
            return $mails;
        }

        $filtered_mails = array();
        foreach ($mails as $email) {
            $users = $this->user_manager->getAllUsersByEmail($email);
            foreach ($users as $user) {
                try {
                    $this->project_access_checker->checkUserCanAccessProject($user, $project);
                    if ($user->isAlive()) {
                        $filtered_mails[] = $email;
                        $this->logger->info("Mail sent to " . $email);
                        break;
                    } else {
                        $this->logger->warning("User is not alive - Mail not sent to " . $email);
                    }
                } catch (Project_AccessPrivateException $e) {
                    $this->logger->warning("Project is private - Mail not sent to " . $email);
                } catch (Project_AccessProjectNotFoundException $e) {
                    $this->logger->warning("Project not found - Mail not sent to " . $email);
                } catch (Project_AccessDeletedException $e) {
                    $this->logger->warning("Project is deleted - Mail not sent to " . $email);
                } catch (Project_AccessRestrictedException $e) {
                    $this->logger->warning("Project is restricted - Mail not sent to " . $email);
                } catch (ProjectAccessSuspendedException $e) {
                    $this->logger->warning('Project is suspended - Mail not sent to ' . $email);
                }
            }

            if (count($users) === 0) {
                $this->logger->warning("User not found - Mail not sent to " . $email);
            }
        }
        return $filtered_mails;
    }

    /**
     * @return array
     */
    private function deduplicateEmails(array $mails)
    {
        return array_flip(array_flip(array_filter($mails)));
    }
}
