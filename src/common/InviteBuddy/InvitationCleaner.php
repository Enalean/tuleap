<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

use Tuleap\Language\LocaleSwitcher;
use Tuleap\Mail\TemplateWithoutFooter;
use Tuleap\User\RetrieveUserById;

final class InvitationCleaner
{
    /**
     * @param \Closure(\Codendi_Mail): void $sendmail
     */
    public function __construct(
        private InvitationPurger $invitation_purger,
        private LocaleSwitcher $locale_switcher,
        private \TemplateRendererFactory $renderer_factory,
        private \Closure $sendmail,
        private RetrieveUserById $user_manager,
    ) {
    }

    public function cleanObsoleteInvitations(\DateTimeImmutable $today): void
    {
        $purged_invitations = $this->invitation_purger->purgeObsoleteInvitations(
            $today,
            \ForgeConfig::getInt(InvitationPurger::NB_DAYS)
        );
        if (! $purged_invitations) {
            return;
        }

        foreach ($this->getObsoleteInvitationsIndexedByUsers($purged_invitations) as $user_id => $obsolete_invitations) {
            $user = $this->user_manager->getUserById($user_id);
            if ($user) {
                $this->sendNotification($user, $obsolete_invitations);
            }
        }
    }

    /**
     * @param Invitation[] $purged_invitations
     *
     * @return array<int, Invitation[]>
     */
    private function getObsoleteInvitationsIndexedByUsers(array $purged_invitations): array
    {
        $invitation_by_user_id = [];
        foreach ($purged_invitations as $invitation) {
            if (in_array($invitation->status, [Invitation::STATUS_ERROR, Invitation::STATUS_CREATING], true)) {
                continue;
            }

            if ($invitation->to_user_id) {
                continue;
            }

            if (! isset($invitation_by_user_id[$invitation->from_user_id])) {
                $invitation_by_user_id[$invitation->from_user_id] = [];
            }
            $invitation_by_user_id[$invitation->from_user_id][] = $invitation;
        }

        return $invitation_by_user_id;
    }

    /**
     * @param Invitation[] $obsolete_invitations
     */
    private function sendNotification(\PFUser $from_user, array $obsolete_invitations): void
    {
        $this->locale_switcher->setLocaleForSpecificExecutionContext(
            $from_user->getLocale(),
            function () use ($from_user, $obsolete_invitations): void {
                $nb_obsolete_invitations = count($obsolete_invitations);

                $mail = new \Codendi_Mail();
                $mail->setLookAndFeelTemplate(new TemplateWithoutFooter());
                $mail->setFrom(\ForgeConfig::get('sys_noreply'));
                $mail->setTo($from_user->getEmail());
                $mail->setSubject(
                    ngettext(
                        'Obsolete invitation removal',
                        'Obsolete invitations removal',
                        $nb_obsolete_invitations,
                    )
                );

                $renderer = $this->renderer_factory->getRenderer(__DIR__ . "/../../templates/invite_buddy");

                $presenter = [
                    'instance_name'           => \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME),
                    'current_user_real_name'  => $from_user->getRealName(),
                    'nb_obsolete_invitations' => $nb_obsolete_invitations,
                    'obsolete_invitations'    => array_map(
                        static function (Invitation $invitation) {
                            return [
                                'to'         => $invitation->to_email,
                                'created_on' => \DateHelper::formatForLanguage($GLOBALS['Language'], $invitation->created_on),
                            ];
                        },
                        $obsolete_invitations,
                    ),
                ];
                $mail->setBodyHtml($renderer->renderToString('mail-obsolete-invitation', $presenter));
                $mail->setBodyText($renderer->renderToString('mail-obsolete-invitation-text', $presenter));

                ($this->sendmail)($mail);
            }
        );
    }
}
