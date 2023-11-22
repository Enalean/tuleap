<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook;

use Codendi_Mail;
use ForgeConfig;
use Git_GitRepositoryUrlManager;
use GitRepository;
use MailBuilder;
use MailEnhancer;
use Tuleap\Notification\Notification;
use Tuleap\ServerHostname;

class PostReceiveMailSender
{
    public const DEFAULT_MAIL_SUBJECT = 'Git notification';
    public const DEFAULT_FROM         = 'git';

    public const TIMEOUT_EXIT_CODE = 124;
    public const INITIAL_COMMIT    = '0000000000000000000000000000000000000000';

    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $repository_url_manager;

    /**
     * @var MailBuilder
     */
    private $mail_builder;

    /**
     * @var PostReceiveMailsRetriever
     */
    private $mails_retriever;

    public function __construct(
        Git_GitRepositoryUrlManager $repository_url_manager,
        MailBuilder $mail_builder,
        PostReceiveMailsRetriever $mails_retriever,
    ) {
        $this->repository_url_manager = $repository_url_manager;
        $this->mail_builder           = $mail_builder;
        $this->mails_retriever        = $mails_retriever;
    }

    /**
     * @return bool
     */
    public function sendMail(GitRepository $repository, $oldrev, $newrev, $refname)
    {
        $notified_mails = $this->mails_retriever->getNotifiedMails($repository);
        if (count($notified_mails) === 0) {
            return true;
        }
        $mail_raw_output  = [];
        $exit_status_code = 0;
        exec('GIT_DIR=' . escapeshellarg($repository->getFullPath()) .
            ' /usr/share/tuleap/plugins/git/hooks/post-receive-email ' . escapeshellarg($oldrev) . ' ' .
            escapeshellarg($newrev) . ' ' . escapeshellarg($refname), $mail_raw_output, $exit_status_code);

        $subject       = isset($mail_raw_output[0]) ? $mail_raw_output[0] : self::DEFAULT_MAIL_SUBJECT;
        $mail_enhancer = new MailEnhancer();
        $this->addAdditionalMailHeaders($mail_enhancer, $mail_raw_output);
        $this->setFrom($mail_enhancer);

        $body         = $this->createMailBody($mail_raw_output);
        $access_link  = $repository->getDiffLink($this->repository_url_manager, $newrev);
        $notification = new Notification($notified_mails, $subject, '', $body, $access_link, 'Git');

        if ($exit_status_code === self::TIMEOUT_EXIT_CODE) {
            $this->warnSiteAdministratorOfAMisuseOfAGitRepo($repository, $oldrev, $refname);
        }

        return $this->mail_builder->buildAndSendEmail($repository->getProject(), $notification, $mail_enhancer);
    }

    private function setFrom(MailEnhancer $mail_enhancer)
    {
        $email_domain = $this->getEmailDomain();

        $mail_enhancer->addHeader('From', self::DEFAULT_FROM . '@' . $email_domain);
    }

    /**
     * @return string
     */
    private function getEmailDomain()
    {
        $email_domain = ForgeConfig::get('sys_default_mail_domain');

        if (! $email_domain) {
            $email_domain = ServerHostname::rawHostname();
        }

        return $email_domain;
    }

    private function addAdditionalMailHeaders(MailEnhancer $mail_enhancer, $mail_raw_output)
    {
        foreach (array_slice($mail_raw_output, 1, 4) as $raw_header) {
            $header = explode(':', $raw_header);
            $mail_enhancer->addHeader($header[0], $header[1]);
        }
    }

    /**
     * @return string
     */
    private function createMailBody($mail_raw_output)
    {
        $body = '';
        foreach (array_slice($mail_raw_output, 5) as $body_part) {
            $body .= $body_part . "\n";
        }
        return $body;
    }

    private function warnSiteAdministratorOfAMisuseOfAGitRepo(
        GitRepository $repository,
        $oldrev,
        $refname,
    ) {
        /*
         * We do not want to warn the site administrator when it is the first push in the repo.
         * It is not uncommon to have a large first push (copy of an existing repo for example).
         */
        if ($this->isInitialRevision($oldrev)) {
            return;
        }

        $repository_name = $repository->getName();
        $project         = $repository->getProject();
        $project_name    = $project->getUnixName();
        $email_domain    = $this->getEmailDomain();
        $mail            = new Codendi_Mail();
        $mail->setFrom(self::DEFAULT_FROM . '@' . $email_domain);
        $mail->setTo(ForgeConfig::get('sys_email_admin'));
        $mail->setSubject("Potential misuse of Git detected in the repository $repository_name of project $project_name");
        $mail->setBodyText(
            "A recent push in $repository_name on the reference $refname has reached a timeout. " .
            "You should inspect the repository."
        );
        $mail->send();
    }

    /**
     * @return bool
     */
    private function isInitialRevision($revision)
    {
        return $revision === self::INITIAL_COMMIT;
    }
}
