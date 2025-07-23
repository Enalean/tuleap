<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Test\Stubs;

use Tuleap\Gitlab\Group\VerifyRepositoryIntegrationsAlreadyLinked;

final class VerifyRepositoryIntegrationsAlreadyLinkedStub implements VerifyRepositoryIntegrationsAlreadyLinked
{
    private function __construct(private bool $is_repository_integration_already_linked)
    {
    }

    #[\Override]
    public function isRepositoryIntegrationAlreadyLinkedToAGroup(int $integration_id): bool
    {
        return $this->is_repository_integration_already_linked;
    }

    public static function withAlreadyLinked(): self
    {
        return new self(true);
    }

    public static function withNeverLinked(): self
    {
        return new self(false);
    }
}
