<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\SeatManagement;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use Lcobucci\JWT\UnencryptedToken;
use Override;
use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Result;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\SeatManagement\Fault\LicenseClaimsParsingFault;

final readonly class LicenseContentRetriever implements RetrieveLicenseContent
{
    public function __construct(
        private LoggerInterface $logger,
        private TreeMapper $mapper,
    ) {
    }

    #[Override]
    public function retrieveLicenseContent(UnencryptedToken $token): Ok|Err
    {
        try {
            return Result::ok($this->mapper->map(LicenseContent::class, $token->claims()->all()));
        } catch (MappingError $error) {
            $this->logger->info('Failed parsing license claims: ' . $error->getMessage(), ['exception' => $error]);
            return Result::err(LicenseClaimsParsingFault::build($error->getMessage()));
        }
    }
}
