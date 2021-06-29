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

use Tuleap\Platform\Banner\Banner;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 * @psalm-import-type BannerImportance from \Tuleap\Platform\Banner\Banner
 */
final class BannerRepresentation
{
    /**
     * @var string {@required true}
     */
    public string $message;
    /**
     * @var string {@required true} {@choice standard,warning,critical}
     * @psalm-var BannerImportance
     */
    public string $importance;

    /**
     * @var string {@required false} Expiration date in ISO 8601 date format
     * @psalm-var string|null
     */
    public ?string $expiration_date = null;

    public function __construct(Banner $banner)
    {
        $this->message         = $banner->getMessage();
        $this->importance      = $banner->getImportance();
        $this->expiration_date = JsonCast::fromDateTimeToDate($banner->getExpirationDate());
    }
}
