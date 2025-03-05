<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\FormElement;

use Tracker_FormElement_Field_List_Bind;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeRepresentation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinksFieldRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsWithAllowedLinkTypes(): void
    {
        $type        = 'art_link';
        $permissions = ['read', 'update', 'submit'];

        $link_field = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $link_field->method('getId')->willReturn(666);
        $link_field->method('getName')->willReturn('the_link_field');
        $link_field->method('getLabel')->willReturn('The links');
        $link_field->method('isCollapsed')->willReturn(false);
        $link_field->method('isRequired')->willReturn(true);
        $link_field->method('getDefaultRESTValue')->willReturn(null);
        $link_field->method('getRESTAvailableValues')->willReturn(null);
        $link_field->method('getRESTBindingProperties')->willReturn([
            Tracker_FormElement_Field_List_Bind::REST_TYPE_KEY => null,
            Tracker_FormElement_Field_List_Bind::REST_LIST_KEY => [],
        ]);

        $allowed_link_types = [
            TypePresenter::buildVisibleType('_is_child', 'Child', 'Parent'),
            TypePresenter::buildVisibleType('duplicate', 'Duplicated by', 'Duplicates'),
            TypePresenter::buildVisibleType('blocked_by', 'Blocked by', 'Blocked'),
        ];

        $representation = LinksFieldRepresentation::buildRepresentationWithAllowedLinkTypes(
            $link_field,
            $type,
            $permissions,
            $allowed_link_types,
            null
        );

        self::assertEquals(666, $representation->field_id);
        self::assertEquals('The links', $representation->label);
        self::assertEquals('the_link_field', $representation->name);
        self::assertEquals(['read', 'update', 'create'], $representation->permissions);

        self::assertFalse($representation->collapsed);
        self::assertTrue($representation->required);

        self::assertNull($representation->values);
        self::assertNull($representation->default_value);
        self::assertNull($representation->bindings['type']);

        self::assertEmpty($representation->bindings['list']);
        self::assertEmpty($representation->permissions_for_groups);

        self::assertEquals([
            TypeRepresentation::build('_is_child', 'Child', 'Parent'),
            TypeRepresentation::build('duplicate', 'Duplicated by', 'Duplicates'),
            TypeRepresentation::build('blocked_by', 'Blocked by', 'Blocked'),
        ], $representation->allowed_types);
    }
}
