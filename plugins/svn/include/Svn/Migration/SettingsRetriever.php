<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Svn\Migration;

use Project;
use SVN_Immutable_Tags_DAO;
use SvnNotificationDao;
use Tuleap\Svn\Admin\ImmutableTag;
use Tuleap\Svn\Admin\MailNotification;
use Tuleap\Svn\Repository\HookConfig;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\Settings;

class SettingsRetriever
{
    /**
     * @var SvnNotificationDao
     */
    private $notification_dao;
    /**
     * @var SVN_Immutable_Tags_DAO
     */
    private $tags_dao;

    public function __construct(
        SVN_Immutable_Tags_DAO $immutable_tags_dao,
        SvnNotificationDao $notification_dao
    ) {
        $this->tags_dao         = $immutable_tags_dao;
        $this->notification_dao = $notification_dao;
    }

    public function getSettingsFromCoreRepository(Repository $repository)
    {
        $commit_rules      = $this->getCommitRules($repository->getProject());
        $immutable_tag     = $this->getImmutableTag($repository);
        $access_file       = "";
        $mail_notification = $this->getMailNotification($repository);

        return new Settings($commit_rules, $immutable_tag, $access_file, $mail_notification);
    }

    private function getCommitRules(Project $project)
    {
        return array(
            HookConfig::MANDATORY_REFERENCE       => $project->isSVNMandatoryRef(),
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => $project->canChangeSVNLog()
        );
    }

    /**
     * @return ImmutableTag
     */
    private function getImmutableTag(Repository $repository)
    {
        $project_id = $repository->getProject()->getID();
        $paths      = $this->tags_dao->getImmutableTagsPathForProject($project_id)->getRow();

        $core_paths = "";
        if (isset($paths['paths'])) {
            $core_paths = $paths['paths'];
        }


        $whitelists     = $this->tags_dao->getImmutableTagsWhitelistForProject($project_id)->getRow();
        $core_whitelist = "";
        if (isset($whitelists['whitelist'])) {
            $core_whitelist = $whitelists['whitelist'];
        }

        return new ImmutableTag($repository, $core_paths, $core_whitelist);
    }

    /**
     * @return MailNotification[]
     */
    private function getMailNotification(Repository $repository)
    {
        $mail_notifications = array();

        $core_notifications = $this->notification_dao->getSvnMailingList($repository->getProject()->getID());
        foreach ($core_notifications as $key => $notification) {
            $mail_notifications[] = new MailNotification(
                $key,
                $repository,
                $notification['path'],
                explode(',', $notification['svn_events_mailing_list']),
                array(),
                array()
            );
        }

        return $mail_notifications;
    }
}
