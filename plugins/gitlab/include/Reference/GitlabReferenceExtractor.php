<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Reference;

final class GitlabReferenceExtractor
{
    public static function splitRepositoryNameAndReferencedItemId(string $value): GitlabReferenceSplittedValues
    {
        $regexp = "#(?P<repository_name>.+?/.+?)/(?P<reference>.+)#";
        preg_match($regexp, $value, $matches);

        if (isset($matches['repository_name']) && isset($matches['reference'])) {
            $repository_name = $matches['repository_name'];
            $reference       = $matches['reference'];

            return GitlabReferenceSplittedValues::buildFromReference(
                $repository_name,
                $reference
            );
        }

        return GitlabReferenceSplittedValues::buildNotFoundReference();
    }
}
