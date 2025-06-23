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

namespace Tuleap\Artidoc\Stubs\Document\Tracker;

use Tuleap\Artidoc\Document\Tracker\CheckTrackerIsSuitableForDocument;
use Tuleap\Artidoc\Document\Tracker\TrackerNotFoundFault;
use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class CheckTrackerIsSuitableForDocumentStub implements CheckTrackerIsSuitableForDocument
{
    /**
     * @param null|array<int, \Tuleap\Tracker\Tracker> $trackers
     */
    private function __construct(private ?array $trackers)
    {
    }

    public static function withSuitableTrackers(\Tuleap\Tracker\Tracker $tracker, \Tuleap\Tracker\Tracker ...$other_trackers): self
    {
        return new self(
            array_reduce(
                [$tracker, ...$other_trackers],
                static function (array $trackers, \Tuleap\Tracker\Tracker $tracker): array {
                    $trackers[$tracker->getId()] = $tracker;

                    return $trackers;
                },
                [],
            )
        );
    }

    public static function withoutSuitableTracker(): self
    {
        return new self([]);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    public function checkTrackerIsSuitableForDocument(\Tuleap\Tracker\Tracker $tracker, Artidoc $document, \PFUser $user): Ok|Err
    {
        if ($this->trackers === null) {
            throw new \Exception('Unexpected call to checkTrackerIsSuitableForDocument');
        }

        if (isset($this->trackers[$tracker->getId()])) {
            return Result::ok($tracker);
        }

        return Result::err(TrackerNotFoundFault::forDocument($document));
    }
}
