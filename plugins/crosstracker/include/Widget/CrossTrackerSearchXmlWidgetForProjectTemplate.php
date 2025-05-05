<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

use Tuleap\Widget\XML\XMLPreference;
use Tuleap\Widget\XML\XMLPreferenceValue;
use Tuleap\Widget\XML\XMLWidget;

/**
 * @psalm-immutable
 */
final readonly class CrossTrackerSearchXmlWidgetForProjectTemplate
{
    public static function build(): XMLWidget
    {
        return (new XMLWidget(CrossTrackerSearchWidget::NAME))
            ->withPreference(
                self::getQueryAsXml(
                    'All open artifacts',
                    <<<EOS
                    SELECT @pretty_title, @status, @last_update_date, @submitted_by
                    FROM @project = 'self'
                    WHERE @status = OPEN()
                    ORDER BY @last_update_date DESC
                    EOS,
                    true,
                )
            )
            ->withPreference(
                self::getQueryAsXml(
                    'Open artifacts assigned to me',
                    <<<EOS
                    SELECT @pretty_title, @status, @last_update_date, @submitted_by
                    FROM @project = 'self'
                    WHERE @status = OPEN() AND @assigned_to = MYSELF()
                    ORDER BY @last_update_date DESC
                    EOS,
                    false,
                )
            );
    }

    private static function getQueryAsXml(string $title, string $tql, bool $is_default): XMLPreference
    {
        return (new XMLPreference(WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY))
            ->withValue(
                XMLPreferenceValue::text(
                    WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY_IS_DEFAULT,
                    $is_default ? '1' : '0',
                )
            )
            ->withValue(
                XMLPreferenceValue::text(WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY_TITLE, $title)
            )
            ->withValue(
                XMLPreferenceValue::text(WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY_DESCRIPTION, '')
            )
            ->withValue(
                XMLPreferenceValue::text(WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY_TQL, $tql)
            );
    }
}
