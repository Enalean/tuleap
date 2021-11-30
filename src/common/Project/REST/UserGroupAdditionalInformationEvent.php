<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Project\REST;

use Tuleap\Event\Dispatchable;

final class UserGroupAdditionalInformationEvent implements Dispatchable
{
    public const NAME = 'addAdditionalInformation';

    /**
     * @var UserGroupAdditionalInformation[]|null[]
     * @psalm-var array<string,UserGroupAdditionalInformation|null>
     *
     * @psalm-readonly-allow-private-mutation
     */
    public array $additional_information = [];

    public function __construct(
        /** @psalm-readonly */ public \ProjectUGroup $project_ugroup,
        /** @psalm-readonly */ public \PFUser $current_user,
    ) {
    }

    /**
     * @psalm-external-mutation-free
     */
    public function setAdditionalInformation(string $name, ?UserGroupAdditionalInformation $additional_information): void
    {
        $this->additional_information[$name] = $additional_information;
    }
}
