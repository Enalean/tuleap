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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsFeatureStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FEATURE_ID = 769;
    private VerifyIsFeatureStub $feature_verifier;
    private VerifyFeatureIsVisibleStub $visibility_verifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->feature_verifier    = VerifyIsFeatureStub::withValidFeature();
        $this->visibility_verifier = VerifyFeatureIsVisibleStub::withAlwaysVisibleFeatures();
    }

    /**
     * @throws FeatureIsNotPlannableException
     * @throws FeatureNotFoundException
     */
    private function checkIsFeature(): void
    {
        $verifier = new FeatureChecker($this->feature_verifier, $this->visibility_verifier);
        $verifier->checkIsFeature(self::FEATURE_ID, UserIdentifierStub::buildGenericUser());
    }

    public function testItAllowsValidFeature(): void
    {
        $this->checkIsFeature();
        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsIfArtifactIsNotAFeature(): void
    {
        $this->feature_verifier = VerifyIsFeatureStub::withNotFeature();
        $this->expectException(FeatureIsNotPlannableException::class);
        $this->checkIsFeature();
    }

    public function testItThrowsIfFeatureIsNotVisibleByUser(): void
    {
        $this->visibility_verifier = VerifyFeatureIsVisibleStub::withNotVisibleFeature();
        $this->expectException(FeatureNotFoundException::class);
        $this->checkIsFeature();
    }
}
