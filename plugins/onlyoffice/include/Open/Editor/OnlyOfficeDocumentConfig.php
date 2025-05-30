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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\OnlyOffice\Open\OnlyOfficeDocument;
use Tuleap\ServerHostname;

/**
 * @psalm-immutable
 * @see https://api.onlyoffice.com/editors/config/document
 */
final class OnlyOfficeDocumentConfig
{
    public array $permissions;

    /**
     * @psalm-param lowercase-string $fileType
     */
    private function __construct(
        public string $fileType,
        public string $key,
        public string $title,
        public string $url,
        private OnlyOfficeDocument $document,
    ) {
        $this->permissions = ['chat' => false, 'print' => false, 'edit' => $this->document->can_be_edited];
    }

    public static function fromDocument(OnlyOfficeDocument $document, ConcealedString $download_token): self
    {
        return new self(
            strtolower(pathinfo($document->filename, PATHINFO_EXTENSION)),
            sprintf('tuleap_document_%d_%d', $document->item->getId(), $document->version_id),
            $document->filename,
            ServerHostname::HTTPSUrl() . '/onlyoffice/document_download?token=' . urlencode($download_token->getString()),
            $document,
        );
    }

    public function getAssociatedDocument(): OnlyOfficeDocument
    {
        return $this->document;
    }
}
