<?php
/**
 * Copyright Enalean (c) 2014 - 2015. All rights reserved.
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
 * I'm responsible of handling what happens in pre-commit subversion hook
 */
class SVN_Hook_PreCommit extends SVN_Hook {

    /**
     * @var SVN_Immutable_Tags_Handler
     */
    private $handler;

    /**
     * @var SVN_Svnlook
     */
    private $svn_look;

    public function __construct(
        SVN_Hooks $svn_hooks,
        SVN_CommitMessageValidator $message_validator,
        SVN_Svnlook $svn_look,
        SVN_Immutable_Tags_Handler $handler,
        Logger $logger
    ) {
        parent::__construct($svn_hooks, $message_validator);

        $this->svn_look = $svn_look;
        $this->handler  = $handler;
        $this->logger   = $logger;
    }

    /**
     * Check if the commit message is valid
     *
     * @param String $repository
     * @param String $commit_message
     *
     * @throws Exception
     */
    public function assertCommitMessageIsValid($repository, $commit_message) {
        if ($this->optionDoesNotAllowEmptyCommitMessage() && $commit_message === '') {
            throw new Exception('Commit message must not be empty');
        }

        $project = $this->getProjectFromRepositoryPath($repository);
        $this->message_validator->assertCommitMessageIsValid($project, $commit_message);
    }

    private function optionDoesNotAllowEmptyCommitMessage() {
        return ! ForgeConfig::get('sys_allow_empty_svn_commit_message');
    }

    /**
     * Check if the commit is done on an allowed path
     * @param String  $repository
     * @param Integer $transaction
     * @throws Exception
     */
    public function assertCommitToTagIsAllowed($repository, $transaction) {
        $project = $this->getProjectFromRepositoryPath($repository);

        if ($this->handler->doesProjectUsesImmutableTags($project) &&
            ! $this->isCommitAllowed($project, $transaction)
        ) {
            throw new SVN_CommitToTagDeniedException("Commit to tag is not allowed");
        }
    }

   /**
     * Check if the commit target is tags
     * @param Project $project
     * @param Integer $transaction
     *
     * @return Boolean
     */
    private function isCommitAllowed($project, $transaction) {
        $paths = $this->svn_look->getTransactionPath($project, $transaction);

        $this->logger->debug("Checking if commit is done in tag");

        foreach ($paths as $path) {
            $this->logger->debug("Checking $path");
            if ($this->isCommitDoneInImmutableTag($project, $path)) {
                $this->logger->debug("$path is denied");
                return false;
            }
        }

        $this->logger->debug("Commit is allowed \o/");
        return true;
    }

   /**
     * Check if it is an update or delete to tags
     * @param String $path
     *
     * @return Boolean
     */
    private function isCommitDoneInImmutableTag(Project $project, $path) {
        $immutable_paths = explode(PHP_EOL, $this->handler->getImmutableTagsPathForProject($project->getID()));

        foreach ($immutable_paths as $immutable_path) {
            if ($this->isCommitForbidden($project, $immutable_path, $path)) {
                return true;
            }
        }

        return false;
    }

    private function isCommitForbidden(Project $project, $immutable_path, $path) {
        $immutable_path_regexp = $this->getWellFormedRegexImmutablePath($immutable_path);

        $pattern = "%^(?:
            (?:U|D)\s+$immutable_path_regexp            # U  moduleA/tags/v1
                                                        # U  moduleA/tags/v1/toto
            |
            A\s+".$immutable_path_regexp."/[^/]+/[^/]+  # A  moduleA/tags/v1/toto
            )%x";

        if (preg_match($pattern, $path)) {
            return ! $this->isCommitDoneOnWhitelistElement($project, $path);
        }
    }

    private function isCommitDoneOnWhitelistElement(Project $project, $path) {
        $whitelist = $this->handler->getAllowedTagsFromWhiteList($project);
        if (! $whitelist) {
            return false;
        }

        $whitelist_regexp = array();
        foreach ($whitelist as $whitelist_path) {
            $whitelist_regexp[] = $this->getWellFormedRegexImmutablePath($whitelist_path);
        }

        $allowed_tags = implode('|', $whitelist_regexp);

        $pattern = "%^
            A\s+(?:$allowed_tags)/[^/]+/?$  # A  tags/moduleA/v1/   (allowed)
                                            # A  tags/moduleA/toto  (allowed)
                                            # A  tags/moduleA/v1/toto (forbidden)
            %x";

        return preg_match($pattern, $path);
    }

    private function getWellFormedRegexImmutablePath($immutable_path) {
        if (strpos($immutable_path, '/') === 0) {
            $immutable_path = substr($immutable_path, 1);
        }

        if (strrpos($immutable_path, '/') === strlen($immutable_path)) {
            $immutable_path = substr($immutable_path, -1);
        }

        $immutable_path = preg_quote($immutable_path);
        $immutable_path = str_replace('\*', '[^/]+', $immutable_path);

        return $immutable_path;
    }
}