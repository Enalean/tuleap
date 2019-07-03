<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Codendi_Request;
use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Numeric;
use Tracker_Semantic;
use Tracker_SemanticManager;
use TrackerManager;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;

class SemanticTimeframe extends Tracker_Semantic
{
    private const NAME = 'timeframe';

    private const HARD_CODED_START_DATE_FIELD_NAME = ChartConfigurationFieldRetriever::START_DATE_FIELD_NAME;
    private const HARD_CODED_DURATION_FIELD_NAME   = ChartConfigurationFieldRetriever::DURATION_FIELD_NAME;

    /**
     * @var Tracker_FormElement_Field_Date|null
     */
    private $start_date_field;
    /**
     * @var Tracker_FormElement_Field_Numeric|null
     */
    private $duration_field;

    public function __construct(
        Tracker $tracker,
        ?Tracker_FormElement_Field_Date $start_date_field,
        ?Tracker_FormElement_Field_Numeric $duration_field
    ) {
        parent::__construct($tracker);
        $this->start_date_field = $start_date_field;
        $this->duration_field   = $duration_field;
    }

    public function getShortName(): string
    {
        return self::NAME;
    }

    public function getLabel(): string
    {
        throw new \RuntimeException('Not implemented yet');
    }

    public function getDescription(): string
    {
        throw new \RuntimeException('Not implemented yet');
    }

    public function display(): void
    {
        throw new \RuntimeException('Not implemented yet');
    }

    public function displayAdmin(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ): void {
        throw new \RuntimeException('Not implemented yet');
    }

    public function process(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ): void {
        throw new \RuntimeException('Not implemented yet');
    }

    public function exportToXml(SimpleXMLElement $root, $xmlMapping): void
    {
        throw new \RuntimeException('Not implemented yet');
    }

    public function isUsedInSemantics($field): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }

    public function save(): bool
    {
        throw new \RuntimeException('Not implemented yet');
    }

    public function getStartDateFieldName(): string
    {
        if ($this->start_date_field === null) {
            return self::HARD_CODED_START_DATE_FIELD_NAME;
        }

        return $this->start_date_field->getName();
    }

    public function getDurationFieldName(): string
    {
        if ($this->duration_field === null) {
            return self::HARD_CODED_DURATION_FIELD_NAME;
        }

        return $this->duration_field->getName();
    }
}
