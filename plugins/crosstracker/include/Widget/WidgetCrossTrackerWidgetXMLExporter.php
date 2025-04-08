<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use SimpleXMLElement;
use Tuleap\CrossTracker\Query\CrossTrackerQueryFactory;
use Tuleap\Option\Option;

final readonly class WidgetCrossTrackerWidgetXMLExporter
{
    public const PREFERENCE_QUERY             = 'query';
    public const PREFERENCE_QUERY_IS_DEFAULT  = 'is-default';
    public const PREFERENCE_QUERY_TITLE       = 'title';
    public const PREFERENCE_QUERY_DESCRIPTION = 'description';
    public const PREFERENCE_QUERY_TQL         = 'tql';

    public function __construct(
        private CrossTrackerQueryFactory $query_cross_tracker_query_factory,
    ) {
    }

    /**
     * @return Option<\SimpleXMLElement>
     */
    public function generateXML(int $widget_id): Option
    {
        $widget = new SimpleXMLElement('<widget />');
        $widget->addAttribute('name', CrossTrackerSearchWidget::NAME);

        $queries       = $this->query_cross_tracker_query_factory->getByWidgetId($widget_id);
        $cdata_factory = new \XML_SimpleXMLCDATAFactory();
        foreach ($queries as $query) {
            $preference = $widget->addChild('preference');
            if ($preference === null) {
                return Option::nothing(SimpleXMLElement::class);
            }
            $preference->addAttribute('name', self::PREFERENCE_QUERY);

            $is_default_value_element = $preference->addChild('value', $query->isDefault() ? '1' : '0');
            if ($is_default_value_element === null) {
                return Option::nothing(SimpleXMLElement::class);
            }
            $is_default_value_element->addAttribute('name', self::PREFERENCE_QUERY_IS_DEFAULT);

            $cdata_factory->insertWithAttributes(
                $preference,
                'value',
                $query->getTitle(),
                ['name' => self::PREFERENCE_QUERY_TITLE]
            );
            $cdata_factory->insertWithAttributes(
                $preference,
                'value',
                $query->getDescription(),
                ['name' => self::PREFERENCE_QUERY_DESCRIPTION]
            );
            $cdata_factory->insertWithAttributes(
                $preference,
                'value',
                $query->getQuery(),
                ['name' => self::PREFERENCE_QUERY_TQL]
            );
        }

        return Option::fromValue($widget);
    }
}
