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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\reference\Events;

use Tuleap\Event\Dispatchable;
use Tuleap\reference\CrossReferenceNatureIcon;

class CrossReferenceGetNatureIconEvent implements Dispatchable
{
    public const NAME = 'crossReferenceGetNatureIconEvent';

    /**
     * @var string
     */
    private $nature;
    /**
     * @var null|CrossReferenceNatureIcon
     */
    private $icons_nature = null;

    /**
     * @var string $natures
     */

    public function __construct(string $nature)
    {
        $this->nature = $nature;
    }

    public function getNature(): string
    {
        return $this->nature;
    }

    public function setCrossReferenceNatureIcon(CrossReferenceNatureIcon $icon_nature): void
    {
        $this->icons_nature = $icon_nature;
    }

    public function getCrossReferencesNatureIcon(): ?CrossReferenceNatureIcon
    {
        return $this->icons_nature;
    }
}
