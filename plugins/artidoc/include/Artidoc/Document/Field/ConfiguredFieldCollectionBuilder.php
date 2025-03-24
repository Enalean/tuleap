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

namespace Tuleap\Artidoc\Document\Field;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;

/**
 * @psalm-import-type ConfiguredFieldRow from RetrieveConfiguredField
 */
final readonly class ConfiguredFieldCollectionBuilder
{
    public function __construct(private RetrieveConfiguredField $dao, private \Tracker_FormElementFactory $factory)
    {
    }

    public function buildFromSectionIdentifier(SectionIdentifier $section_identifier, \PFUser $user): ConfiguredFieldCollection
    {
        return $this->buildFromRows($this->dao->retrieveConfiguredFieldsFromSectionId($section_identifier), $user);
    }

    public function buildFromArtidoc(ArtidocWithContext $artidoc, \PFUser $user): ConfiguredFieldCollection
    {
        return $this->buildFromArtidocId($artidoc->document->getId(), $user);
    }

    private function buildFromArtidocId(int $artidoc_id, \PFUser $user): ConfiguredFieldCollection
    {
        return $this->buildFromRows($this->dao->retrieveConfiguredFieldsFromItemId($artidoc_id), $user);
    }

    /**
     * @param list<ConfiguredFieldRow> $rows
     */
    private function buildFromRows(array $rows, \PFUser $user): ConfiguredFieldCollection
    {
        $fields = [];
        foreach ($rows as $row) {
            $field = $this->factory->getFieldById($row['field_id']);

            if (! $field instanceof \Tracker_FormElement_Field_String) {
                continue;
            }

            if (! $field->userCanRead($user)) {
                continue;
            }

            if (! isset($fields[$field->tracker_id])) {
                $fields[$field->tracker_id] = [];
            }
            $fields[$field->tracker_id][] = new ConfiguredField($field, $row['display_type']);
        }

        return new ConfiguredFieldCollection($fields);
    }
}
