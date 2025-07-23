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

use PFUser;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class InvitationToOneRecipientSenderStub implements InvitationToOneRecipientSender
{
    private array $calls = [];

    private function __construct(private \Closure $callback)
    {
    }

    public static function withOk(): self
    {
        return new self(static fn () => Result::ok(true));
    }

    public static function withErr(): self
    {
        return new self(static fn () => Result::err(Fault::fromMessage('An error occurred')));
    }

    public static function withReturnCallback(\Closure $callback): self
    {
        return new self($callback);
    }

    #[\Override]
    public function sendToRecipient(
        PFUser $from_user,
        InvitationRecipient $recipient,
        ?\Project $project,
        ?string $custom_message,
        ?PFUser $resent_from_user,
    ): Ok|Err {
        $this->calls[] = [
            'from_user'        => $from_user,
            'recipient'        => $recipient,
            'project'          => $project,
            'custom_message'   => $custom_message,
            'resent_from_user' => $resent_from_user,
        ];

        return ($this->callback)($from_user, $recipient, $project, $custom_message, $resent_from_user);
    }

    public function hasBeenCalled(): bool
    {
        return count($this->calls) > 0;
    }

    /**
     * @return list<array{from_user: PFUser, recipient: InvitationRecipient, project: ?\Project, custom_message: ?string, resent_from_user: ?PFUser}>
     */
    public function getCalls(): array
    {
        return $this->calls;
    }
}
