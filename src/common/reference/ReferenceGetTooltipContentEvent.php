<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class ReferenceGetTooltipContentEvent implements Dispatchable
{
    public const NAME = 'referenceGetTooltipContentEvent';

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
     * @var string
     */
    private $output;
    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @param string $keyword
     * @param string $value
     */
    public function __construct(\Reference $reference, \Project $project, \PFUser $user, $keyword, $value)
    {
        $this->reference = $reference;
        $this->project   = $project;
        $this->user      = $user;
        $this->keyword   = $keyword;
        $this->value     = $value;
    }

    /**
     * @return \Reference
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return \Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return \PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function getOutput()
    {
        return $this->output;
    }
}
