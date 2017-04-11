<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Bugzilla\Administration;

use Codendi_HTMLPurifier;

class Presenter
{
    public $bugzilla_title;
    public $references_configuration;
    public $under_construction;
    public $purified_no_bugzilla_reference;

    public function __construct()
    {
        $this->bugzilla_title                 = dgettext('tuleap-bugzilla_reference', 'Bugzilla configuration');
        $this->bugzilla_add                   = dgettext('tuleap-bugzilla_reference', 'Add reference');
        $this->purified_no_bugzilla_reference = Codendi_HTMLPurifier::instance()->purify(
            dgettext(
                'tuleap-bugzilla_reference',
                'There is nothing here, <br> start by adding a Bugzilla reference'
            ),
            CODENDI_PURIFIER_LIGHT
        );
    }
}
