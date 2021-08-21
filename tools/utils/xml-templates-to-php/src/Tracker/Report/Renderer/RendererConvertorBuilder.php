<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tools\Xml2Php\Tracker\Report\Renderer;

use Psr\Log\LoggerInterface;

final class RendererConvertorBuilder
{
    public static function getConvertor(\SimpleXMLElement $renderer, LoggerInterface $logger): ?RendererConvertor
    {
        $type = (string) $renderer['type'];
        switch ($type) {
            case 'table':
                return new TableConvertor();
            case 'plugin_cardwall':
                $logger->info('Dependency on Cardwall detected!');
                return new CardwallConvertor();
            case 'plugin_graphontrackersv5':
                $logger->info('Dependency on GraphOnTrackers detected!');
                return new GraphOnTrackersConvertor();
            default:
                $logger->error(sprintf('%s renderer are not implemented yet', $type));
        }

        return null;
    }
}
