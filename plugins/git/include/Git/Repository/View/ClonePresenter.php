<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

use GitRepository;

class ClonePresenter
{
    /** @var string */
    private $gerrit_label = "Gerrit";
    /** @var string */
    private $ssh_label = "SSH";
    /** @var string */
    private $https_label = "HTTPS";

    /** @var CloneURLPresenter[] */
    public $clone_url_presenters = [];

    /** @var CloneURLPresenter[] */
    public $ssh_mirrors_presenters = [];

    /** @var string */
    public $selected_url;
    /** @var string */
    public $selected_url_label;

    /** @var bool */
    public $has_ssh_mirrors;

    public function __construct(CloneURLs $clone_urls, GitRepository $repository)
    {
        if ($clone_urls->hasGerritUrl()) {
            $this->selected_url           = $clone_urls->getGerritUrl();
            $this->selected_url_label     = sprintf(dgettext("tuleap-git", "%s (Default)"), $this->gerrit_label);
            $this->clone_url_presenters[] = new CloneURLPresenter(
                $this->selected_url,
                $this->selected_url_label,
                true,
                false
            );
        }
        if ($clone_urls->hasSshUrl()) {
            $ssh_is_selected              = (! $this->selected_url);
            $this->clone_url_presenters[] = $this->getSSHCloneURLPresenter($clone_urls, $ssh_is_selected);
        }
        if ($clone_urls->hasHttpsUrl()) {
            $https_is_selected            = (! $this->selected_url);
            $this->clone_url_presenters[] = $this->getHTTPSCloneURLPresenter($clone_urls, $https_is_selected);
        }
        if ($clone_urls->hasMirrorLinks()) {
            $this->ssh_mirrors_presenters = $this->getMirrorLinksCloneURLPresenter($clone_urls, $repository);
        }

        $this->has_ssh_mirrors = $clone_urls->hasMirrorLinks();
    }

    private function getSSHCloneURLPresenter(CloneURLs $clone_urls, $ssh_is_selected)
    {
        if ($ssh_is_selected) {
            $this->selected_url       = $clone_urls->getSshUrl();
            $this->selected_url_label = sprintf(dgettext("tuleap-git", "%s (Default)"), $this->ssh_label);
            return new CloneURLPresenter(
                $this->selected_url,
                $this->selected_url_label,
                true,
                $clone_urls->hasGerritUrl()
            );
        }
        return new CloneURLPresenter(
            $clone_urls->getSshUrl(),
            $this->ssh_label,
            false,
            $clone_urls->hasGerritUrl()
        );
    }

    private function getHTTPSCloneURLPresenter(CloneURLs $clone_urls, $https_is_selected)
    {
        if ($https_is_selected) {
            $this->selected_url       = $clone_urls->getHttpsUrl();
            $this->selected_url_label = sprintf(dgettext("tuleap-git", "%s (Default)"), $this->https_label);
            return new CloneURLPresenter(
                $this->selected_url,
                $this->selected_url_label,
                true,
                $clone_urls->hasGerritUrl()
            );
        }
        return new CloneURLPresenter(
            $clone_urls->getHttpsUrl(),
            $this->https_label,
            false,
            $clone_urls->hasGerritUrl()
        );
    }

    private function getMirrorLinksCloneURLPresenter(CloneURLs $clone_urls, GitRepository $repository)
    {
        $presenters = [];

        foreach ($clone_urls->getMirrorsLinks() as $mirror) {
            $presenters[] = new CloneURLPresenter(
                $repository->getSSHForMirror($mirror),
                $mirror->name,
                false,
                $clone_urls->hasMirrorLinks()
            );
        }

        return $presenters;
    }
}
