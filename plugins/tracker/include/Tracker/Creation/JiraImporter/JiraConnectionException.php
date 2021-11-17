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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class JiraConnectionException extends \Exception
{
    /**
     * @var string
     */
    private $i18n_message;

    private function __construct(string $message, string $i18n_message, int $code = 0)
    {
        parent::__construct($message, $code);
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

    public static function responseIsNotOk(RequestInterface $request, ResponseInterface $response, ?string $debug_file): self
    {
        $jira_errors   = [];
        $jira_warnings = [];
        try {
            $text_body = (string) $response->getBody();
            if ($debug_file) {
                file_put_contents($debug_file, "Headers: " . PHP_EOL, FILE_APPEND);
                foreach ($response->getHeaders() as $header_name => $header_values) {
                    file_put_contents($debug_file, "$header_name: " . implode(', ', $header_values) . PHP_EOL, FILE_APPEND);
                }
                file_put_contents($debug_file, PHP_EOL . "Body content: " . PHP_EOL, FILE_APPEND);
                file_put_contents($debug_file, $text_body, FILE_APPEND);
            }
            $body = \json_decode($text_body, true, 512, JSON_THROW_ON_ERROR);
            if (isset($body['errorMessages']) && count($body['errorMessages'])) {
                $jira_errors = $body['errorMessages'];
            }
            if (isset($body['warningMessages']) && count($body['warningMessages'])) {
                $jira_warnings = $body['warningMessages'];
            }
        } catch (\JsonException $exception) {
            $message = sprintf(
                'Query was not successful (code: %d, message: "%s"). Error response cannot be read, invalid json for %s',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                (string) $request->getUri(),
            );
            return new self(
                $message,
                $message,
                $response->getStatusCode(),
            );
        }
        $message = sprintf(
            'Query `%s %s` was not successful (code: %d, message: "%s"). Jira errors:' . PHP_EOL . '%s' . PHP_EOL . 'Jira warnings:' . PHP_EOL . '%s',
            $request->getMethod(),
            (string) $request->getUri(),
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            implode(PHP_EOL, $jira_errors),
            implode(PHP_EOL, $jira_warnings),
        );
        return new self(
            $message,
            $message,
            $response->getStatusCode(),
        );
    }

    public static function connectionToServerFailed(int $error_code, string $message, RequestInterface $request): self
    {
        return new self(
            "Error can't connect to server :" .  $error_code . " " . $message . "" . $request->getUri(),
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

    public static function canNotRetrieveFullCollectionOfIssuesException(): self
    {
        return new self(
            "can not retrieve full collection of issues",
            dgettext(
                'tuleap-tracker',
                "Fail to retrieve the full collection of issues in selected tracker."
            )
        );
    }

    public static function canNotRetrieveFullCollectionOfIssueChangelogsException(): self
    {
        return new self(
            "can not retrieve full collection of issue changelogs",
            dgettext(
                'tuleap-tracker',
                "Fail to retrieve the full collection of issue changelogs in selected tracker."
            )
        );
    }

    public static function canNotRetrieveFullCollectionOfIssueCommentsException(): self
    {
        return new self(
            "can not retrieve full collection of issue comments",
            dgettext(
                'tuleap-tracker',
                "Fail to retrieve the full collection of issue comments in selected tracker."
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

    public static function canNotRetrieveUserInfoException(string $accountId): self
    {
        return new self(
            "can not retrieve user information",
            sprintf(
                dgettext(
                    'tuleap-tracker',
                    "Fail to retrieve information of user having the accountId %s."
                ),
                $accountId
            )
        );
    }

    public function getI18nMessage(): string
    {
        return $this->i18n_message;
    }
}
