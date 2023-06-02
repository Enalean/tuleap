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

use PFUser;
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SearchableVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\InvalidMetadataException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\InvalidMetadataForComparisonException;

/**
 * @template-implements SearchableVisitor<InvalidSearchableCollectorParameters, void>
 */
final class InvalidSearchableCollectorVisitor implements SearchableVisitor
{
    public const SUPPORTED_NAME = '@comments';

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Tracker
     */
    private $tracker;
    /**
     * @var PFUser
     */
    private $user;

    public function __construct(Tracker_FormElementFactory $form_element_factory, Tracker $tracker, PFUser $user)
    {
        $this->form_element_factory = $form_element_factory;
        $this->tracker              = $tracker;
        $this->user                 = $user;
    }

    public function visitField(Field $searchable_field, $parameters)
    {
        $field = $this->form_element_factory->getUsedFormElementFieldByNameForUser(
            $this->tracker->getId(),
            $searchable_field->getName(),
            $this->user
        );

        if (! $field) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addNonexistentSearchable(
                $searchable_field->getName()
            );
        } else {
            try {
                $parameters->getCheckerProvider()
                    ->getInvalidFieldChecker($field)
                    ->checkFieldIsValidForComparison($parameters->getComparison(), $field);
            } catch (InvalidFieldException $exception) {
                $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addInvalidSearchableError(
                    $exception->getMessage()
                );
            }
        }
    }

    public function visitMetadata(Metadata $metadata, $parameters)
    {
        if ($metadata->getName() !== self::SUPPORTED_NAME) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addNonexistentSearchable(
                $metadata->getName()
            );

            return;
        }

        try {
            $parameters->getMetadataChecker()->checkMetaDataIsValid($metadata, $parameters->getComparison());
        } catch (InvalidMetadataForComparisonException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addInvalidSearchableError(
                $exception->getMessage()
            );
        } catch (InvalidMetadataException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addInvalidSearchableError(
                $exception->getMessage()
            );
        }
    }
}
