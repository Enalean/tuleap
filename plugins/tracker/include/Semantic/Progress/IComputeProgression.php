<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\Tracker\Artifact\Artifact;

interface IComputeProgression
{
    public static function getMethodName(): string;

    public static function getMethodLabel(): string;

    public function getCurrentConfigurationDescription(): string;

    public function isFieldUsedInComputation(\Tuleap\Tracker\FormElement\Field\TrackerField $field): bool;

    public function computeProgression(Artifact $artifact, \PFUser $user): ProgressionResult;

    public function isConfigured(): bool;

    public function isConfiguredAndValid(): bool;

    public function getErrorMessage(): string;

    public function exportToREST(\PFUser $user): ?IRepresentSemanticProgress;

    public function exportToXMl(\SimpleXMLElement $root, array $xml_mapping): void;

    public function saveSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool;

    public function deleteSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool;
}
