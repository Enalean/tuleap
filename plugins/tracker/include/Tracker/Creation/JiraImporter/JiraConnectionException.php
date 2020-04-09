<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter;

class JiraConnectionException extends \Exception
{
    /**
     * @var string
     */
    private $i18n_message;

    private function __construct(string $message, string $i18n_message)
    {
        parent::__construct($message);
        $this->i18n_message = $i18n_message;
    }

    public static function credentialsKeyIsMissing(): self
    {
        return new self(
            "credentials key is mandatory",
            "credentials key is mandatory"
        );
    }

    public static function credentialsValuesAreMissing(): self
    {
        return new self(
            "server, email or token empty",
            dgettext(
                'tuleap-tracker',
                "You must provide a valid Jira server, user email and token"
            )
        );
    }

    public static function credentialsValuesAreInvalid(): self
    {
        return new self(
            "server, email or token is invalid",
            dgettext(
                'tuleap-tracker',
                "Can not connect to Jira server, please check your Jira credentials."
            )
        );
    }

    public static function connectionToServerFailed(int $error_code, string $message): self
    {
        return new self(
            "Error %s: %s",
            sprintf(
                dgettext(
                    'tuleap-tracker',
                    "Can not connect to Jira server, please check your Jira credentials."
                ),
                $error_code,
                $message
            )
        );
    }

    public static function canNotRetrieveFullCollectionException(): self
    {
        return new self(
            "can not retrieve full collection",
            dgettext(
                'tuleap-tracker',
                "Fail to retrieve the full collection of projects."
            )
        );
    }


    public static function urlIsInvalid(): self
    {
        return new self(
            "server url is invalid",
            dgettext(
                'tuleap-tracker',
                "Server url is invalid"
            )
        );
    }

    public function getI18nMessage(): string
    {
        return $this->i18n_message;
    }
}
