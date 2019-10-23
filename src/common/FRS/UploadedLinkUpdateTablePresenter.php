<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\FRS;

class UploadedLinkUpdateTablePresenter
{
    public $link_label;
    public $name_label;
    public $uploaded_links_label;
    public $add_link_label;
    public $delete_label;

    public $has_existing_links;
    /**
     * @var UploadedLinkPresenter[]
     */
    public $existing_links;

    /**
     * @param UploadedLinkPresenter[] $existing_links
     */
    public function __construct(array $existing_links)
    {
        $this->uploaded_links_label = _('Links');
        $this->link_label           = _('Link');
        $this->name_label           = _('Name');
        $this->add_link_label       = _('Add link');
        $this->delete_label         = _('Delete');

        $this->existing_links     = $existing_links;
        $this->has_existing_links = count($existing_links) > 0;
    }
}
