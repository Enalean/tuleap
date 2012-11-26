<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 *
 * Portions Copyright 2010 (c) Mélanie Le Bail
 * Portions Copyright 2011 (c) France Telecom
 */

require_once 'common/plugin/PluginDescriptor.class.php';


class MediaWikiPluginDescriptor extends PluginDescriptor {

    function MediaWikiPluginDescriptor() {
        $this->PluginDescriptor(_('Mediawiki'), 'v1.0', _('Mediawiki integration in the forge'));
    }
}
