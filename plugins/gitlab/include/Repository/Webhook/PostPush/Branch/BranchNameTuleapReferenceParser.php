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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush\Branch;

use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;

class BranchNameTuleapReferenceParser
{
    private const string PATTERN = '/tuleap-(\d+)/i';

    public function extractTuleapReferenceFromBranchName(string $branch_name): ?WebhookTuleapReference
    {
        $matches = [];
        if (preg_match(self::PATTERN, $branch_name, $matches)) {
            $id = (int) $matches[1];
            return new WebhookTuleapReference($id, null);
        }

        return null;
    }
}
