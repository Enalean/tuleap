<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
use CuyZ\Valinor\Mapper\Source\Exception\InvalidSource;
use CuyZ\Valinor\Mapper\Source\JsonSource;
use CuyZ\Valinor\Mapper\TreeMapper;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Parser as ParserInterface;
use Lcobucci\JWT\Signer\Eddsa;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validator;
use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Result;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\SeatManagement\Fault\InvalidLicenseSignatureFault;
use function Psl\File\read;
use function Psl\Filesystem\is_file;

final readonly class LicenseSignatureChecker implements CheckLicenseSignature
{
    private const string ISSUER = 'enalean-tuleap-enterprise';

    public function __construct(
        private LoggerInterface $logger,
        private ParserInterface $parser,
        private Validator $validator,
        private TreeMapper $mapper,
    ) {
    }

    #[\Override]
    public function checkLicenseSignature(string $license_file_path, string $keys_directory): Ok|Err
    {
        $license_content = trim(read($license_file_path));
        if ($license_content === '') {
            $this->logger->info('License file is empty');
            return Result::err(InvalidLicenseSignatureFault::build('License file is empty'));
        }

        try {
            $token = $this->parser->parse($license_content);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $error) {
            $this->logger->info('Failed parsing license: ' . $error->getMessage(), ['exception' => $error]);
            return Result::err(InvalidLicenseSignatureFault::build($error->getMessage()));
        }

        try {
            $headers = $this->mapper->map(LicenseHeaders::class, $token->headers()->all());
        } catch (MappingError $error) {
            $this->logger->info('Failed parsing license headers: ' . $error->getMessage(), ['exception' => $error]);
            return Result::err(InvalidLicenseSignatureFault::build($error->getMessage()));
        }

        $kid             = $headers->kid->toString();
        $public_key_file = "$keys_directory/$kid.key";
        if (! is_file($public_key_file)) {
            $this->logger->info('License uses non-existent public key.');
            return Result::err(InvalidLicenseSignatureFault::build('License uses non-existent public key.'));
        }

        try {
            $public_key = $this->mapper->map(LicensePublicKey::class, new JsonSource(read($public_key_file)));
        } catch (MappingError | InvalidSource $error) {
            $this->logger->info('Failed parsing public key: ' . $error->getMessage(), ['exception' => $error]);
            return Result::err(InvalidLicenseSignatureFault::build($error->getMessage()));
        }

        if (! $this->validator->validate($token, new IssuedBy(self::ISSUER), new SignedWith(new Eddsa(), InMemory::plainText($public_key->x)))) {
            $this->logger->info('License signature is invalid.');
            return Result::err(InvalidLicenseSignatureFault::build('License signature is invalid.'));
        }

        assert($token instanceof UnencryptedToken);
        return Result::ok($token);
    }
}
