<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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


// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class FlamingParrot_NavBarItemPresentersCollectionBuilder
{
    /** @var PFUser */
    private $user;

    /** @var string */
    private $request_uri;

    /** @var string */
    private $selected_top_tab;

    /** @var array */
    private $projects;

    public function __construct(
        PFUser $user,
        $request_uri,
        $selected_top_tab,
        array $projects
    ) {
        $this->user             = $user;
        $this->request_uri      = $request_uri;
        $this->selected_top_tab = $selected_top_tab;
        $this->projects         = $projects;
    }

    public function buildNavBarItemPresentersCollection()
    {
        $collection = new FlamingParrot_NavBarItemPresentersCollection();

        $this->addProjectsItem($collection);

        return $collection;
    }

    private function addProjectsItem(FlamingParrot_NavBarItemPresentersCollection $collection)
    {
        $collection->addItem(new FlamingParrot_NavBarItemProjectsPresenter(
            'project',
            $this->isNavBarItemActive(['/softwaremap/', '/projects/', '/project/']),
            $this->user,
            $this->projects
        ));
    }

    private function isNavBarItemActive($paths_to_detect, $toptab = null)
    {
        if ($toptab === $this->selected_top_tab) {
            return true;
        }

        if (! is_array($paths_to_detect)) {
            $paths_to_detect = [$paths_to_detect];
        }

        foreach ($paths_to_detect as $path) {
            if (strpos($this->request_uri, $path) === 0) {
                return true;
            }
        }

        return false;
    }
}
