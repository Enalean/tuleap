<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Cardwall;

use ParagonIE\EasyDB\EasyStatement;
use Tracker_Report_RendererFactory;
use Tuleap\DB\DataAccessObject;
use Tuleap\Project\MappingRegistry;

final class CardwallRendererDuplicatorDao extends DataAccessObject
{
    /**
     * @param list<array{from: int, to: int, values: array, workflow: bool}> $fields_mapping
     */
    public function duplicate(MappingRegistry $registry, array $fields_mapping): void
    {
        try {
            $renderer_mapping = $registry->getCustomMapping(Tracker_Report_RendererFactory::MAPPING_KEY);
        } catch (\RuntimeException) {
            return;
        }

        $field_id_mapping = [];
        foreach ($fields_mapping as $field_mapping) {
            $field_id_mapping[$field_mapping['from']] = $field_mapping['to'];
        }

        $renderer_statement             = $this->getCase('renderer_id', $renderer_mapping);
        $field_statement                = $this->getCase('field_id', $field_id_mapping);
        $template_renderer_in_statement = EasyStatement::open()->in('renderer_id IN (?*)', $this->getTemplateRendererId($renderer_mapping));
        $sql                            = <<<EOSQL
        INSERT INTO plugin_cardwall_renderer(renderer_id, field_id)
        SELECT $renderer_statement, $field_statement
        FROM plugin_cardwall_renderer
        WHERE $template_renderer_in_statement
        EOSQL;

        $this->getDB()->safeQuery($sql, $template_renderer_in_statement->values());
    }

    private function getTemplateRendererId(array|\ArrayObject $renderer_mapping): array
    {
        $keys = [];
        foreach ($renderer_mapping as $template_id => $new_id) {
            $keys[] = $template_id;
        }
        return $keys;
    }

    private function getCase(string $field_name, array|\ArrayObject $when_statement): string
    {
        return 'CASE ' . $field_name . ' ' . $this->getAllWhenThen($when_statement) . ' END';
    }

    private function getAllWhenThen(array|\ArrayObject $when_statement): string
    {
        $when_then = '';
        foreach ($when_statement as $when => $then) {
            $when_then .= $this->getWhenThen((int) $when, (int) $then);
        }
        return $when_then;
    }

    private function getWhenThen(int $when, int $then): string
    {
        return ' WHEN ' . $when . ' THEN ' . $then . ' ';
    }
}
