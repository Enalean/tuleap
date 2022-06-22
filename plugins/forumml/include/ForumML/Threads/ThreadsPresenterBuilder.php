<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ForumML\Threads;

use Project;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\ForumML\ThreadsDao;
use Tuleap\Layout\PaginationPresenter;

class ThreadsPresenterBuilder
{
    /**
     * @var ThreadsDao
     */
    private $dao;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \UserHelper
     */
    private $user_helper;
    /**
     * @var TlpRelativeDatePresenterBuilder
     */
    private $relative_date_builder;

    public function __construct(
        ThreadsDao $dao,
        TlpRelativeDatePresenterBuilder $relative_date_builder,
        \UserManager $user_manager,
        \UserHelper $user_helper,
    ) {
        $this->dao                   = $dao;
        $this->user_manager          = $user_manager;
        $this->user_helper           = $user_helper;
        $this->relative_date_builder = $relative_date_builder;
    }

    public function getThreadsPresenter(
        Project $project,
        \PFUser $user,
        int $list_id,
        string $list_name,
        int $offset,
        string $search,
    ): ThreadsPresenter {
        $limit = 10;

        $threads    = $this->dao->searchThreadsOfLists($list_id, $limit, $offset, $search);
        $nb_threads = $this->dao->foundRows();

        $thread_info_presenter_collection = [];
        foreach ($threads as $row) {
            $thread_info_presenter_collection[] = $this->getThreadInfoPresenter(
                $project,
                $user,
                $list_name,
                $list_id,
                $row['id_message'],
                $row['subject'],
                $row['date'],
                $row['sender']
            );
        }

        $post_thread_url = '';
        if (! $user->isAnonymous()) {
            $post_thread_url = '/plugins/forumml/index.php?' .
                http_build_query(
                    [
                        'group_id' => $project->getID(),
                        'list'     => $list_id,
                    ]
                );
        }

        return new ThreadsPresenter(
            $list_name,
            $nb_threads,
            $thread_info_presenter_collection,
            $post_thread_url,
            $search,
            new PaginationPresenter(
                $limit,
                $offset,
                count($threads),
                $nb_threads,
                ThreadsController::getUrl($list_id),
                [],
            ),
        );
    }

    private function getSubjectWithoutListName(string $list_name, string $subject): string
    {
        return preg_replace(
            '/^[ ]*\[' . preg_quote($list_name, '/') . '\]/i',
            '',
            $subject
        );
    }

    private function getThreadInfoPresenter(
        Project $project,
        \PFUser $user,
        string $list_name,
        int $list_id,
        int $id_message,
        string $subject,
        string $date,
        string $sender,
    ): ThreadInfoPresenter {
        $url = '/plugins/forumml/message.php?' .
            http_build_query(
                [
                    'group_id' => $project->getID(),
                    'topic'    => $id_message,
                    'list'     => $list_id,
                ]
            );
        // Uncomment the instruction below to have new urls when you browse a ML
        // $url = OneThreadController::getUrl($list_id, $id_message);

        $has_avatar = false;
        $avatar_url = '';

        $from_info = mailparse_rfc822_parse_addresses($sender);
        if (isset($from_info[0]['address'])) {
            $display_name = trim($from_info[0]['display']);
            if ($display_name) {
                $sender = $display_name;
            }

            $sender_user = $this->user_manager
                ->getUserCollectionByEmails([$from_info[0]['address']])
                ->getUserByEmail($from_info[0]['address']);

            if ($sender_user) {
                $has_avatar = $sender_user->hasAvatar();
                $avatar_url = $sender_user->getAvatarUrl();
                $sender     = $this->user_helper->getDisplayNameFromUser($sender_user);
            }
        }

        return new ThreadInfoPresenter(
            $this->getSubjectWithoutListName($list_name, $subject),
            $this->dao->searchNbChildren([$id_message], $list_id),
            $url,
            $has_avatar,
            $avatar_url,
            $sender,
            $this->relative_date_builder->getTlpRelativeDatePresenterInInlineContext(
                new \DateTimeImmutable('@' . (int) strtotime($date)),
                $user,
            )
        );
    }
}
