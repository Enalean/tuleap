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

use Exception;

class TypeDeletor
{
    /** @var TypeDao */
    private $dao;

    /** @var TypeValidator */
    private $validator;

    public function __construct(TypeDao $dao, TypeValidator $validator)
    {
        $this->dao       = $dao;
        $this->validator = $validator;
    }

    /**
     * @throws UnableToDeleteTypeException
     */
    public function delete($shortname)
    {
        $this->validator->checkIsNotOrHasNotBeenUsed($shortname);

        try {
            $this->dao->delete($shortname);
        } catch (Exception $exception) {
            throw new UnableToDeleteTypeException(dgettext('tuleap-tracker', 'error while playing with the database.  Help us improve your experience by sending an error report.'));
        }
    }
}
