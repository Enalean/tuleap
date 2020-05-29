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
 *
 */

namespace Tuleap\Reference;

use Tuleap\Event\Dispatchable;
use Tuleap\Layout\TooltipJSON;

class ReferenceGetTooltipRepresentationEvent implements Dispatchable
{
    public const NAME = 'referenceGetTooltipRepresentationEvent';

    /**
     * @var \Reference
     */
    private $reference;
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var string
     */
    private $value;
    /**
     * @var TooltipJSON|null
     */
    private $output;
    /**
     * @var \PFUser
     */
    private $user;

    public function __construct(\Reference $reference, \Project $project, \PFUser $user, string $keyword, string $value)
    {
        $this->reference = $reference;
        $this->project   = $project;
        $this->user      = $user;
        $this->keyword   = $keyword;
        $this->value     = $value;
    }

    public function getReference(): \Reference
    {
        return $this->reference;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }

    public function setOutput(TooltipJSON $output): void
    {
        $this->output = $output;
    }

    public function getOutput(): ?TooltipJSON
    {
        return $this->output;
    }
}
