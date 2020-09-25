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

use Codendi_HTMLPurifier;
use Codendi_Mail;
use ForgeConfig;
use Tuleap\InstanceBaseURLBuilder;

class InvitationEmailNotifier
{
    /**
     * @var InstanceBaseURLBuilder
     */
    private $instance_base_url_builder;

    public function __construct(InstanceBaseURLBuilder $instance_base_url_builder)
    {
        $this->instance_base_url_builder = $instance_base_url_builder;
    }

    public function send(\PFUser $current_user, string $email): bool
    {
        $mail = new Codendi_Mail();

        $template = new \Tuleap_Template_Mail();
        $template->set('remove_footer', true);
        $mail->setLookAndFeelTemplate($template);

        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->addAdditionalHeader('Reply-To', $current_user->getEmail());
        $mail->setTo($email);
        $mail->setSubject(sprintf(_('Invitation to register on %s'), ForgeConfig::get('sys_name')));

        $body = sprintf(
            _('%s invited you to log on %s, you need to register first:'),
            $current_user->getRealName(),
            ForgeConfig::get('sys_name'),
        );
        $body .= "\r\n" . $this->instance_base_url_builder->build() . '/account/register.php';

        $mail->setBodyHtml(Codendi_HTMLPurifier::instance()->purify($body, Codendi_HTMLPurifier::CONFIG_BASIC));
        $mail->setBodyText($body);

        return $mail->send();
    }
}
