<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

class TypeValidator
{
    public const SHORTNAME_PATTERN = '[a-zA-Z][a-zA-Z_]*';

    public function __construct(private TypeDao $dao)
    {
    }

    /** @throws InvalidTypeParameterException */
    public function checkShortname($shortname)
    {
        if (! $shortname) {
            throw new InvalidTypeParameterException(
                dgettext('tuleap-tracker', 'missing shortname')
            );
        }
        if (! preg_match('/^' . self::SHORTNAME_PATTERN . '$/', $shortname)) {
            throw new InvalidTypeParameterException(
                dgettext('tuleap-tracker', 'Only letters and underscore are allowed for shortname (must start with a letter though).')
            );
        }
    }

    /** @throws InvalidTypeParameterException */
    public function checkForwardLabel($forward_label)
    {
        if (! $forward_label) {
            throw new InvalidTypeParameterException(
                dgettext('tuleap-tracker', 'missing forward label')
            );
        }
    }

    /** @throws InvalidTypeParameterException */
    public function checkReverseLabel($reverse_label)
    {
        if (! $reverse_label) {
            throw new InvalidTypeParameterException(
                dgettext('tuleap-tracker', 'missing reverse label')
            );
        }
    }

    public function checkIsNotOrHasNotBeenUsed($shortname)
    {
        if ($this->dao->isOrHasBeenUsed($shortname)) {
            throw new UnableToDeleteTypeException(
                dgettext('tuleap-tracker', 'type can\'t be deleted because it is or has been already used.')
            );
        }
    }
}
