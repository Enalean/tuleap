<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class GitPresenters_AdminMassUdpdateMirroringPresenter
{

    /**
     * @var GitPresenters_MirrorPresenter[]
     */
    public $mirror_presenters;

    public function __construct(array $mirror_presenters)
    {
        $this->mirror_presenters = $mirror_presenters;
    }

    public function has_more_than_one_mirror()
    {
        return count($this->mirror_presenters) > 1;
    }

    public function percent_width()
    {
        $remaining_percent = 80;

        return $remaining_percent / count($this->mirror_presenters);
    }

    public function mirroring_title()
    {
        return dgettext('tuleap-git', 'Mirroring');
    }

    public function mirroring_mirror_name()
    {
        return dgettext('tuleap-git', 'Name');
    }

    public function mirroring_mirror_url()
    {
        return dgettext('tuleap-git', 'URL');
    }
}
