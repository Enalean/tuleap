<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions;

final class IUpdatePermissionsFollowingSiteAccessChangeStub implements IUpdatePermissionsFollowingSiteAccessChange
{
    private bool $has_conversion_from_anon_to_registered_be_called          = false;
    private bool $has_conversion_from_authenticated_to_registered_be_called = false;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    #[\Override]
    public function updateAllAnonymousAccessToRegistered(): void
    {
        $this->has_conversion_from_anon_to_registered_be_called = true;
    }

    #[\Override]
    public function updateAllAuthenticatedAccessToRegistered(): void
    {
        $this->has_conversion_from_authenticated_to_registered_be_called = true;
    }

    public function hasConversionFromAnonToRegisteredBeCalled(): bool
    {
        return $this->has_conversion_from_anon_to_registered_be_called;
    }

    public function hasConversionFromAuthenticatedToRegisteredBeCalled(): bool
    {
        return $this->has_conversion_from_authenticated_to_registered_be_called;
    }
}
