<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use Tuleap\Authentication\Scope\AuthenticationScope;

class AccessKeyCreationNotifier
{
    /**
     * @var string
     */
    private $server_url;
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $html_purifier;

    public function __construct(string $server_url, \Codendi_HTMLPurifier $html_purifier)
    {
        $this->server_url    = $server_url;
        $this->html_purifier = $html_purifier;
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope> $scopes
     */
    public function notifyCreation(\PFUser $user, string $description, array $scopes): void
    {
        $mail = new \Codendi_Mail();
        $mail->setFrom(\ForgeConfig::get('sys_noreply'));
        $mail->setToUser([$user]);
        $mail->setSubject(gettext('A personal access key has been added to your account'));

        $description = $description ?: '-';

        $scope_names = [];
        foreach ($scopes as $scope) {
            $scope_names[] = $scope->getDefinition()->getName();
        }

        $content = sprintf(
            ngettext(
                "A personal access key (%s) with the %s scope was recently added to your account %s.\n\nVisit %s for more information.",
                "A personal access key (%s) with the %s scopes was recently added to your account %s.\n\nVisit %s for more information.",
                count($scope_names)
            ),
            $description,
            implode(', ', $scope_names),
            \ForgeConfig::get('sys_name'),
            $this->buildURLToAccountAccessTokenSection()
        );

        $mail->setBodyText($content);
        $mail->setBodyHtml($this->html_purifier->purify($content, CODENDI_PURIFIER_BASIC));
        $mail->send();
    }

    private function buildURLToAccountAccessTokenSection(): string
    {
        return $this->server_url . '/account/#account-access-keys';
    }
}
