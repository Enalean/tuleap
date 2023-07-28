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

declare(strict_types=1);

namespace Tuleap\Reference;

class CrossReferenceCollection
{
    /**
     * @var string
     */
    private $nature;
    /**
     * @var string
     */
    private $label;
    /**
     * @var CrossReference[]
     */
    private $both;
    /**
     * @var CrossReference[]
     */
    private $target;
    /**
     * @var CrossReference[]
     */
    private $source;
    /**
     * @var string
     */
    private $nature_icon;

    /**
     * @param CrossReference[] $both
     * @param CrossReference[] $target
     * @param CrossReference[] $source
     */
    public function __construct(string $nature, string $label, array $both, array $target, array $source, string $nature_icon)
    {
        $this->nature      = $nature;
        $this->label       = $label;
        $this->both        = $both;
        $this->target      = $target;
        $this->source      = $source;
        $this->nature_icon = $nature_icon;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getNature(): string
    {
        return $this->nature;
    }

    /**
     * @return string
     */
    public function getNatureIcon()
    {
        return $this->nature_icon;
    }

    /**
     * @return CrossReference[]
     */
    public function getCrossReferencesBoth(): array
    {
        return $this->both;
    }

    /**
     * @return CrossReference[]
     */
    public function getCrossReferencesSource(): array
    {
        return $this->source;
    }

    /**
     * @return CrossReference[]
     */
    public function getCrossReferencesTarget(): array
    {
        return $this->target;
    }
}
