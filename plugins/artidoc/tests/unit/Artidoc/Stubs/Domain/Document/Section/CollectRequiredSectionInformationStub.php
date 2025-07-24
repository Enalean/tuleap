<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Stubs\Domain\Document\Section;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\CollectRequiredSectionInformation;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class CollectRequiredSectionInformationStub implements CollectRequiredSectionInformation
{
    private bool $called = false;

    /**
     * @param null|array<int, Ok|Err> $results
     */
    private function __construct(private ?array $results)
    {
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    public static function withRequiredInformationFor(int $artifact_id, int ...$other_artifact_ids): self
    {
        return new self(
            self::mapResult(
                [$artifact_id, ...$other_artifact_ids],
                Result::ok(null),
            )
        );
    }

    public function andMissingRequiredInformationFor(int $artifact_id, int ...$other_artifact_ids): self
    {
        if ($this->results === null) {
            throw new \LogicException('Cannot add information if not expected to be called');
        }

        $this->results = $this->results +
            self::mapResult(
                [$artifact_id, ...$other_artifact_ids],
                Result::err(Fault::fromMessage('Required information are missing')),
            );

        return $this;
    }

    /**
     * @return array<int, Ok|Err>
     */
    private static function mapResult(array $artifact_ids, Ok|Err $result): array
    {
        return array_fill_keys(
            $artifact_ids,
            $result,
        );
    }

    public static function withoutRequiredInformation(int $artifact_id, int ...$other_artifact_ids): self
    {
        return (new self([]))
            ->andMissingRequiredInformationFor($artifact_id, ...$other_artifact_ids);
    }

    #[\Override]
    public function collectRequiredSectionInformation(ArtidocWithContext $artidoc, int $artifact_id): Ok|Err
    {
        $this->called = true;

        if ($this->results === null) {
            throw new \Exception('Unexpected call to method  ' . __METHOD__);
        }

        if (! isset($this->results[$artifact_id])) {
            throw new \Exception('Artifact ' . $artifact_id . ' not found');
        }

        return $this->results[$artifact_id];
    }

    public function isCalled(): bool
    {
        return $this->called;
    }
}
