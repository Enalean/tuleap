<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Notification;

use Codendi_Mail_Interface;
use DateTimeImmutable;
use MailManager;
use PFUser;
use TemplateRendererFactory;
use Tracker_Artifact_Changeset;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\ServerHostname;
use Tuleap\TrackerFunctions\Administration\AdministrationController;
use Tuleap\User\TuleapFunctionsUser;

final class BuildMessagesForAdmins implements MessageBuilder
{
    private const HTML_TEMPLATE = 'message_html';
    private const TEXT_TEMPLATE = 'message_txt';

    public function __construct(
        private readonly TemplateRendererFactory $renderer_factory,
    ) {
    }

    public function buildMessagesForAdmins(array $admins, Tracker_Artifact_Changeset $changeset): Ok
    {
        $messages = [];
        foreach ($admins as $admin) {
            $message_content = $this->getMessageContent($admin, $changeset);
            $messages[]      = new MessageRepresentation(
                [$admin->getEmail()],
                [],
                (new TuleapFunctionsUser())->getEmail(),
                dgettext('tuleap-tracker_functions', 'Tuleap function failed'),
                $message_content['htmlBody'],
                $message_content['textBody'],
                [],
            );
        }

        return Result::ok($messages);
    }

    /**
     * @return array{
     *     htmlBody: string,
     *     textBody: string,
     * }
     */
    private function getMessageContent(PFUser $user, Tracker_Artifact_Changeset $changeset): array
    {
        $renderer = $this->renderer_factory->getRenderer(__DIR__);

        $date      = new DateTimeImmutable();
        $presenter = [
            'artifact_id'    => $changeset->getArtifact()->getId(),
            'tracker_admin'  => ServerHostname::HTTPSUrl() . AdministrationController::getUrl($changeset->getTracker()),
            'execution_date' => date($GLOBALS['Language']->getText('system', 'datefmt'), $date->getTimestamp()),
        ];

        $html_body    = '';
        $mail_manager = new MailManager();
        $format       = $mail_manager->getMailPreferencesByUser($user);
        if ($format === Codendi_Mail_Interface::FORMAT_HTML) {
            $html_body = $renderer->renderToString(self::HTML_TEMPLATE, $presenter);
        }

        return [
            'htmlBody' => $html_body,
            'textBody' => $renderer->renderToString(self::TEXT_TEMPLATE, $presenter),
        ];
    }
}
