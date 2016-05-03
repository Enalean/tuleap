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

namespace Tuleap\FRS;

class AdditionalInformationPresenter
{

    public $linked_artifact_id;

    public function __construct($linked_artifact_id)
    {
        $this->linked_artifact_id = $linked_artifact_id;
    }

    public function has_a_linked_artifact()
    {
        return $this->linked_artifact_id != null;
    }

    public function artifact_id_title()
    {
        return $GLOBALS['Language']->getText('plugin_frs', 'artifact_id_title');
    }
}
