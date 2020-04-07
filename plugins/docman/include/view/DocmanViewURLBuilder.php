<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\View;

use Codendi_HTMLPurifier;
use Docman_Item;

final class DocmanViewURLBuilder
{
    public static function buildUrl(string $prefix, array $parameters, bool $convert_parameters_html_entities = true): string
    {
        $url = '';
        if ($prefix) {
            $url = $prefix;
        }
        if (count($parameters)) {
            $query_parameters = '';
            if ($url !== '' && substr($url, -1) !== '?') {
                $query_parameters = '&';
            }
            $query_parameters .= http_build_query($parameters);
            if ($convert_parameters_html_entities) {
                $url .= Codendi_HTMLPurifier::instance()->purify($query_parameters);
            } else {
                $url .= $query_parameters;
            }
        }
        return $url;
    }

    private static function buildPopupUrl(string $url, bool $injs = false): string
    {
        if ($injs) {
            return "javascript:help_window(\\'$url\\')";
        }

        return "javascript:help_window('$url')";
    }

    /**
     * @psalm-param array{default_url?: string, pv?: mixed, report?: mixed} $params
     */
    public static function buildActionUrl(
        Docman_Item $item,
        array $params,
        array $url_parameters,
        bool $injs = false,
        bool $popup = false
    ): string {
        $item_specific_action_url = $item->accept(new ItemActionURLVisitor(), $url_parameters);
        if ($item_specific_action_url !== null) {
            return (string) $item_specific_action_url;
        }

        $prefix = '';
        if (isset($params['default_url']) && (bool) $params['default_url'] !== false) {
            $prefix = $params['default_url'];
        }
        if ($popup && isset($params['pv']) && $params['pv'] !== false) {
            $url = self::buildUrl($prefix, $url_parameters, !$injs);
            return self::buildPopupUrl($url, $injs);
        }
        if (isset($params['pv']) && $params['pv'] !== false) {
            $url_parameters['pv'] = $params['pv'];
        }
        if (isset($params['report']) && $params['report'] !== false) {
            $url_parameters['report'] = $params['report'];
        }
        return self::buildUrl($prefix, $url_parameters, !$injs);
    }
}
