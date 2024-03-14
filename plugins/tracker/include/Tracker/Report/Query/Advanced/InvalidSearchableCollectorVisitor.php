<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Report\Query\Advanced;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SearchableVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FlatInvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\InvalidMetadataException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\InvalidMetadataForComparisonException;

/**
 * @template-implements SearchableVisitor<InvalidSearchableCollectorParameters, void>
 */
final readonly class InvalidSearchableCollectorVisitor implements SearchableVisitor
{
    public const SUPPORTED_NAME = '@comments';

    public function __construct(
        private \Tracker_FormElementFactory $form_element_factory,
        private FlatInvalidFieldChecker $field_checker,
        private \Tracker $tracker,
        private \PFUser $user,
    ) {
    }

    public function visitField(Field $searchable_field, $parameters)
    {
        $field = $this->form_element_factory->getUsedFormElementFieldByNameForUser(
            $this->tracker->getId(),
            $searchable_field->getName(),
            $this->user
        );

        if (! $field) {
            $parameters->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->addNonexistentSearchable($searchable_field->getName());
            return;
        }
        try {
            $this->field_checker->checkFieldIsValidForComparison($parameters->getComparison(), $field);
        } catch (InvalidFieldException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->addInvalidSearchableError($exception->getMessage());
        }
    }

    public function visitMetadata(Metadata $metadata, $parameters)
    {
        if ($metadata->getName() !== self::SUPPORTED_NAME) {
            $parameters->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->addNonexistentSearchable($metadata->getName());
            return;
        }

        try {
            $parameters->getMetadataChecker()
                ->checkMetaDataIsValid($metadata, $parameters->getComparison());
        } catch (InvalidMetadataForComparisonException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->addInvalidSearchableError($exception->getMessage());
        } catch (InvalidMetadataException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()
                ->getInvalidSearchablesCollection()
                ->addInvalidSearchableError($exception->getMessage());
        }
    }
}
