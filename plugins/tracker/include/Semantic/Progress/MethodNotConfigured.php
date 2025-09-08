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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\Tracker\Artifact\Artifact;

class MethodNotConfigured implements IComputeProgression
{
    private const METHOD_NAME = 'not-configured';

    #[\Override]
    public static function getMethodName(): string
    {
        return self::METHOD_NAME;
    }

    #[\Override]
    public static function getMethodLabel(): string
    {
        return '';
    }

    #[\Override]
    public function getCurrentConfigurationDescription(): string
    {
        return dgettext('tuleap-tracker', 'This semantic is not defined yet.');
    }

    #[\Override]
    public function isFieldUsedInComputation(\Tuleap\Tracker\FormElement\Field\TrackerField $field): bool
    {
        return false;
    }

    #[\Override]
    public function computeProgression(Artifact $artifact, \PFUser $user): ProgressionResult
    {
        return new ProgressionResult(null, '');
    }

    #[\Override]
    public function isConfiguredAndValid(): bool
    {
        return false;
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return false;
    }

    #[\Override]
    public function getErrorMessage(): string
    {
        return '';
    }

    #[\Override]
    public function exportToREST(\PFUser $user): ?IRepresentSemanticProgress
    {
        return null;
    }

    #[\Override]
    public function exportToXMl(\SimpleXMLElement $root, array $xml_mapping): void
    {
    }

    #[\Override]
    public function saveSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool
    {
        return false;
    }

    #[\Override]
    public function deleteSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool
    {
        return false;
    }
}
