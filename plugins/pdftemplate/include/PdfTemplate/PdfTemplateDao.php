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

final class PdfTemplateDao extends DataAccessObject implements RetrieveAllTemplates, CreateTemplate, DeleteTemplate, RetrieveTemplate, UpdateTemplate
{
    public function __construct(private readonly PdfTemplateIdentifierFactory $identifier_factory)
    {
        parent::__construct();
    }

    public function create(string $label, string $description, string $style): PdfTemplate
    {
        $identifier = $this->identifier_factory->buildIdentifier();

        $this->getDB()->insert(
            'plugin_pdftemplate',
            [
                'id'          => $identifier->getBytes(),
                'label'       => $label,
                'description' => $description,
                'style'       => $style,
            ],
        );

        return new PdfTemplate($identifier, $label, $description, $style);
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
        );
    }

    public function update(PdfTemplate $template): void
    {
        $this->getDB()->update(
            'plugin_pdftemplate',
            [
                'label'       => $template->label,
                'description' => $template->description,
                'style'       => $template->style,
            ],
            [
                'id' => $template->identifier->getBytes(),
            ]
        );
    }
}
