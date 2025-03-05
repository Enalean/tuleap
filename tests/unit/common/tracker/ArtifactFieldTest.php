<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactFieldTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('artifactFieldProvider')]
    public function testItSaysIfFieldIsStandard(ArtifactField $field, bool $result): void
    {
        if ($result === true) {
            self::assertTrue($field->isStandardField());
        } else {
            self::assertFalse($field->isStandardField());
        }
    }

    public static function artifactFieldProvider(): array
    {
        $field_artifact_id             = new ArtifactField();
        $field_artifact_id->field_name = 'artifact_id';

        $field_status_id             = new ArtifactField();
        $field_status_id->field_name = 'status_id';

        $field_submitted_by             = new ArtifactField();
        $field_submitted_by->field_name = 'submitted_by';

        $field_open_date             = new ArtifactField();
        $field_open_date->field_name = 'open_date';

        $field_closed_date             = new ArtifactField();
        $field_closed_date->field_name = 'close_date';

        $field_summary             = new ArtifactField();
        $field_summary->field_name = 'summary';

        $field_details             = new ArtifactField();
        $field_details->field_name = 'details';

        $field_severity             = new ArtifactField();
        $field_severity->field_name = 'severity';

        $field_last_update_date             = new ArtifactField();
        $field_last_update_date->field_name = 'last_update_date';

        $field_custom             = new ArtifactField();
        $field_custom->field_name = 'custom_field';

        return [
            [$field_artifact_id, true],
            [$field_status_id, true],
            [$field_submitted_by, true],
            [$field_open_date, true],
            [$field_closed_date, true],
            [$field_summary, true],
            [$field_details, true],
            [$field_severity, true],
            [$field_last_update_date, true],
            [$field_custom, false],
        ];
    }
}
