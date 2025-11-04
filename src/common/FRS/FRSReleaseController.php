<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

require_once __DIR__ . '/../../www/file/file_utils.php';

use FRSPackage;
use FRSRelease;
use FRSReleaseFactory;
use HTTPRequest;
use Project;

readonly class FRSReleaseController
{
    public function __construct(
        private FRSReleaseFactory $release_factory,
        private \Codendi_HTMLPurifier $purifier,
    ) {
    }

    public function delete(Project $project, FRSRelease $release): never
    {
        $package_link = '/file/' . urlencode((string) $project->getID()) . '/package/' . urlencode((string) $release->getPackageID());
        (new \CSRFSynchronizerToken($package_link))->check();

        if (! $this->release_factory->delete_release($project->getGroupId(), $release->getReleaseID())) {
            throw new FRSDeleteReleaseNotYoursException();
        }

        $GLOBALS['Response']->addFeedback(\Feedback::SUCCESS, $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_del'));
        $GLOBALS['Response']->redirect($package_link);
        exit;
    }

    public function add(Project $project, $package_id)
    {
        $release = new FRSRelease();
        $release->setPackageID($package_id);
        $release->setStatusID($this->release_factory->STATUS_ACTIVE);
        $release->setReleaseDate(time());

        $title    = $release->getPackage()->getName();
        $subtitle = _('Create a new release');
        $url      = '?func=create&amp;postExpected=&amp;group_id=' . $this->purifier->purify(urlencode($project->getGroupId())) . '&amp;package_id=' . $this->purifier->purify(urlencode($package_id));
        frs_display_release_form($is_update = false, $release, $project->getGroupId(), $title, $subtitle, $url);
    }

    public function create(HTTPRequest $request, Project $project, FRSPackage $package)
    {
        if ($request->exist('cancel')) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'create_canceled'));
            $GLOBALS['Response']->redirect('/file/?group_id=' . urlencode($project->getGroupId()));
        }

        frs_process_release_form(
            $is_update = false,
            $request,
            $project->getGroupId(),
            $GLOBALS['Language']->getText('file_admin_editreleases', 'release_new_file_version'),
            '?func=create&amp;postExpected=&amp;group_id=' . $this->purifier->purify(urlencode($project->getGroupId())) . '&amp;package_id=' . $this->purifier->purify(urlencode($package->getPackageID()))
        );
    }

    public function displayForm(Project $project, FRSRelease $release)
    {
        frs_display_release_form(
            $is_update = true,
            $release,
            $project->getGroupId(),
            $release->getName(),
            $GLOBALS['Language']->getText('file_admin_editreleases', 'edit_release'),
            '?func=update&amp;postExpected=&amp;group_id=' . $this->purifier->purify(urlencode($project->getGroupId())) . '&amp;package_id=' . $this->purifier->purify(urlencode($release->getPackageID())) . '&amp;id=' . $this->purifier->purify(urlencode((string) $release->getReleaseID()))
        );
    }

    public function update(HTTPRequest $request, Project $project, FRSRelease $release)
    {
        frs_process_release_form(
            $is_update = true,
            $request,
            $project->getGroupId(),
            $GLOBALS['Language']->getText('file_admin_editreleases', 'edit_release'),
            '?func=update&amp;postExpected=&amp;group_id=' . $this->purifier->purify(urlencode($project->getGroupId())) . '&amp;package_id=' . $this->purifier->purify(urlencode($release->getPackageID())) . '&amp;id=' . $this->purifier->purify(urlencode((string) $release->getReleaseID()))
        );
    }
}
