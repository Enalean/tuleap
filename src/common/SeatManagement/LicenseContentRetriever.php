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
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Parser as ParserInterface;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tuleap\Option\Option;
use function Psl\File\read;

final readonly class LicenseContentRetriever implements RetrieveLicenseContent
{
    public function __construct(
        private LoggerInterface $logger,
        private ParserInterface $parser,
        private TreeMapper $mapper,
    ) {
    }

    #[Override]
    public function retrieveLicenseContent(string $license_file_path): Option
    {
        $license_content = trim(read($license_file_path));
        assert($license_content !== '');

        try {
            $token = $this->parser->parse($license_content);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $error) {
            throw new RuntimeException('Should have been caught at license signature checker', 0, $error);
        }
        assert($token instanceof UnencryptedToken);

        try {
            return Option::fromValue($this->mapper->map(LicenseContent::class, $token->claims()->all()));
        } catch (MappingError $error) {
            $this->logger->error('Failed parsing license claims: ' . $error->getMessage(), ['exception' => $error]);
            return Option::nothing(LicenseContent::class);
        }
    }
}
