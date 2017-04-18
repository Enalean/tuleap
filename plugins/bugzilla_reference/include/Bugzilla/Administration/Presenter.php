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
    public $keyword;
    public $bz_reference;
    public $server;
    public $username;
    public $password;
    public $cancel;
    public $private_public_comment;
    public $has_references;
    public $follow_up;
    public $edit;
    public $delete;

    /**
     * @var array
     */
    public $presenters;

    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(array $presenters, \CSRFSynchronizerToken $csrf_token)
    {
        $this->presenters     = $presenters;
        $this->has_references = count($presenters) > 0;
        $this->csrf_token     = $csrf_token;

        $this->bugzilla_title                 = dgettext('tuleap-bugzilla_reference', 'Bugzilla configuration');
        $this->bugzilla_add                   = dgettext('tuleap-bugzilla_reference', 'Add reference');
        $this->keyword                        = dgettext('tuleap-bugzilla_reference', 'Keyword');
        $this->server                         = dgettext('tuleap-bugzilla_reference', 'Server');
        $this->username                       = dgettext('tuleap-bugzilla_reference', 'Username');
        $this->password                       = dgettext('tuleap-bugzilla_reference', 'Password');
        $this->follow_up                      = dgettext('tuleap-bugzilla_reference', 'Follow Up');
        $this->cancel                         = dgettext('tuleap-bugzilla_reference', 'Cancel');
        $this->delete                         = dgettext('tuleap-bugzilla_reference', 'Delete');
        $this->edit                           = dgettext('tuleap-bugzilla_reference', 'Edit');
        $this->private_public_comment         = dgettext(
            'bugzilla_reference',
            'Comments added in Bugzilla will be flaged as private'
        );
        $this->purified_no_bugzilla_reference = Codendi_HTMLPurifier::instance()->purify(
            dgettext(
                'tuleap-bugzilla_reference',
                'There is nothing here, <br> start by adding a Bugzilla reference'
            ),
            CODENDI_PURIFIER_LIGHT
        );
    }
}
