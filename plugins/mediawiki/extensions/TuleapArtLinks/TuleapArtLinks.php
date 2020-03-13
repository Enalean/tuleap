<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

if (! defined('MEDIAWIKI')) {
    die("Not an entry point.");
}

$wgExtensionCredits['validextensionclass'][] = array(
    'path'        => __FILE__,
    'name'        => 'TuleapArtLinks',
    'author'      => 'Enalean SAS',
    'url'         => HTTPRequest::instance()->getServerUrl() . '/doc/' . UserManager::instance()->getCurrentUser()->getShortLocale() . '/user-guide/documents-and-files/mediawiki.html#tuleap-specific-extension',
    'description' => 'This extension provides ArtifactLinks integration with Tuleap',
    'version'     => 0.1
);

// Load needed classes
$wgAutoloadClasses['TuleapArtLinksHooks'] = dirname(__FILE__) . '/TuleapArtLinks.hooks.php';

// Define hooks
$wgHooks['OutputPageBeforeHTML'][] = 'TuleapArtLinksHooks::onOutputPageBeforeHTML';
