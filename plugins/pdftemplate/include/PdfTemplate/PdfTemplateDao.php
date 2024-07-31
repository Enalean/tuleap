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

namespace Tuleap\PdfTemplate;

use Tuleap\DB\DataAccessObject;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifier;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\User\RetrieveUserById;

final class PdfTemplateDao extends DataAccessObject implements RetrieveAllTemplates, CreateTemplate, DeleteTemplate, RetrieveTemplate, UpdateTemplate
{
    public function __construct(
        private readonly PdfTemplateIdentifierFactory $identifier_factory,
        private readonly RetrieveUserById $user_retriever,
    ) {
        parent::__construct();
    }

    public function create(
        string $label,
        string $description,
        string $style,
        string $header_content,
        string $footer_content,
        \PFUser $created_by,
        \DateTimeImmutable $created_date,
    ): PdfTemplate {
        $identifier = $this->identifier_factory->buildIdentifier();

        $this->getDB()->insert(
            'plugin_pdftemplate',
            [
                'id'                => $identifier->getBytes(),
                'label'             => $label,
                'description'       => $description,
                'style'             => $style,
                'header_content'    => $header_content,
                'footer_content'    => $footer_content,
                'last_updated_by'   => $created_by->getId(),
                'last_updated_date' => $created_date->getTimestamp(),
            ],
        );

        return new PdfTemplate($identifier, $label, $description, $style, $header_content, $footer_content, $created_by, $created_date);
    }

    public function retrieveAll(): array
    {
        $rows = $this->getDB()->run('SELECT * FROM plugin_pdftemplate ORDER BY label ASC');

        return array_values(
            array_map(
                $this->instantiatePdfTemplateFromRow(...),
                $rows,
            ),
        );
    }

    public function delete(PdfTemplateIdentifier $identifier): void
    {
        $this->getDB()->delete(
            'plugin_pdftemplate',
            [
                'id' => $identifier->getBytes(),
            ]
        );
    }

    public function retrieveTemplate(PdfTemplateIdentifier $identifier): ?PdfTemplate
    {
        $row = $this->getDB()->row(
            'SELECT * FROM plugin_pdftemplate WHERE id = ?',
            $identifier->getBytes(),
        );

        if (! $row) {
            return null;
        }

        return $this->instantiatePdfTemplateFromRow($row);
    }

    private function instantiatePdfTemplateFromRow(array $row): PdfTemplate
    {
        return new PdfTemplate(
            $this->identifier_factory->buildFromBytesData($row['id']),
            $row['label'],
            $row['description'],
            $row['style'],
            $row['header_content'],
            $row['footer_content'],
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

    public function update(PdfTemplate $template): void
    {
        $this->getDB()->update(
            'plugin_pdftemplate',
            [
                'label'             => $template->label,
                'description'       => $template->description,
                'style'             => $template->style,
                'header_content'    => $template->header_content,
                'footer_content'    => $template->footer_content,
                'last_updated_by'   => $template->last_updated_by->getId(),
                'last_updated_date' => $template->last_updated_date->getTimestamp(),
            ],
            [
                'id' => $template->identifier->getBytes(),
            ]
        );
    }
}
