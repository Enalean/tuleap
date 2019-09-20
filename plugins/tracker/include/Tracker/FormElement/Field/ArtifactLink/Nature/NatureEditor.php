<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

class NatureEditor
{

    /** @var NatureDao */
    private $dao;

    /** @var NatureValidator */
    private $validator;

    public function __construct(NatureDao $dao, NatureValidator $validator)
    {
        $this->dao       = $dao;
        $this->validator = $validator;
    }

    /**
     * @throws InvalidNatureParameterException
     * @throws UnableToEditNatureException
     */
    public function edit($shortname, $forward_label, $reverse_label)
    {
        $this->validator->checkForwardLabel($forward_label);
        $this->validator->checkReverseLabel($reverse_label);

        if (! $this->dao->edit($shortname, $forward_label, $reverse_label)) {
            throw new UnableToEditNatureException(
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'db_error')
            );
        }
    }
}
