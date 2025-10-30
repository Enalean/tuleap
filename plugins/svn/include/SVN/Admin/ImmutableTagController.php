<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
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

namespace Tuleap\SVN\Admin;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use System_Command_CommandException;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Repository;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\ServiceSvn;
use Valid_String;
use Valid_Text;

class ImmutableTagController
{
    private $svnlook;
    private $immutable_tag_creator;
    private $immutable_tag_factory;
    private $repository_manager;

    public function __construct(
        RepositoryManager $repository_manager,
        Svnlook $svnlook,
        ImmutableTagCreator $immutable_tag_creator,
        ImmutableTagFactory $immutable_tag_factory,
    ) {
        $this->repository_manager    = $repository_manager;
        $this->svnlook               = $svnlook;
        $this->immutable_tag_creator = $immutable_tag_creator;
        $this->immutable_tag_factory = $immutable_tag_factory;
    }

    public function displayImmutableTag(ServiceSvn $service, HTTPRequest $request)
    {
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());

        $title = $GLOBALS['Language']->getText('global', 'Administration');

        try {
            $existing_tree = $this->svnlook->getTree($repository);
        } catch (System_Command_CommandException $ex) {
            $existing_tree = ImmutableTagPresenter::$SO_MUCH_FOLDERS;
        }

        $service->renderInPageRepositoryAdministration(
            $request,
            $repository->getName() . ' – ' . $title,
            'admin/immutable_tag',
            new ImmutableTagPresenter(
                $repository,
                CSRFSynchronizerTokenPresenter::fromToken($this->getToken($repository)),
                $this->immutable_tag_factory->getByRepositoryId($repository),
                $existing_tree,
                $title
            ),
            '',
            $repository,
        );
    }

    public function saveImmutableTag(ServiceSvn $service, HTTPRequest $request)
    {
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());

        $this->getToken($repository)->check();

        $request->valid(new Valid_String('post_changes'));
        $request->valid(new Valid_String('SUBMIT'));
        if ($request->isPost() && $request->existAndNonEmpty('post_changes')) {
            $vimmutable_tag_path      = new Valid_Text('immutable-tags-path');
            $vimmutable_tag_whitelist = new Valid_Text('immutable-tags-whitelist');

            try {
                if (
                    $request->valid($vimmutable_tag_path)
                    && $request->valid($vimmutable_tag_whitelist)
                ) {
                    $immutable_tags_path = trim($request->get('immutable-tags-path'));

                    $immutable_tags_whitelist = trim($request->get('immutable-tags-whitelist'));
                    $this->immutable_tag_creator->save($repository, $immutable_tags_path, $immutable_tags_whitelist);
                }
            } catch (CannotCreateImmuableTagException $exception) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-svn', 'Unable to save immutable tags.')
                );
            }

            $this->displayImmutableTag($service, $request);
        }
    }

    private function getToken(Repository $repository): CSRFSynchronizerTokenInterface
    {
        return new CSRFSynchronizerToken(SVN_BASE_URL . '/?' . http_build_query([
            'group_id' => $repository->getProject()->getId(),
            'action' => 'display-immutable-tag',
            'repo_id' => $repository->getId(),
        ]));
    }
}
