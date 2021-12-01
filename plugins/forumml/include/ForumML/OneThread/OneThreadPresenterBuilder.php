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

namespace Tuleap\ForumML\OneThread;

use Project;
use Tuleap\ForumML\ThreadsDao;

class OneThreadPresenterBuilder
{
    /**
     * @var ThreadsDao
     */
    private $dao;
    /**
     * @var MessageInfoToMessagePresenterConvertor
     */
    private $convertor;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(
        ThreadsDao $dao,
        MessageInfoToMessagePresenterConvertor $convertor,
        \UserManager $user_manager,
    ) {
        $this->dao          = $dao;
        $this->convertor    = $convertor;
        $this->user_manager = $user_manager;
    }

    /**
     * @throw ThreadNotFoundException
     */
    public function getThreadPresenter(
        Project $project,
        \PFUser $current_user,
        int $list_id,
        string $list_name,
        int $thread_id,
    ): OneThreadPresenter {
        $thread = $this->buildFlattenedThread($project, $list_id, $thread_id);

        $sender_collection = [];
        $emails            = [];
        foreach ($thread as $message_info) {
            $from_info = mailparse_rfc822_parse_addresses($message_info->getSender());
            if (isset($from_info[0])) {
                $emails[] = $from_info[0]['address'];

                $sender_collection[$message_info->getSender()] = new Sender(
                    $from_info[0]['address'],
                    $from_info[0]['display']
                );
            }
        }

        $user_email_collection = $this->user_manager->getUserCollectionByEmails($emails);

        reset($thread);
        $first_message = current($thread);
        $thread_name   = $first_message
            ? $this->getSubjectWithoutListName($list_name, $first_message->getSubject())
            : $list_name;

        return new OneThreadPresenter(
            $thread_name,
            array_values(
                array_map(
                    function (MessageInfo $message_info) use (
                        $user_email_collection,
                        $sender_collection,
                        $current_user,
                        $project,
                        $list_id,
                        $thread_id
                    ) {
                        return $this->convertor->convertToMessagePresenter(
                            $message_info,
                            $user_email_collection,
                            $sender_collection,
                            $current_user,
                            $project,
                            $list_id,
                            $thread_id,
                        );
                    },
                    $thread
                )
            )
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

    /**
     * In order to display the messages in the right order, we fetch the
     * all the messages with the needed headers and attachments.
     * To lower the number of SQL queries, there is 1 query per message
     * tree depth level.
     * All the messages are stored in an array indexed by the message
     * date. If dates conflict we add +1s to the message date.
     * Once all the messages are fetched, we just sort the array based on
     * the keys values.
     *
     * @return MessageInfo[]
     *
     * @throw ThreadNotFoundException
     */
    private function buildFlattenedThread(Project $project, int $list_id, int $thread_id): array
    {
        $thread = [];

        $result = $this->dao->searchMessageInfo($list_id, $thread_id);
        if (! $result) {
            throw new ThreadNotFoundException('Unable to find the thread');
        }

        $parent_ids = $this->instantiateThreadMessagesAndGetAllIds($project, $thread, $result, $list_id);
        $this->buildFlattenedThreadChildrenAtGivenLevelOfDepth($project, $thread, $parent_ids, $list_id);

        ksort($thread, SORT_NUMERIC);

        return $thread;
    }

    /**
     * @param MessageInfo[] $thread
     * @param int[]         $parent_ids
     */
    private function buildFlattenedThreadChildrenAtGivenLevelOfDepth(
        Project $project,
        array &$thread,
        array $parent_ids,
        int $list_id,
    ): void {
        if (empty($parent_ids)) {
            return;
        }

        $result = $this->dao->searchChildrenMessageInfo($list_id, $parent_ids);
        if (! $result) {
            return;
        }

        $ids = $this->instantiateThreadMessagesAndGetAllIds($project, $thread, $result, $list_id);
        $this->buildFlattenedThreadChildrenAtGivenLevelOfDepth($project, $thread, $ids, $list_id);
    }

    /**
     * @param MessageInfo[] $thread
     *
     * @return int[]
     */
    private function instantiateThreadMessagesAndGetAllIds(
        Project $project,
        array &$thread,
        array $result,
        int $list_id,
    ): array {
        $ids         = [];
        $index       = 0;
        $previous_id = -1;
        foreach ($result as $row) {
            $id_message = (int) $row['id_message'];
            if ($id_message !== $previous_id) {
                $ids[] = $id_message;
                $index = $this->insertMessageInThreadWithUniqueDateAndGetMessageIndex($thread, $row);
            }

            if (isset($row['id_attachment']) && $row['id_attachment']) {
                $url = '/plugins/forumml/upload.php?'
                    . http_build_query(
                        [
                            'group_id' => $project->getID(),
                            'list'     => $list_id,
                            'id'       => $row['id_attachment'],
                            'topic'    => $row['id_message'],
                        ]
                    );
                $thread[$index]->addAttachment(
                    new AttachmentPresenter(
                        $row['id_attachment'],
                        $row['file_name'],
                        $row['file_path'],
                        $url,
                    )
                );
            }
            $previous_id = $id_message;
        }

        return $ids;
    }

    /**
     * @param MessageInfo[] $thread
     */
    private function insertMessageInThreadWithUniqueDateAndGetMessageIndex(array &$thread, array $row): int
    {
        $author_date = strtotime($row['date']);

        $date = $author_date;
        while (isset($thread[$date])) {
            $date++;
        }
        $thread[$date] = new MessageInfo(
            $row['id_message'],
            $row['sender'],
            $row['subject'],
            $row['body'],
            $row['content_type'],
            $row['msg_type'],
            $row['cached_html'],
            new \DateTimeImmutable('@' . $author_date)
        );

        return $date;
    }
}
