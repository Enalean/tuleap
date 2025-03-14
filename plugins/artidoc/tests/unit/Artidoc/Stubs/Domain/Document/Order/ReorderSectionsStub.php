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

namespace Tuleap\Artidoc\Stubs\Domain\Document\Order;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Order\ReorderSections;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class ReorderSectionsStub implements ReorderSections
{
    private bool $called      = false;
    private int $called_count = 0;

    private function __construct(private readonly Ok|Err|null $result)
    {
    }

    public static function withSuccessfulReorder(): self
    {
        return new self(Result::ok(null));
    }

    public static function withFailedReorder(): self
    {
        return new self(Result::err(Fault::fromMessage('An error occurred')));
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    public function reorder(ArtidocWithContext $artidoc, SectionOrder $order): Ok|Err
    {
        $this->called = true;
        $this->called_count++;

        if ($this->result === null) {
            throw new \Exception('Unexpected call to reorder');
        }

        return $this->result;
    }

    public function isCalled(): bool
    {
        return $this->called;
    }

    public function getCallCount(): int
    {
        return $this->called_count;
    }
}
