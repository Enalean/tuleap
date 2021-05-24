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

namespace Tuleap\Gitlab\Test\Builder;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\Repository\Token\IntegrationApiToken;

final class CredentialsTestBuilder
{
    /**
     * @var string
     */
    private $secret = 'My secret';
    /**
     * @var bool
     */
    private $email_already_sent = false;

    public static function get(): self
    {
        return new self();
    }

    public function withEmailAlreadySent(): self
    {
        $this->email_already_sent = true;

        return $this;
    }

    public function withoutEmailAlreadySent(): self
    {
        $this->email_already_sent = false;

        return $this;
    }

    public function build(): Credentials
    {
        return new Credentials(
            'https://gitlab.example.com',
            IntegrationApiToken::buildAlreadyKnownToken(
                new ConcealedString($this->secret),
                $this->email_already_sent,
            )
        );
    }
}
