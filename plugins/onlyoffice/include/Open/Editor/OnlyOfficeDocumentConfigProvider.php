<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open\Editor;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenGenerator;
use Tuleap\OnlyOffice\Open\OnlyOfficeDocument;
use Tuleap\OnlyOffice\Open\ProvideOnlyOfficeDocument;

final class OnlyOfficeDocumentConfigProvider implements ProvideOnlyOfficeConfigDocument
{
    public function __construct(
        private ProvideOnlyOfficeDocument $document_provider,
        private OnlyOfficeDownloadDocumentTokenGenerator $token_generator,
    ) {
    }

    /**
     * @psalm-return Ok<OnlyOfficeDocumentConfig>|Err<Fault>
     */
    #[\Override]
    public function getDocumentConfig(\PFUser $user, int $item_id, \DateTimeImmutable $now): Ok|Err
    {
        return $this->document_provider
            ->getDocument($user, $item_id)
            ->andThen(
                /** @psalm-return Ok<OnlyOfficeDocumentConfig>|Err<Fault> */
                function (OnlyOfficeDocument $document) use ($user, $now): Ok|Err {
                    $token = $this->token_generator->generateDownloadToken($user, $document, $now);

                    return Result::ok(
                        OnlyOfficeDocumentConfig::fromDocument($document, $token),
                    );
                }
            );
    }
}
