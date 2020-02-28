<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

/**
 * ArchiveDeletedItemsPluginDescriptor
 */
class ArchiveDeletedItemsPluginDescriptor extends PluginDescriptor
{

    /**
     * Constructor
     *
     * @return Void
     */
    public function __construct()
    {
        parent::__construct(dgettext('tuleap-archivedeleteditems', 'Archive deleted items'), false, dgettext('tuleap-archivedeleteditems', 'This plugin will move files that should be purged (permanently deleted) in a dedicated filesystem for an external archiving (archiving process itself is not managed by this plugin).'));
    }
}
