<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

use Tuleap\User\ProvideCurrentUser;

final readonly class CurrentUserValueWrapper implements ValueWrapper
{
    private ?string $value;

    public function __construct(ProvideCurrentUser $user_provider)
    {
        $current_user = $user_provider->getCurrentUser();
        $this->value  = $current_user->getUserName();
    }

    #[\Override]
    public function accept(ValueWrapperVisitor $visitor, $parameters)
    {
        return $visitor->visitCurrentUserValueWrapper($this, $parameters);
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
