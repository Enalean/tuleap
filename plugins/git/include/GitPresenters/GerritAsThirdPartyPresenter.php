<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class GitPresenters_GerritAsThirdPartyPresenter
{

    public $form_action = 'add_missing_gerrit_access';

    public function third_party_access_text()
    {
        return dgettext('tuleap-git', 'In order to make sure I have the correct access on Gerrit, it can be necessary to update my group and project membership');
    }

    public function third_party_synch_button()
    {
        return dgettext('tuleap-git', 'Update');
    }

    public function third_party_synch_warning()
    {
        return dgettext('tuleap-git', 'You may need to log into Gerrit first');
    }
}
