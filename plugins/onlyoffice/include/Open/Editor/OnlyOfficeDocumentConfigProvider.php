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
use Tuleap\OnlyOffice\Open\AllowedFileExtensions;
use Tuleap\OnlyOffice\Open\DocmanFileLastVersion;
use Tuleap\OnlyOffice\Open\ProvideDocmanFileLastVersion;
use Tuleap\ServerHostname;

final class OnlyOfficeDocumentConfigProvider implements ProvideOnlyOfficeConfigDocument
{
    public function __construct(
        private ProvideDocmanFileLastVersion $docman_file_last_version_provider,
        private OnlyOfficeDownloadDocumentTokenGenerator $token_generator,
    ) {
    }

    /**
     * @psalm-return Ok<OnlyOfficeDocumentConfig>|Err<Fault>
     */
    public function getDocumentConfig(\PFUser $user, int $item_id, \DateTimeImmutable $now): Ok|Err
    {
        return $this->docman_file_last_version_provider
            ->getLastVersionOfAFileUserCanAccess($user, $item_id)
            ->andThen(
                /** @psalm-return Ok<OnlyOfficeDocumentConfig>|Err<Fault> */
                function (DocmanFileLastVersion $file_last_version) use ($user, $now): Ok|Err {
                    $filename  = $file_last_version->version->getFilename();
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    $item_id   = $file_last_version->item->getId();
                    if (! AllowedFileExtensions::isExtensionAllowedToBeOpenInOnlyOffice($extension)) {
                        return Result::err(
                            Fault::fromMessage(
                                sprintf('Item #%d cannot be opened with ONLYOFFICE', $item_id)
                            )
                        );
                    }

                    $token = $this->token_generator->generateDownloadToken($user, $file_last_version, $now);

                    return Result::ok(
                        new OnlyOfficeDocumentConfig(
                            $extension,
                            sprintf('tuleap_document_%d_%d', $item_id, $file_last_version->version->getId()),
                            $filename,
                            ServerHostname::HTTPSUrl() . '/onlyoffice/document_download?token=' . urlencode($token->getString())
                        )
                    );
                }
            );
    }
}
