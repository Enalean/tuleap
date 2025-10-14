<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Reference\Metadata\CreatedByPresenter;

/**
 * @psalm-immutable
 */
final class CreationMetadataPresenter
{
    public const null NO_CREATED_BY_PRESENTER = null;

    /**
     * @var ?CreatedByPresenter
     */
    public $created_by;
    /**
     * @var TlpRelativeDatePresenter
     */
    public $created_on;

    public function __construct(?CreatedByPresenter $created_by, TlpRelativeDatePresenter $created_on)
    {
        $this->created_by = $created_by;
        $this->created_on = $created_on;
    }
}
