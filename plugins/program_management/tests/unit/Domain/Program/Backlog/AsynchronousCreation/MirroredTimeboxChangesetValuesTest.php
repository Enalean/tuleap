<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkTypeProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldReferencesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;

final class MirroredTimeboxChangesetValuesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_LINK_ID            = 530;
    private const TITLE_ID                    = 130;
    private const DESCRIPTION_ID              = 483;
    private const STATUS_ID                   = 656;
    private const START_DATE_ID               = 801;
    private const END_DATE_ID                 = 234;
    private const LINKED_ARTIFACT_ID          = 95;
    private const TITLE_VALUE                 = 'Circassian';
    private const DESCRIPTION_VALUE           = 'consideringly palpebral';
    private const DESCRIPTION_FORMAT          = 'html';
    private const MAPPED_STATUS_BIND_VALUE_ID = 4192;
    private const START_DATE_VALUE            = '2020-04-16';
    private const END_DATE_VALUE              = '2024-08-14';

    public function testItBuildsFromSourceValuesAndFields(): void
    {
        $status_mapper       = MapStatusByValueStub::withSuccessiveBindValueIds(self::MAPPED_STATUS_BIND_VALUE_ID);
        $source_values       = SourceTimeboxChangesetValuesBuilder::buildWithValues(
            self::TITLE_VALUE,
            self::DESCRIPTION_VALUE,
            self::DESCRIPTION_FORMAT,
            ['typifier'],
            self::START_DATE_VALUE,
            self::END_DATE_VALUE,
            self::LINKED_ARTIFACT_ID,
            1604793787
        );
        $artifact_link_value = ArtifactLinkValue::fromArtifactAndType(
            $source_values->getSourceTimebox(),
            ArtifactLinkTypeProxy::fromMirrorTimeboxType()
        );
        $target_fields       = SynchronizedFieldReferencesBuilder::buildWithPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(
                self::TITLE_ID,
                self::DESCRIPTION_ID,
                self::STATUS_ID,
                self::START_DATE_ID,
                self::END_DATE_ID,
                self::ARTIFACT_LINK_ID
            )
        );
        $values              = MirroredTimeboxChangesetValues::fromSourceChangesetValuesAndSynchronizedFields(
            $status_mapper,
            $source_values,
            $target_fields,
            $artifact_link_value
        );

        self::assertSame(self::ARTIFACT_LINK_ID, $values->artifact_link_field->getId());
        self::assertSame(self::LINKED_ARTIFACT_ID, $values->artifact_link_value->linked_artifact->getId());
        self::assertSame(TimeboxArtifactLinkType::ART_LINK_SHORT_NAME, (string) $values->artifact_link_value->type);
        self::assertSame(self::TITLE_ID, $values->title_field->getId());
        self::assertSame(self::TITLE_VALUE, $values->title_value->getValue());
        self::assertSame(self::DESCRIPTION_ID, $values->description_field->getId());
        self::assertSame(self::DESCRIPTION_VALUE, $values->description_value->value);
        self::assertSame(self::DESCRIPTION_FORMAT, $values->description_value->format);
        self::assertSame(self::STATUS_ID, $values->status_field->getId());
        self::assertEquals([self::MAPPED_STATUS_BIND_VALUE_ID], $values->mapped_status_value->getValues());
        self::assertSame(self::START_DATE_ID, $values->start_date_field->getId());
        self::assertSame(self::START_DATE_VALUE, $values->start_date_value->getValue());
        self::assertSame(self::END_DATE_ID, $values->end_period_field->getId());
        self::assertSame(self::END_DATE_VALUE, $values->end_period_value->getValue());
    }
}
