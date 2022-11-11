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

namespace Tuleap\Event\Events;

use Tuleap\Reference\TextWithPotentialReferences;

/**
 * A bunch of text blocks has been received. Maybe there are references to some Tuleap-managed objects
 * among the text blocks.
 * @psalm-immutable
 */
final class PotentialReferencesReceived implements \Tuleap\Event\Dispatchable
{
    public const NAME = 'receivePotentialReferences';

    /**
     * @param TextWithPotentialReferences[] $text_with_potential_references
     */
    public function __construct(
        public array $text_with_potential_references,
        public \Project $project,
    ) {
    }
}
