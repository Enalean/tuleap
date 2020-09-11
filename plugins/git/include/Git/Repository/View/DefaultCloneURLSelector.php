<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Repository\View;

class DefaultCloneURLSelector
{
    /**
     * @throws NoCloneURLException
     */
    public function select(CloneURLs $clone_urls, \PFUser $current_user): DefaultCloneURL
    {
        if ($current_user->isAnonymous()) {
            if ($clone_urls->hasHttpsUrl()) {
                return new DefaultCloneURL(
                    ClonePresenter::HTTPS_ID,
                    $clone_urls->getHttpsUrl(),
                    $this->getDefaultLabel(ClonePresenter::HTTPS_LABEL)
                );
            }

            throw new NoCloneURLException();
        }

        if ($clone_urls->hasGerritUrl()) {
            return new DefaultCloneURL(
                ClonePresenter::GERRIT_ID,
                $clone_urls->getGerritUrl(),
                $this->getDefaultLabel(ClonePresenter::GERRIT_LABEL)
            );
        }

        if ($clone_urls->hasSshUrl()) {
            return new DefaultCloneURL(ClonePresenter::SSH_ID, $clone_urls->getSshUrl(), $this->getDefaultLabel(ClonePresenter::SSH_LABEL));
        }

        if ($clone_urls->hasHttpsUrl()) {
            return new DefaultCloneURL(ClonePresenter::HTTPS_ID, $clone_urls->getHttpsUrl(), $this->getDefaultLabel(ClonePresenter::HTTPS_LABEL));
        }

        throw new NoCloneURLException();
    }

    private function getDefaultLabel(string $label)
    {
        return sprintf(dgettext("tuleap-git", "%s (Default)"), $label);
    }
}
