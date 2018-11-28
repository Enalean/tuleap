<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\StreamFilter;

final class FilterHandle
{
    /**
     * @var resource
     */
    private $filter_resource;

    public function __construct($filter_resource)
    {
        if (! \is_resource($filter_resource)) {
            throw new \InvalidArgumentException(
                'Can only create a filter handle from a resource, got ' . gettype($filter_resource)
            );
        }
        $resource_type = get_resource_type($filter_resource);
        if ($resource_type !== 'stream filter') {
            throw new \InvalidArgumentException(
                'Can only create a filter handle from a stream filter, got ' . $resource_type
            );
        }
        $this->filter_resource = $filter_resource;
    }

    /**
     * @return resource
     */
    public function getFilterResource()
    {
        return $this->filter_resource;
    }
}
