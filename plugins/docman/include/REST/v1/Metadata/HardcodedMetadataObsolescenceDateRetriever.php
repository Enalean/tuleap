<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Metadata;

use Tuleap\Docman\REST\v1\ItemRepresentation;

class HardcodedMetadataObsolescenceDateRetriever
{

    /**
     * @var HardcodedMetdataObsolescenceDateChecker
     */
    private $date_checker;

    public function __construct(HardcodedMetdataObsolescenceDateChecker $date_checker)
    {
        $this->date_checker = $date_checker;
    }

    /**
     * @throws HardCodedMetadataException
     */
    public function getTimeStampOfDate(?string $date): int
    {
        $this->date_checker->checkObsolescenceDateUsageForDocument(
            $date
        );

        if ($date === null) {
            return (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE;
        }

        $formatted_date = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (!$formatted_date) {
            throw HardCodedMetadataException::invalidDateFormat();
        }

        return $formatted_date->getTimestamp();
    }

    /**
     * @throws HardCodedMetadataException
     */
    public function getTimeStampOfDateWithoutPeriodValidity(?string $date, \DateTimeImmutable $current_time): int
    {
        if (!$this->date_checker->isObsolescenceMetadataUsed()) {
            return (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE;
        }

        $formatted_date_timestamp = $this->getTimeStampOfDate($date);

        $this->date_checker->checkDateValidity(
            $current_time->getTimestamp(),
            $formatted_date_timestamp
        );

        return $formatted_date_timestamp;
    }
}
