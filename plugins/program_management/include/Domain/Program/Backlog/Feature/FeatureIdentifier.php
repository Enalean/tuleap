<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\SearchFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Feature\CheckIsValidFeature;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold the identifier of a Feature. Features are always in a Program.
 * Features are chosen by the Program Administrator and can be planned in
 * Program Increments.
 * @psalm-immutable
 */
final class FeatureIdentifier implements ArtifactIdentifier
{
    public int $id;

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function fromIdAndProgram(
        VerifyFeatureIsVisibleByProgram $feature_verifier,
        int $feature_id,
        UserIdentifier $user_identifier,
        ProgramIdentifier $program,
        ?PermissionBypass $bypass,
    ): ?self {
        if (! $feature_verifier->isFeatureVisibleAndInProgram($feature_id, $user_identifier, $program, $bypass)) {
            return null;
        }
        return new self($feature_id);
    }

    /**
     * @throws FeatureNotFoundException
     * @throws FeatureIsNotPlannableException
     */
    public static function fromId(
        CheckIsValidFeature $feature_checker,
        int $feature_id,
        UserIdentifier $user_identifier,
    ): self {
        $feature_checker->checkIsFeature($feature_id, $user_identifier);
        return new self($feature_id);
    }

    /**
     * @return self[]
     */
    public static function buildCollectionFromProgramIncrement(
        SearchFeatures $features_searcher,
        VerifyFeatureIsVisible $feature_verifier,
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user,
    ): array {
        $features = [];
        foreach ($features_searcher->searchFeatures($program_increment) as $feature_id) {
            if ($feature_verifier->isVisibleFeature($feature_id, $user)) {
                $features[] = new self($feature_id);
            }
        }
        return $features;
    }

    /**
     * @return self[]
     */
    public static function buildCollectionFromProgram(
        SearchPlannableFeatures $features_searcher,
        VerifyFeatureIsVisible $feature_verifier,
        ProgramIdentifier $program,
        UserIdentifier $user,
    ): array {
        $features = [];
        foreach ($features_searcher->searchPlannableFeatures($program) as $feature_id) {
            if ($feature_verifier->isVisibleFeature($feature_id, $user)) {
                $features[] = new self($feature_id);
            }
        }
        return $features;
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }
}
