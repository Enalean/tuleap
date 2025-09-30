<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\Float\FloatField;

/**
 * Manage values in changeset for float fields
 */
class Tracker_Artifact_ChangesetValue_Float extends Tracker_Artifact_ChangesetValue_Numeric // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    /**
     * @return mixed
     */
    #[\Override]
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitFloat($this);
    }

    /**
     * Get the float value
     *
     * @return float the float value
     */
    public function getFloat()
    {
        if ($this->numeric !== null) {
            $this->numeric = (float) $this->numeric;
        }
        return $this->numeric;
    }

    /**
     * Get the float value
     *
     * @return float the float value
     */
    #[\Override]
    public function getNumeric()
    {
        return $this->getFloat();
    }

    /**
     * Get the string value for this float
     *
     * @return string|null The value of this artifact changeset value
     */
    #[\Override]
    public function getValue()
    {
        if ($this->getFloat() !== null) {
            return (string) (float) number_format($this->getFloat(), FloatField::FLOAT_DECIMALS, '.', '');
        }
        return null;
    }

    #[\Override]
    public function getRESTValue(PFUser $user)
    {
        return $this->getFullRESTValue($user);
    }

    #[\Override]
    public function getFullRESTValue(PFUser $user)
    {
        return $this->getFullRESTRepresentation($this->getFloat());
    }

    /**
     * Get the Json value
     *
     * @return string The value of this artifact changeset value in Json format
     */
    #[\Override]
    public function getJsonValue()
    {
        return $this->getFloat();
    }
}
