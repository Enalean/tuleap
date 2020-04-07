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

final class StreamFilter
{
    /**
     * @return FilterHandle
     */
    public static function prependFilter($resource_to_filter, FilterInterface $filter)
    {
        if (! \is_resource($resource_to_filter)) {
            throw new \InvalidArgumentException(
                'Can only prepend a filter to a resource, got ' . gettype($resource_to_filter)
            );
        }

        $chain_identifier = $filter->getFilteredChainIdentifier();
        if (
            $chain_identifier !== STREAM_FILTER_READ && $chain_identifier !== STREAM_FILTER_WRITE &&
            $chain_identifier !== STREAM_FILTER_ALL
        ) {
            throw new \DomainException(
                'Only acceptable chain identifier are STREAM_FILTER_READ (' . STREAM_FILTER_READ . '), ' .
                'STREAM_FILTER_WRITE (' . STREAM_FILTER_WRITE . ') ' .
                'and STREAM_FILTER_ALL (' .  STREAM_FILTER_ALL . '), got ' . $chain_identifier
            );
        }

        $filter_wrapper_name = self::registerFilterWrapper();
        $filter_resource     = \stream_filter_prepend(
            $resource_to_filter,
            $filter_wrapper_name,
            $chain_identifier,
            $filter
        );

        if (! \is_resource($filter_resource)) {
            throw new \RuntimeException('Not able to prepend the filter to the stream');
        }

        return new FilterHandle($filter_resource);
    }

    public static function removeFilter(FilterHandle $handle)
    {
        \stream_filter_remove($handle->getFilterResource());
    }

    /**
     * @return string
     */
    private static function registerFilterWrapper()
    {
        if (\in_array(self::class, \stream_get_filters(), true)) {
            return self::class;
        }
        $is_registration_successful = \stream_filter_register(self::class, StreamFilterWrapper::class);
        if (! $is_registration_successful) {
            throw new \RuntimeException('Not able to register the stream wrapper filter');
        }
        return self::class;
    }
}
