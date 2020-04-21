<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ArtifactRuleValueTest extends \PHPUnit\Framework\TestCase
{
    public function testApplyTo(): void
    {
        $arv = new ArtifactRuleValue('id', 'group_artifact_id', 'source_field', 'source_value', 'target_field', 'target_value');

        $this->assertTrue($arv->applyTo('group_artifact_id', 'source_field', 'source_value', 'target_field', 'target_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($arv->applyTo('false_group_artifact_id', 'source_field', 'source_value', 'target_field', 'farget_value'));
        $this->assertFalse($arv->applyTo('false_group_artifact_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id', 'target_source_field', 'source_value', 'target_field', 'false_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id', 'target_source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id', 'source_field', 'false_source_value', 'target_field', 'false_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id', 'source_field', 'false_source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id', 'source_field', 'source_value', 'false_target_field', 'false_value'));
        $this->assertFalse($arv->applyTo('group_artifact_id', 'source_field', 'source_value', 'false_target_field', 'false_target_value'));
    }

    public function testCanApplyTo(): void
    {
        $arv = new ArtifactRuleValue('id', 'group_artifact_id', 'source_field', 'source_value', 'target_field', 'target_value');

        $this->assertTrue($arv->canApplyTo('group_artifact_id', 'source_field', 'source_value', 'target_field', 'target_value'));
        $this->assertTrue($arv->canApplyTo('group_artifact_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($arv->canApplyTo('false_group_artifact_id', 'source_field', 'source_value', 'target_field', 'farget_value'));
        $this->assertFalse($arv->canApplyTo('false_group_artifact_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($arv->canApplyTo('group_artifact_id', 'target_source_field', 'source_value', 'target_field', 'false_value'));
        $this->assertFalse($arv->canApplyTo('group_artifact_id', 'target_source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($arv->canApplyTo('group_artifact_id', 'source_field', 'false_source_value', 'target_field', 'false_value'));
        $this->assertFalse($arv->canApplyTo('group_artifact_id', 'source_field', 'false_source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($arv->canApplyTo('group_artifact_id', 'source_field', 'source_value', 'false_target_field', 'false_value'));
        $this->assertFalse($arv->canApplyTo('group_artifact_id', 'source_field', 'source_value', 'false_target_field', 'false_target_value'));
    }
}
