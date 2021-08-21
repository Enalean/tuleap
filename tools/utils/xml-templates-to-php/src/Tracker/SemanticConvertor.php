<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tools\Xml2Php\Tracker;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Psr\Log\LoggerInterface;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Semantic\Contributor\XML\XMLContributorSemantic;
use Tuleap\Tracker\Semantic\Description\XML\XMLDescriptionSemantic;
use Tuleap\Tracker\Semantic\Status\Done\XML\XMLDoneSemantic;
use Tuleap\Tracker\Semantic\Status\XML\XMLStatusSemantic;
use Tuleap\Tracker\Semantic\Title\XML\XMLTitleSemantic;
use Tuleap\Tracker\Semantic\XML\XMLFieldsBasedSemantic;

class SemanticConvertor
{
    public static function buildFromXML(
        \SimpleXMLElement $xml,
        LoggerInterface $logger,
        IdToNameMapping $id_to_name_mapping,
    ): ?Expr {
        $type = (string) $xml['type'];
        switch ($type) {
            case 'title':
                return new Expr\New_(
                    new Name('\\' . XMLTitleSemantic::class),
                    [
                        new Arg(
                            new Expr\New_(
                                new Name('\\' . XMLReferenceByName::class),
                                [
                                    new Arg(new String_($id_to_name_mapping->get((string) $xml->field['REF']))),
                                ]
                            )
                        ),
                    ]
                );
            case 'description':
                return new Expr\New_(
                    new Name('\\' . XMLDescriptionSemantic::class),
                    [
                        new Arg(
                            new Expr\New_(
                                new Name('\\' . XMLReferenceByName::class),
                                [
                                    new Arg(new String_($id_to_name_mapping->get((string) $xml->field['REF']))),
                                ]
                            )
                        ),
                    ]
                );
            case 'contributor':
                return new Expr\New_(
                    new Name('\\' . XMLContributorSemantic::class),
                    [
                        new Arg(
                            new Expr\New_(
                                new Name('\\' . XMLReferenceByName::class),
                                [
                                    new Arg(new String_($id_to_name_mapping->get((string) $xml->field['REF']))),
                                ]
                            )
                        ),
                    ]
                );
            case 'status':
                $expr = new Expr\New_(
                    new Name('\\' . XMLStatusSemantic::class),
                    [
                        new Arg(
                            new Expr\New_(
                                new Name('\\' . XMLReferenceByName::class),
                                [
                                    new Arg(new String_($id_to_name_mapping->get((string) $xml->field['REF']))),
                                ]
                            )
                        ),
                    ]
                );

                $values = [];
                foreach ($xml->open_values->open_value as $value) {
                    $values[] = new Arg(
                        new Expr\New_(
                            new Name('\\' . XMLBindValueReferenceById::class),
                            [
                                new Arg(new String_((string) $value['REF'])),
                            ]
                        )
                    );
                }
                if ($values) {
                    $expr = new Expr\MethodCall($expr, 'withOpenValues', $values);
                }

                return $expr;
            case 'done':
                $expr = new Expr\New_(
                    new Name('\\' . XMLDoneSemantic::class)
                );

                $values = [];
                foreach ($xml->closed_values->closed_value as $value) {
                    $values[] = new Arg(
                        new Expr\New_(
                            new Name('\\' . XMLBindValueReferenceById::class),
                            [
                                new Arg(new String_((string) $value['REF'])),
                            ]
                        )
                    );
                }
                if ($values) {
                    $expr = new Expr\MethodCall($expr, 'withDoneValues', $values);
                }

                return $expr;
            case 'tooltip':
            case 'plugin_cardwall_card_fields':
                $expr = new Expr\New_(
                    new Name('\\' . XMLFieldsBasedSemantic::class),
                    [
                        new Arg(new String_($type)),
                    ]
                );

                $fields = [];
                foreach ($xml->field as $field) {
                    $fields[] = new Arg(
                        new Expr\New_(
                            new Name('\\' . XMLReferenceByName::class),
                            [
                                new Arg(new String_($id_to_name_mapping->get((string) $field['REF']))),
                            ]
                        )
                    );
                }
                if ($fields) {
                    $expr = new Expr\MethodCall($expr, 'withFields', $fields);
                }

                return $expr;
            default:
                $logger->error(sprintf('Semantic %s is not implemented yet.', $type));
        }

        return null;
    }
}
