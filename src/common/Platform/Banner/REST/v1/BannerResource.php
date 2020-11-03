<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Platform\Banner\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Platform\Banner\BannerCreator;
use Tuleap\Platform\Banner\BannerDao;
use Tuleap\Platform\Banner\BannerRemover;
use Tuleap\Platform\Banner\BannerRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;

final class BannerResource extends AuthenticatedResource
{
    public const ROUTE = 'banner';

    /**
     * @url OPTIONS
     */
    public function options(): void
    {
        Header::allowOptionsGetPutDelete();
    }

    /**
     * Put banner
     *
     * Put the banner message to be displayed
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"message": "A message to be displayed on the platform",<br>
     *   &nbsp;"importance": "critical"<br>
     *  }<br>
     * </pre>
     *
     * @url PUT
     *
     * @param BannerRepresentation $banner banner to be displayed {@from body}
     *
     * @throws RestException
     */
    protected function putBanner(BannerRepresentation $banner): void
    {
        $this->checkSiteAdminAccess();
        $this->options();

        if (empty($banner->message)) {
            throw new RestException(400, 'Message cannot be empty');
        }

        $banner_creator = new BannerCreator(new BannerDao());
        $banner_creator->addBanner($banner->message, $banner->importance);
    }

    /**
     * Delete the banner message
     *
     * @url DELETE
     *
     * @throws RestException 403
     */
    protected function deleteBanner(): void
    {
        $this->checkSiteAdminAccess();
        $this->options();

        $banner_remover = new BannerRemover(new BannerDao());
        $banner_remover->deleteBanner();
    }

    /**
     * Get banner
     *
     * Get the banner
     *
     * @url GET
     *
     * @access hybrid
     * @oauth2-scope read:project
     *
     * @throws RestException
     */
    public function getBanner(): BannerRepresentation
    {
        $this->checkAccess();
        $this->options();

        $banner_retriever = new BannerRetriever(new BannerDao());
        $banner = $banner_retriever->getBanner();
        if (! $banner) {
            throw new RestException(404, 'No banner set for the platform');
        }

        return new BannerRepresentation($banner);
    }

    private function checkSiteAdminAccess(): void
    {
        $this->checkAccess();

        $user = \UserManager::instance()->getCurrentUser();
        if (! $user->isSuperUser()) {
            throw new RestException(403);
        }
    }
}
