<?php
/**
 * Copyright Enalean (c) 2013-2015. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * Central access point for things that needs to happen when post-receive is
 * executed
 */
class Git_Hook_PostReceive {

    const DEFAULT_MAIL_SUBJECT = 'Git notification';
    const DEFAULT_FROM         = 'git';

    /** @var Git_Hook_LogAnalyzer */
    private $log_analyzer;

    /** @var GitRepositoryFactory  */
    private $repository_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var Git_Ci_Launcher */
    private $ci_launcher;

    /** @var Git_Hook_ParseLog */
    private $parse_log;

    /** @var Git_SystemEventManager */
    private $system_event_manager;

    /** @var Git_GitRepositoryUrlManager */
    private $repository_url_manager;

    /** * @var EventManager */
    private $event_manager;

    public function __construct(
            Git_Hook_LogAnalyzer $log_analyzer,
            GitRepositoryFactory $repository_factory,
            UserManager $user_manager,
            Git_Ci_Launcher $ci_launcher,
            Git_Hook_ParseLog $parse_log,
            Git_GitRepositoryUrlManager $repository_url_manager,
            Git_SystemEventManager $system_event_manager,
            EventManager $event_manager) {
        $this->log_analyzer           = $log_analyzer;
        $this->repository_factory     = $repository_factory;
        $this->user_manager           = $user_manager;
        $this->ci_launcher            = $ci_launcher;
        $this->parse_log              = $parse_log;
        $this->system_event_manager   = $system_event_manager;
        $this->repository_url_manager = $repository_url_manager;
        $this->event_manager          = $event_manager;
    }

    public function beforeParsingReferences($repository_path) {
        $repository = $this->repository_factory->getFromFullPath($repository_path);
        if ($repository !== null) {
            $this->system_event_manager->queueGrokMirrorManifestFollowingAGitPush($repository);
            $this->event_manager->processEvent(GIT_HOOK_POSTRECEIVE, array(
                'repository' => $repository
            ));
        }
    }

    public function execute($repository_path, $user_name, $oldrev, $newrev, $refname, MailBuilder $mail_builder) {
        $repository = $this->repository_factory->getFromFullPath($repository_path);
        if ($repository !== null) {
            $user = $this->user_manager->getUserByUserName($user_name);
            if ($user === null) {
                $user = new PFUser(array('user_id' => 0));
            }
            $this->sendMail($repository, $mail_builder, $oldrev, $newrev, $refname);
            $this->executeForRepositoryAndUser($repository, $user, $oldrev, $newrev, $refname);
            $this->event_manager->processEvent(GIT_HOOK_POSTRECEIVE_REF_UPDATE, array(
                'repository' => $repository,
                'oldrev'     => $oldrev,
                'newrev'     => $newrev,
                'refname'    => $refname,
                'user'       => $user,
            ));
        }
    }

    private function executeForRepositoryAndUser(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname) {
        $this->ci_launcher->executeForRepository($repository);

        $push_details = $this->log_analyzer->getPushDetails($repository, $user, $oldrev, $newrev, $refname);
        $this->parse_log->execute($push_details);
    }

    /**
     * @return bool
     */
    private function sendMail(GitRepository $repository, MailBuilder $mail_builder, $oldrev, $newrev, $refname) {
        $mail_raw_output = array();
        exec('/usr/share/codendi/plugins/git/hooks/post-receive-email ' . escapeshellarg($oldrev) . ' ' .
            escapeshellarg($newrev) . ' ' . escapeshellarg($refname), $mail_raw_output);

        $subject       = isset($mail_raw_output[0]) ? $mail_raw_output[0] : self::DEFAULT_MAIL_SUBJECT;
        $mail_enhancer = new MailEnhancer();
        $this->addAdditionalMailHeaders($mail_enhancer, $mail_raw_output);
        $this->setFrom($mail_enhancer);

        $body          = $this->createMailBody($mail_raw_output);
        $access_link   = $repository->getDiffLink($this->repository_url_manager, $newrev);
        $notification  = new Notification($repository->getNotifiedMails(), $subject, '', $body, $access_link, 'Git');

        return $mail_builder->buildAndSendEmail($repository->getProject(), $notification, $mail_enhancer);
    }

    private function setFrom(MailEnhancer $mail_enhancer) {
        $email_domain = ForgeConfig::get('sys_default_mail_domain');

        if (! $email_domain) {
            $email_domain = ForgeConfig::get('sys_default_domain');
        }

        $mail_enhancer->addHeader('From', self::DEFAULT_FROM . '@' . $email_domain);
    }

    private function addAdditionalMailHeaders(MailEnhancer $mail_enhancer, $mail_raw_output) {
        foreach (array_slice($mail_raw_output, 1, 4) as $raw_header) {
            $header = explode(':', $raw_header);
            $mail_enhancer->addHeader($header[0], $header[1]);
        }
    }

    /**
     * @return string
     */
    private function createMailBody($mail_raw_output) {
        $body = '';
        foreach (array_slice($mail_raw_output, 5) as $body_part) {
            $body .= $body_part . "\n";
        }
        return $body;
    }
}
