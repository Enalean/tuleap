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

use Tuleap\Notification\Notification;

class SystemEvent_GIT_GERRIT_MIGRATION extends SystemEvent // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const NAME = "GIT_GERRIT_MIGRATION";

    /** @var GitDao */
    private $dao;

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var Git_RemoteServer_GerritServerFactory */
    private $server_factory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var Git_Driver_Gerrit_ProjectCreator */
    private $project_creator;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    /** @var UserManager */
    private $user_manager;

    /** @var MailBuilder */
    private $mail_builder;

    public function process()
    {
        $repo_id          = (int) $this->getParameter(0);
        $remote_server_id = (int) $this->getParameter(1);
        $this->dao->switchToGerrit($repo_id, $remote_server_id);

        $repository = $this->repository_factory->getRepositoryById($repo_id);
        if (! $repository) {
            $this->error('Unable to find repository, perhaps it was deleted in the mean time?');
            return;
        }

        try {
            $server             = $this->server_factory->getServer($repository);
            $gerrit_template_id = $this->getParameter(2);
            $gerrit_project     = $this->project_creator->createGerritProject($server, $repository, $gerrit_template_id);
            $this->project_creator->removeTemporaryDirectory();
            $this->project_creator->finalizeGerritProjectCreation($server, $repository, $gerrit_template_id);
            $this->dao->setGerritMigrationSuccess($repository->getId());
            $repository->setRemoteServerMigrationStatus(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);
            $repository->getBackend()->updateRepoConf($repository);

            $this->done("Created project $gerrit_project on " . $server->getBaseUrl());
            return true;
        } catch (Git_Driver_Gerrit_ProjectCreator_ProjectAlreadyExistsException $e) {
            $this->logError($repository, "gerrit: ", "Gerrit failure: ", $e);
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->logError($repository, "gerrit: ", "Gerrit failure: ", $e);
        } catch (Git_Command_Exception $e) {
            $this->logError($repository, "gerrit: ", "Gerrit failure: ", $e);
        } catch (Exception $e) {
            $this->logError($repository, "", "An error occured while processing event: ", $e);
        }
    }

    private function logError(GitRepository $repository, $sysevent_prefix, $log_prefix, Exception $e)
    {
        $this->dao->setGerritMigrationError($repository->getId());
        $this->error($sysevent_prefix . $e->getMessage());
        $this->logger->error($log_prefix . $this->verbalizeParameters(null), ['exception' => $e]);
        $this->sendErrorNotification($repository);
    }

    private function sendErrorNotification(GitRepository $repository)
    {
        $user = $this->getRequester();
        if (! $user->isAnonymous()) {
            $factory  = new BaseLanguageFactory();
            $language = $factory->getBaseLanguage($user->getLocale());
            $url      = \Tuleap\ServerHostname::HTTPSUrl() . GIT_BASE_URL . '/?action=repo_management&group_id=' . $repository->getProjectId() . '&repo_id=' . $repository->getId() . '&pane=gerrit';

            $notification = new Notification(
                [$user->getEmail()],
                sprintf(dgettext('tuleap-git', 'Migration of %1$s to gerrit error'), $repository->getFullName()),
                sprintf(dgettext('tuleap-git', 'An error occured while migrating repository %1$s on gerrit. Please check <a href="%2$s">gerrit settings</a> and contact site administration.'), $repository->getFullName(), $url),
                sprintf(dgettext('tuleap-git', 'An error occured while migrating repository %1$s on gerrit. Please check <a href="%2$s">gerrit settings</a> and contact site administration.'), $repository->getFullName(), $url),
                $url,
                'git'
            );
            $this->mail_builder->buildAndSendEmail($repository->getProject(), $notification, new MailEnhancer());
        }
    }

    /**
     * @return string a human readable representation of parameters
     */
    public function verbalizeParameters($with_link)
    {
        $txt = '';

        $repo_id          = (int) $this->getParameter(0);
        $remote_server_id = (int) $this->getParameter(1);

        $txt .= 'repo: ' . $this->verbalizeRepoId($repo_id, $with_link) . ', remote server: ' . $this->verbalizeRemoteServerId($remote_server_id, $with_link) . $this->verbalizeAccessRightMigration();
        $user = $this->getRequester();
        if (! $user->isAnonymous()) {
            if ($with_link) {
                $txt .= ' <a href="/admin/usergroup.php?user_id=' . $user->getId() . '">' . $user->getRealName() . '</a>';
            } else {
                $txt .= ' ' . $user->getRealName();
            }
        }

        return $txt;
    }

    private function getRequester()
    {
        $user_id = (int) $this->getParameter(3);
        return $this->user_manager->getUserById($user_id);
    }

    private function verbalizeAccessRightMigration()
    {
        $migrate_access_rights = $this->getParameter(2);
        if (! $migrate_access_rights) {
            return ', without access rights';
        }
    }

    private function verbalizeRepoId($repo_id, $with_link)
    {
        $txt = '#' . $repo_id;
        if ($with_link) {
            $hp   = Codendi_HTMLPurifier::instance();
            $repo = $this->repository_factory->getRepositoryById($repo_id);
            if ($repo) {
                $txt = $repo->getHTMLLink($this->url_manager);
            }
        }
        return $txt;
    }

    private function verbalizeRemoteServerId($remote_server_id, $with_link)
    {
        $txt = '#' . $remote_server_id;
        if ($with_link) {
            try {
                $server = $this->server_factory->getServerById($remote_server_id);
                $txt    = $server->getBaseUrl();
            } catch (Git_RemoteServer_NotFoundException $exception) {
                $txt .= " GERRIT SERVER DELETED";
            }
        }
        return $txt;
    }

    public function injectDependencies(
        GitDao $dao,
        GitRepositoryFactory $repository_factory,
        Git_RemoteServer_GerritServerFactory $server_factory,
        \Psr\Log\LoggerInterface $logger,
        Git_Driver_Gerrit_ProjectCreator $project_creator,
        Git_GitRepositoryUrlManager $url_manager,
        UserManager $user_manager,
        MailBuilder $mail_builder,
    ) {
        $this->dao                = $dao;
        $this->repository_factory = $repository_factory;
        $this->server_factory     = $server_factory;
        $this->logger             = $logger;
        $this->project_creator    = $project_creator;
        $this->url_manager        = $url_manager;
        $this->user_manager       = $user_manager;
        $this->mail_builder       = $mail_builder;
    }
}
