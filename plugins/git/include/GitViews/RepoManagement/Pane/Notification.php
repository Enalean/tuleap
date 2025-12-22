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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use GitRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRendererFactory;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Git\GitPresenters\RepositoryPaneNotificationPresenter;
use Tuleap\Git\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Git\Notifications\CollectionOfUserToBeNotifiedPresenterBuilder;
use Tuleap\HTTPRequest;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Project\UGroups\UserGroupsPresenterBuilder;
use User_ForgeUserGroupFactory;

class Notification extends Pane
{
    public const string ID = 'mail';

    /**
     * @var CollectionOfUserToBeNotifiedPresenterBuilder
     */
    private $user_to_be_notified_builder;

    /**
     * @var CollectionOfUgroupToBeNotifiedPresenterBuilder
     */
    private $group_to_be_notified_builder;

    private AdditionalNotificationPaneContent $additional_notification_pane_content;

    public function __construct(
        GitRepository $repository,
        HTTPRequest $request,
        EventDispatcherInterface $event_manager,
        CollectionOfUserToBeNotifiedPresenterBuilder $user_to_be_notified_builder,
        CollectionOfUgroupToBeNotifiedPresenterBuilder $group_to_be_notified_builder,
        private readonly User_ForgeUserGroupFactory $user_group_factory,
    ) {
        parent::__construct($repository, $request);
        $this->user_to_be_notified_builder          = $user_to_be_notified_builder;
        $this->group_to_be_notified_builder         = $group_to_be_notified_builder;
        $this->additional_notification_pane_content = new AdditionalNotificationPaneContent($repository, $request);

        $event_manager->dispatch($this->additional_notification_pane_content);
    }

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    #[\Override]
    public function getIdentifier()
    {
        return self::ID;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    #[\Override]
    public function getTitle()
    {
        return dgettext('tuleap-git', 'Notifications');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    #[\Override]
    public function getContent()
    {
        $users  = $this->user_to_be_notified_builder->getCollectionOfUserToBeNotifiedPresenter($this->repository);
        $groups = $this->group_to_be_notified_builder->getCollectionOfUgroupToBeNotifiedPresenter($this->repository);

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates/settings');
        $html     = $renderer->renderToString(
            'notifications',
            new RepositoryPaneNotificationPresenter(
                CSRFSynchronizerTokenPresenter::fromToken($this->csrf_token()),
                $this->repository,
                $this->getIdentifier(),
                $users,
                $groups,
                new UserGroupsPresenterBuilder()->getUgroups(
                    $this->user_group_factory->getAllForProject(
                        $this->repository->getProject()
                    ),
                    []
                )
            )
        );
        $html    .= $this->additional_notification_pane_content->getHTML();

        return $html;
    }

    #[\Override]
    public function getJavascriptAssets(): array
    {
        return [
            ...$this->additional_notification_pane_content->getJavascriptAssets(),
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../../scripts/repository-admin/frontend-assets',
                    '/assets/git/repository-admin'
                ),
                'src/admin-notifications.ts',
            ),
        ];
    }
}
