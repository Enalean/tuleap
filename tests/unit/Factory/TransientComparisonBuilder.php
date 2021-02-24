<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Factory;

use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\TransientComparison;

class TransientComparisonBuilder
{
    /** @var string */
    private $name;

    /** @var string|null */
    private $comment;

    /** @var Baseline */
    private $base_baseline;

    /** @var Baseline */
    private $compared_to_baseline;

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function comment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function base(Baseline $base_baseline): self
    {
        $this->base_baseline = $base_baseline;
        return $this;
    }

    public function comparedTo(Baseline $compared_to_baseline): self
    {
        $this->compared_to_baseline = $compared_to_baseline;
        return $this;
    }

    public function build(): TransientComparison
    {
        return new TransientComparison(
            $this->name,
            $this->comment,
            $this->base_baseline,
            $this->compared_to_baseline
        );
    }
}
