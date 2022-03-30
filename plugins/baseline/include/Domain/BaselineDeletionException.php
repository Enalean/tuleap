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

namespace Tuleap\Baseline\Domain;

use Exception;

/** Cannot delete a baseline because associated to some comparisons */
class BaselineDeletionException extends Exception
{
    /** @var int */
    private $associated_comparisons_count;

    public function __construct(int $associated_comparisons_count)
    {
        parent::__construct(
            sprintf(
                dgettext(
                    'tuleap-baseline',
                    'The baseline is associated to %u saved comparison(s). You should delete this(these) comparison(s) before deleting the baseline'
                ),
                $associated_comparisons_count
            )
        );
        $this->associated_comparisons_count = $associated_comparisons_count;
    }

    public function getAssociatedComparisonsCount(): int
    {
        return $this->associated_comparisons_count;
    }
}
