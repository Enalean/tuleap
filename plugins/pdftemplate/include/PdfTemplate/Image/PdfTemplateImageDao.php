<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\PdfTemplate\Image;

use Tuleap\DB\DataAccessObject;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifier;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifierFactory;
use Tuleap\User\RetrieveUserById;

final class PdfTemplateImageDao extends DataAccessObject implements CreateImage, RetrieveAllImages, RetrieveImage, DeleteImage
{
    public function __construct(
        private PdfTemplateImageIdentifierFactory $identifier_factory,
        private readonly RetrieveUserById $user_retriever,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function create(
        PdfTemplateImageIdentifier $identifier,
        string $filename,
        int $filesize,
        \PFUser $created_by,
        \DateTimeImmutable $created_date,
    ): PdfTemplateImage {
        $this->getDB()->insert(
            'plugin_pdftemplate_image',
            [
                'id'                => $identifier->getBytes(),
                'filename'          => $filename,
                'filesize'          => $filesize,
                'last_updated_by'   => $created_by->getId(),
                'last_updated_date' => $created_date->getTimestamp(),
            ]
        );

        return new PdfTemplateImage($identifier, $filename, $filesize, $created_by, $created_date);
    }

    #[\Override]
    public function retrieveAll(): array
    {
        $rows = $this->getDB()->run('SELECT * FROM plugin_pdftemplate_image ORDER BY filename ASC');

        return array_values(
            array_map(
                $this->instantiatePdfTemplateImageFromRow(...),
                $rows,
            ),
        );
    }

    private function instantiatePdfTemplateImageFromRow(array $row): PdfTemplateImage
    {
        return new PdfTemplateImage(
            $this->identifier_factory->buildFromBytesData($row['id']),
            $row['filename'],
            $row['filesize'],
            $this->getUser($row['last_updated_by']),
            (new \DateTimeImmutable())->setTimestamp($row['last_updated_date']),
        );
    }

    private function getUser(int $id): \PFUser
    {
        $user = $this->user_retriever->getUserById($id);
        if (! $user) {
            throw new \Exception('Unable to find user ' . $id);
        }

        return $user;
    }

    #[\Override]
    public function retrieveImage(PdfTemplateImageIdentifier $identifier): ?PdfTemplateImage
    {
        $row = $this->getDB()->row(
            'SELECT * FROM plugin_pdftemplate_image WHERE id = ?',
            $identifier->getBytes(),
        );

        if (! $row) {
            return null;
        }

        return $this->instantiatePdfTemplateImageFromRow($row);
    }

    #[\Override]
    public function deleteImage(PdfTemplateImage $image): void
    {
        $this->getDB()->delete(
            'plugin_pdftemplate_image',
            [
                'id' => $image->identifier->getBytes(),
            ]
        );
    }
}
