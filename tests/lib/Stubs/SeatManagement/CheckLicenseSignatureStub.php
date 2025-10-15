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

namespace Tuleap\Test\Stubs\SeatManagement;

use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\Signature;
use Lcobucci\JWT\UnencryptedToken;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\SeatManagement\CheckLicenseSignature;
use Tuleap\SeatManagement\Fault\InvalidLicenseSignatureFault;

final readonly class CheckLicenseSignatureStub implements CheckLicenseSignature
{
    private UnencryptedToken $token;

    private function __construct(private bool $is_valid)
    {
        $this->token = new Plain(new DataSet([], ''), new DataSet([], ''), new Signature('a', 'a'));
    }

    public static function buildWithValidSignature(): self
    {
        return new self(true);
    }

    public static function buildWithInvalidSignature(): self
    {
        return new self(false);
    }

    #[\Override]
    public function checkLicenseSignature(string $license_file_path, string $keys_directory): Ok|Err
    {
        return $this->is_valid
            ? Result::ok($this->token)
            : Result::err(InvalidLicenseSignatureFault::build('Invalid license signature.'));
    }
}
