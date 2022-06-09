<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Reference;

final class GotoLink
{
    private function __construct(
        /**
         * @psalm-readonly
         */
        private string $uri,
    ) {
    }

    public static function fromComponents(string $keyword, string $value, int $project_id): self
    {
        // If no group_id from context, the default is "100".
        // Don't use it in the link...
        $project_parameter = '';
        if ($project_id !== 100) {
            $project_parameter = sprintf('&group_id=%s', urlencode((string) $project_id));
        }
        return new self(sprintf('/goto?key=%s&val=%s%s', urlencode($keyword), urlencode($value), $project_parameter));
    }

    /**
     * @return string full link (with http://servername...) if needed.
     */
    public function getFullGotoLink(): string
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . $this->uri;
    }
}
