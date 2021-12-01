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

use Codendi_HTMLPurifier;
use PFUser;
use Project;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\ForumML\ThreadsDao;
use Tuleap\User\UserEmailCollection;
use UserHelper;

require_once __DIR__ . '/../../forumml_utils.php';

class MessageInfoToMessagePresenterConvertor
{
    /**
     * @var UserHelper
     */
    private $user_helper;
    /**
     * @var TlpRelativeDatePresenterBuilder
     */
    private $date_presenter_builder;
    /**
     * @var ThreadsDao
     */
    private $dao;

    public function __construct(
        UserHelper $user_helper,
        TlpRelativeDatePresenterBuilder $date_presenter_builder,
        ThreadsDao $dao,
    ) {
        $this->user_helper            = $user_helper;
        $this->date_presenter_builder = $date_presenter_builder;
        $this->dao                    = $dao;
    }

    /**
     * @param array<string, Sender> $sender_collection
     */
    public function convertToMessagePresenter(
        MessageInfo $message_info,
        UserEmailCollection $user_email_collection,
        array $sender_collection,
        PFUser $current_user,
        Project $project,
        int $list_id,
        int $thread_id,
    ): MessagePresenter {
        $has_avatar = false;
        $avatar_url = '';
        $user_name  = $message_info->getSender();

        if (isset($sender_collection[$message_info->getSender()])) {
            $user = $user_email_collection->getUserByEmail(
                $sender_collection[$message_info->getSender()]->getAddress()
            );
            if ($user) {
                $user_name  = $this->user_helper->getDisplayNameFromUser($user);
                $has_avatar = $user->hasAvatar();
                $avatar_url = $user->getAvatarUrl();
            } else {
                $user_name = $sender_collection[$message_info->getSender()]->getDisplay();
            }
        }


        $body_html = $this->getBodyHTML($message_info, $project, $list_id, $thread_id);

        return new MessagePresenter(
            $message_info->getId(),
            $body_html,
            $user_name,
            $has_avatar,
            $avatar_url,
            $message_info->getAttachments(),
            $this->date_presenter_builder->getTlpRelativeDatePresenterInInlineContext(
                $message_info->getDate(),
                $current_user
            )
        );
    }

    private function getBodyHTML(MessageInfo $message_info, Project $project, int $list_id, int $thread_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        if (strpos($message_info->getContentType(), 'multipart/') !== false) {
            $content_type = $message_info->getMsgType();
        } else {
            $content_type = $message_info->getContentType();
        }
        $is_html = strpos($content_type, "text/html") !== false;

        $body = $message_info->getCachedHtml();
        if (! $body) {
            $body        = $message_info->getBody();
            $attachments = $message_info->getAttachments();
            if (! empty($attachments)) {
                reset($attachments);
                $first_attachment = current($attachments);

                if (preg_match('/.html$/i', $first_attachment->file_name)) {
                    // By default, the first html attachment replaces the default body (text)
                    if (! $message_info->getCachedHtml() && is_file($first_attachment->file_path)) {
                        $body = file_get_contents($first_attachment->file_path);
                        // Make sure that the body is utf8
                        if (! mb_detect_encoding($body, 'UTF-8', true)) {
                            $body = mb_convert_encoding($body, 'UTF-8');
                        }
                        $is_html = true;
                    }
                }
            }

            if ($is_html) {
                $body = plugin_forumml_replace_attachment(
                    $message_info->getId(),
                    $project->getID(),
                    $list_id,
                    $thread_id,
                    $body
                );
                $body = $purifier->purify($body, Codendi_HTMLPurifier::CONFIG_FULL, $project->getID());
            } else {
                $body = $purifier->purify($body, Codendi_HTMLPurifier::CONFIG_CONVERT_HTML, $project->getID());
            }

            $this->dao->storeCachedHtml($message_info->getId(), $body);
        }

        return $body;
    }
}
