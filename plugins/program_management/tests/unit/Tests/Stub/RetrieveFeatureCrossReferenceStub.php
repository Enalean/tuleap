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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureCrossReference;

final class RetrieveFeatureCrossReferenceStub implements RetrieveFeatureCrossReference
{
    private function __construct(private array $tracker_short_names)
    {
    }

    public static function withShortname(string $tracker_short_name): self
    {
        return new self([$tracker_short_name]);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveShortNames(string $tracker_short_name, string ...$other_short_names): self
    {
        return new self([$tracker_short_name, ...$other_short_names]);
    }

    #[\Override]
    public function getFeatureCrossReference(FeatureIdentifier $feature_identifier): string
    {
        if (count($this->tracker_short_names) > 0) {
            $short_name = array_shift($this->tracker_short_names);
            return $short_name . ' #' . $feature_identifier->getId();
        }
        throw new \LogicException('No tracker short name configured');
    }
}
