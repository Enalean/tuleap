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
 */

namespace Tuleap\Tracker\FormElement\Event;

use Tracker_FormElement_Field;
use Tuleap\Event\Dispatchable;

class MessageFetcherAdditionalWarnings implements Dispatchable
{
    public const NAME = 'getMessageFetcherAdditionalWarnings';

    /**
     * @var Tracker_FormElement_Field
     */
    private $field;

    /**
     * @var array
     */
    private $warnings = [];

    public function __construct(Tracker_FormElement_Field $field)
    {
        $this->field = $field;
    }

    /**
     * @return Tracker_FormElement_Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @param array $warnings
     */
    public function setWarnings(array $warnings)
    {
        $this->warnings = $warnings;
    }
}
