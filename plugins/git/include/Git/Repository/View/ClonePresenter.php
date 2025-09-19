<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    public const  string GERRIT_ID    = 'gerrit';
    public const  string GERRIT_LABEL = 'Gerrit';

    public const  string SSH_ID    = 'ssh';
    public const  string SSH_LABEL = 'SSH';

    public const  string HTTPS_ID    = 'https';
    public const  string HTTPS_LABEL = 'HTTPS';

    /** @var CloneURLPresenter[] */
    public $clone_url_presenters = [];
    /** @var DefaultCloneURLSelector */
    private $default_url_selector;
    /**
     * @var bool
     */
    public $has_clone_urls;

    public function __construct(DefaultCloneURLSelector $default_url_selector)
    {
        $this->default_url_selector = $default_url_selector;
    }

    public function build(CloneURLs $clone_urls, GitRepository $repository, \PFUser $current_user)
    {
        try {
            $selected_clone_url = $this->default_url_selector->select($clone_urls, $current_user);

            $this->clone_url_presenters[] = new CloneURLPresenter(
                $selected_clone_url->getId(),
                $selected_clone_url->getUrl(),
                $selected_clone_url->getLabel(),
                $current_user->isAnonymous()
            );

            $this->has_clone_urls = true;

            $this->buildAdditionalCloneURLs($clone_urls, $selected_clone_url, $repository);
        } catch (NoCloneURLException $e) {
            $this->has_clone_urls = false;
        }
    }

    private function buildAdditionalCloneURLs(
        CloneURLs $clone_urls,
        DefaultCloneURL $selected_clone_url,
        GitRepository $repository,
    ): void {
        if ($clone_urls->hasSshUrl() && ! $selected_clone_url->hasSameUrl($clone_urls->getSshUrl())) {
            $this->clone_url_presenters[] = new CloneURLPresenter(
                self::SSH_ID,
                $clone_urls->getSshUrl(),
                self::SSH_LABEL,
                $clone_urls->hasGerritUrl()
            );
        }
        if ($clone_urls->hasHttpsUrl() && ! $selected_clone_url->hasSameUrl($clone_urls->getHttpsUrl())) {
            $this->clone_url_presenters[] = new CloneURLPresenter(
                self::HTTPS_ID,
                $clone_urls->getHttpsUrl(),
                self::HTTPS_LABEL,
                $clone_urls->hasGerritUrl()
            );
        }
    }
}
