<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Option\Option;
use Tuleap\Tracker\Semantic\IDuplicateSemantic;
use Tuleap\Tracker\Semantic\IBuildSemanticFromXML;
use Tuleap\Tracker\Semantic\Title\GetTitleSemantic;
use Tuleap\Tracker\Semantic\Title\TitleSemanticDAO;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Semantic_TitleFactory implements IBuildSemanticFromXML, IDuplicateSemantic, GetTitleSemantic
{
    /**
     * Hold an instance of the class
     * @var self|null
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return Tracker_Semantic_TitleFactory an instance of the factory
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            $c              = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    public function getByTracker(Tracker $tracker): Tracker_Semantic_Title
    {
        return Tracker_Semantic_Title::load($tracker);
    }

    public function getInstanceFromXML(
        SimpleXMLElement $current_semantic_xml,
        SimpleXMLElement $all_semantics_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $tracker_mapping,
    ): ?Tracker_Semantic {
        $xml_field            = $current_semantic_xml->field;
        $xml_field_attributes = $xml_field->attributes();
        if (! isset($xml_mapping[(string) $xml_field_attributes['REF']])) {
            return null;
        }

        $field = $xml_mapping[(string) $xml_field_attributes['REF']];
        return new Tracker_Semantic_Title($tracker, $field);
    }

    /**
     * Duplicate the semantic from tracker source to tracker target
     */
    public function duplicate(int $from_tracker_id, int $to_tracker_id, array $field_mapping): void
    {
        $new_dao = new TitleSemanticDAO();
        $new_dao->searchByTrackerId($from_tracker_id)
            ->andThen(function (int $from_title_field_id) use ($field_mapping): Option {
                foreach ($field_mapping as $mapping) {
                    if ($mapping['from'] == $from_title_field_id) {
                        return Option::fromValue((int) $mapping['to']);
                    }
                }
                return Option::nothing(\Psl\Type\int());
            })->apply(function (int $to_title_field_id) use ($new_dao, $to_tracker_id): void {
                $new_dao->save($to_tracker_id, $to_title_field_id);
            });
    }
}
