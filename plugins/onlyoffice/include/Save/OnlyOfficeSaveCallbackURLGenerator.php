<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Save;

use Tuleap\OnlyOffice\Open\Editor\OnlyOfficeDocumentConfig;
use Tuleap\ServerHostname;

final class OnlyOfficeSaveCallbackURLGenerator
{
    public const CALLBACK_SAVE_URL = '/onlyoffice/document_save';

    public function __construct(private OnlyOfficeSaveDocumentTokenGenerator $save_document_token_generator)
    {
    }

    public function getCallbackURL(\PFUser $user, OnlyOfficeDocumentConfig $document_config, \DateTimeImmutable $now): string
    {
        $save_document_token = $this->save_document_token_generator->generateSaveToken(
            $user,
            $document_config->getAssociatedDocument(),
            $now
        );

        if ($save_document_token === null) {
            return ServerHostname::HTTPSUrl() . self::CALLBACK_SAVE_URL;
        }

        return ServerHostname::HTTPSUrl() . self::CALLBACK_SAVE_URL . '?token=' . urlencode($save_document_token->getString());
    }
}
