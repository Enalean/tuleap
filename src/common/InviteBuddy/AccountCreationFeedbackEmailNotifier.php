<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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


namespace Tuleap\InviteBuddy;

use Codendi_Mail;
use ForgeConfig;
use TemplateRendererFactory;
use Tuleap\mail\TemplateWithoutFooter;

class AccountCreationFeedbackEmailNotifier
{
    public function send(\PFUser $from_user, \PFUser $just_created_user): bool
    {
        $mail = new Codendi_Mail();
        $mail->setLookAndFeelTemplate(new TemplateWithoutFooter());
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($from_user->getEmail());
        $mail->setSubject(sprintf(_('Invitation complete!'), ForgeConfig::get('sys_name')));

        $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../../templates/invite_buddy");

        $presenter = [
            'user' => \UserHelper::instance()->getDisplayNameFromUser($just_created_user),
            'email' => $just_created_user->getEmail(),
            'instance_name' => ForgeConfig::get('sys_name'),
        ];
        $mail->setBodyHtml($renderer->renderToString('account-creation-feedback', $presenter));
        $mail->setBodyHtml($renderer->renderToString('account-creation-feedback-text', $presenter));

        return $mail->send();
    }
}
