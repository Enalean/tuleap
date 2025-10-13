<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact;

use Tuleap\Instrument\Prometheus\Prometheus;

final class ArtifactInstrumentation
{
    private const string METRIC_NAME = 'tracker_artifacts_total';

    public const string TYPE_CREATED = 'created';
    public const string TYPE_UPDATED = 'updated';
    public const string TYPE_VIEWED  = 'viewed';
    public const string TYPE_DELETED = 'deleted';

    /**
     * @psalm-param self::TYPE_CREATED|self::TYPE_UPDATED|self::TYPE_VIEWED|self::TYPE_DELETED $type
     */
    public static function increment(string $type): void
    {
        Prometheus::instance()->increment(self::METRIC_NAME, 'Total number of artifacts', ['type' => $type]);
    }
}
