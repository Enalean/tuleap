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

class InvalidMethod implements IComputeProgression
{
    private const METHOD_NAME = 'invalid';

    /**
     * @var string
     */
    private $error_message;

    public function __construct(string $error_message)
    {
        $this->error_message = $error_message;
    }

    public static function getMethodName(): string
    {
        return self::METHOD_NAME;
    }

    public static function getMethodLabel(): string
    {
        return '';
    }

    public function getCurrentConfigurationDescription(): string
    {
        return $this->getErrorMessage();
    }

    public function isFieldUsedInComputation(\Tracker_FormElement_Field $field): bool
    {
        return false;
    }

    public function computeProgression(Artifact $artifact, \PFUser $user): ProgressionResult
    {
        return new ProgressionResult(null, $this->error_message);
    }

    public function isConfiguredAndValid(): bool
    {
        return false;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getErrorMessage(): string
    {
        return $this->error_message;
    }

    public function exportToREST(\PFUser $user): ?IRepresentSemanticProgress
    {
        return null;
    }

    public function exportToXMl(\SimpleXMLElement $root, array $xml_mapping): void
    {
    }

    public function saveSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool
    {
        return false;
    }

    public function deleteSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool
    {
        return false;
    }
}
