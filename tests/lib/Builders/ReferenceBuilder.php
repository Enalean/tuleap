<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Test\Builders;

final class ReferenceBuilder
{
    private const ART_REFERENCE_ID      = 1;
    private const ARTIFACT_REFERENCE_ID = 2;
    private const ART_KEYWORD           = 'art';
    private const ARTIFACT_KEYWORD      = 'artifact';
    private const SYSTEM_SCOPE          = 'S';
    private const PROJECT_SCOPE         = 'P';

    private function __construct(
        private int $reference_id,
        private string $keyword,
        private string $scope,
        private int $project_id = 170,
    ) {
    }

    public static function anArtReference(): self
    {
        return new self(self::ART_REFERENCE_ID, self::ART_KEYWORD, self::SYSTEM_SCOPE);
    }

    public static function anArtifactReference(): self
    {
        return new self(self::ARTIFACT_REFERENCE_ID, self::ARTIFACT_KEYWORD, self::SYSTEM_SCOPE);
    }

    public static function aTrackerShortnameReference(string $tracker_shortname): self
    {
        return new self(126, $tracker_shortname, self::PROJECT_SCOPE);
    }

    public function inProject(int $project_id): self
    {
        $this->project_id = $project_id;
        return $this;
    }

    public function build(): \Reference
    {
        return new \Reference(
            $this->reference_id,
            $this->keyword,
            'Tracker Artifact',
            '/plugins/tracker?&aid=$1&group_id=$group_id',
            $this->scope,
            'plugin_tracker',
            'plugin_tracker_artifact',
            1,
            $this->project_id
        );
    }
}
