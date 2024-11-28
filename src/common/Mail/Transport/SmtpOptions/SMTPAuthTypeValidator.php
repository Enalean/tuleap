<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Mail\Transport\SmtpOptions;

use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Config\ValueValidator;
use Tuleap\Mail\Transport\MailTransportBuilder;

final class SMTPAuthTypeValidator implements ValueValidator
{
    private const AUTHORIZED_AUTHS = [
        MailTransportBuilder::EMAIL_AUTH_PLAIN,
        MailTransportBuilder::EMAIL_AUTH_LOGIN,
        MailTransportBuilder::EMAIL_AUTH_XOAUTH2,
    ];

    public static function buildSelf(): self
    {
        return new self();
    }

    public function checkIsValid(string $value): void
    {
        if (in_array($value, self::AUTHORIZED_AUTHS, true)) {
            return;
        }
        throw new InvalidConfigKeyValueException('SMTP auth type can only be one of: ' . implode(
            ', ',
            array_map(static fn(string $auth) => "\"$auth\"", self::AUTHORIZED_AUTHS),
        ));
    }
}
