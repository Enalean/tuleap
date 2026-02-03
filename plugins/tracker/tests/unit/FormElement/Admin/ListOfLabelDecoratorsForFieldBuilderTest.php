<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Admin;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Semantic\CollectionOfSemanticsUsingAParticularTrackerField;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ListOfLabelDecoratorsForFieldBuilderTest extends TestCase
{
    public function testDecorators(): void
    {
        $builder = new ListOfLabelDecoratorsForFieldBuilder();
        self::assertCount(0, $builder->getLabelDecorators($this->getFormElementWithoutSemanticNorNotifications()));
        self::assertCount(1, $builder->getLabelDecorators($this->getFormElementWithSemanticButWithoutNotifications()));
        self::assertCount(1, $builder->getLabelDecorators($this->getFormElementWithNotificationsButWithoutSemantic()));
        self::assertCount(2, $builder->getLabelDecorators($this->getFormElementWithSemanticAndNotifications()));
    }

    private function getFormElementWithoutSemanticNorNotifications(): TrackerField
    {
        return $this->getFormElement(false, false);
    }

    private function getFormElementWithSemanticButWithoutNotifications(): TrackerField
    {
        return $this->getFormElement(true, false);
    }

    private function getFormElementWithNotificationsButWithoutSemantic(): TrackerField
    {
        return $this->getFormElement(false, true);
    }

    private function getFormElementWithSemanticAndNotifications(): TrackerField
    {
        return $this->getFormElement(true, true);
    }

    private function getFormElement(bool $has_semantic, bool $has_notifications): TrackerField
    {
        $tracker = TrackerTestBuilder::aTracker()->build();

        $form_element = $this->createStub(TextField::class);
        $form_element->method('getId')->willReturn(123);
        $form_element->method('getTracker')->willReturn($tracker);

        $semantics = $has_semantic ? [new TrackerSemanticTitle($tracker, $form_element)] : [];
        $form_element->method('getUsagesInSemantics')->willReturn(
            new CollectionOfSemanticsUsingAParticularTrackerField($form_element, $semantics),
        );
        $form_element->method('hasNotifications')->willReturn($has_notifications);

        return $form_element;
    }
}
