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

namespace Tuleap\Gitlab\REST\v1;

/**
 * @psalm-immutable
 */
class GitlabBranchPOSTRepresentation
{
    public int $gitlab_integration_id;

    public int $artifact_id;

    public string $reference;

    public static function build(
        int $gitlab_integration_id,
        int $artifact_id,
        string $reference,
    ): self {
        $representation = new self();

        $representation->gitlab_integration_id = $gitlab_integration_id;
        $representation->artifact_id           = $artifact_id;
        $representation->reference             = $reference;

        return $representation;
    }
}
