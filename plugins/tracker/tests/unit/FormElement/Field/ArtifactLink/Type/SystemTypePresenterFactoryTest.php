<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemTypePresenterFactoryTest extends TestCase
{
    private \EventManager&MockObject $event_manager;
    private SystemTypePresenterBuilder $factory;

    #[Override]
    protected function setUp(): void
    {
        $this->event_manager = $this->createMock(\EventManager::class);
        $this->factory       = new SystemTypePresenterBuilder($this->event_manager);
    }

    public function testItReturnsNullWhenShortnameIsNull(): void
    {
        $presenter = $this->factory->getSystemTypeFromShortname(null);

        self::assertInstanceOf(TypePresenter::class, $presenter);
        self::assertSame('', $presenter->shortname);
        self::assertFalse($presenter->is_system);
    }

    public function testItReturnsTypeIsLinkedToPresenterForTypeIsLinkedToShortname(): void
    {
        $presenter = $this->factory->getSystemTypeFromShortname(ArtifactLinkField::DEFAULT_LINK_TYPE);

        self::assertInstanceOf(DefaultLinkTypePresenter::class, $presenter);
        self::assertSame('', $presenter->shortname);
        self::assertTrue($presenter->is_system);
    }

    public function testItReturnsTypeIsChildPresenterForTypeIsChildShortname(): void
    {
        $presenter = $this->factory->getSystemTypeFromShortname(ArtifactLinkField::TYPE_IS_CHILD);

        self::assertInstanceOf(TypeIsChildPresenter::class, $presenter);
        self::assertSame(ArtifactLinkField::TYPE_IS_CHILD, $presenter->shortname);
        self::assertTrue($presenter->is_system);
    }

    public function testItReturnsTypePresenterDefinedByPlugin(): void
    {
        $event_manager = $this->createMock(\EventManager::class);
        $event_manager
            ->expects($this->once())
            ->method('processEvent')
            ->with('event_get_type_presenter', self::callback(function (array &$params) {
                $params['presenter'] = new TypePresenter('_mirrored_milestone', 'Mirror of', 'Mirrored by', false);
                return true;
            }));


        $factory = new SystemTypePresenterBuilder($event_manager);

        $presenter = $factory->getSystemTypeFromShortname('custom');

        self::assertInstanceOf(TypePresenter::class, $presenter);
        self::assertSame('_mirrored_milestone', $presenter->shortname);
        self::assertSame('Mirror of', $presenter->forward_label);
        self::assertSame('Mirrored by', $presenter->reverse_label);
        self::assertFalse($presenter->is_visible);
    }
}
