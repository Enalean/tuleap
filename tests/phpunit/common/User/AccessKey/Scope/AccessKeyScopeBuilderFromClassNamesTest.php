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

namespace Tuleap\User\AccessKey\Scope;

use PHPUnit\Framework\TestCase;

final class AccessKeyScopeBuilderFromClassNamesTest extends TestCase
{
    public function testStopLookingForTheAccessKeyScopeAsSoonAsOneItBuilt(): void
    {
        $key_scope_recognize_identifier = new /** @psalm-immutable */ class implements AccessKeyScope
        {
            use AccessKeyScopeThrowOnActualMethodCall;

            /**
             * @psalm-pure
             */
            public static function fromItself(): AccessKeyScope
            {
                return new self();
            }

            /**
             * @psalm-pure
             */
            public static function fromIdentifier(AccessKeyScopeIdentifier $identifier): AccessKeyScope
            {
                return self::fromItself();
            }
        };
        $key_scope_not_supposed_to_be_tried = new /** @psalm-immutable */ class implements AccessKeyScope
        {
            use AccessKeyScopeThrowOnActualMethodCall;

            /**
             * @psalm-pure
             */
            public static function fromItself(): AccessKeyScope
            {
                throw new \LogicException('Not supposed to be built');
            }

            /**
             * @psalm-pure
             */
            public static function fromIdentifier(AccessKeyScopeIdentifier $identifier): AccessKeyScope
            {
                throw new \LogicException('Not supposed to be tried');
            }
        };

        $builder = new AccessKeyScopeBuilderFromClassNames(
            get_class($key_scope_recognize_identifier),
            get_class($key_scope_not_supposed_to_be_tried)
        );

        $this->assertEquals(
            $key_scope_recognize_identifier,
            $builder->buildAccessKeyScopeFromScopeIdentifier(AccessKeyScopeIdentifier::fromIdentifierKey('foo:bar'))
        );
    }

    public function testKeyScopeAttemptToBuildFromAnUnknownIdentifier(): void
    {
        $key_scope = new /** @psalm-immutable */ class implements AccessKeyScope
        {
            use AccessKeyScopeThrowOnActualMethodCall;

            /**
             * @psalm-pure
             */
            public static function fromItself(): AccessKeyScope
            {
                throw new \LogicException('Not supposed to be built');
            }

            /**
             * @psalm-pure
             */
            public static function fromIdentifier(AccessKeyScopeIdentifier $identifier): ?AccessKeyScope
            {
                return null;
            }
        };

        $builder = new AccessKeyScopeBuilderFromClassNames(
            get_class($key_scope)
        );

        $this->assertNull(
            $builder->buildAccessKeyScopeFromScopeIdentifier(AccessKeyScopeIdentifier::fromIdentifierKey('unknown:unknown'))
        );
    }

    public function testBuildAllKnownKeyScopes(): void
    {
        $key_scope_1 = new /** @psalm-immutable */ class implements AccessKeyScope
        {
            use AccessKeyScopeThrowOnActualMethodCall;

            /**
             * @psalm-pure
             */
            public static function fromItself(): AccessKeyScope
            {
                return new self();
            }

            /**
             * @psalm-pure
             */
            public static function fromIdentifier(AccessKeyScopeIdentifier $identifier): ?AccessKeyScope
            {
                return null;
            }
        };
        $key_scope_2 = new /** @psalm-immutable */ class implements AccessKeyScope
        {
            use AccessKeyScopeThrowOnActualMethodCall;

            /**
             * @psalm-pure
             */
            public static function fromItself(): AccessKeyScope
            {
                return new self();
            }

            /**
             * @psalm-pure
             */
            public static function fromIdentifier(AccessKeyScopeIdentifier $identifier): ?AccessKeyScope
            {
                return null;
            }
        };

        $key_scope_1_class_name = get_class($key_scope_1);
        $key_scope_2_class_name = get_class($key_scope_2);

        $this->assertNotEquals($key_scope_1_class_name, $key_scope_2_class_name);

        $builder = new AccessKeyScopeBuilderFromClassNames(
            $key_scope_1_class_name,
            $key_scope_2_class_name
        );

        $all_scope_classnames = [];
        foreach ($builder->buildAllAvailableAccessKeyScopes() as $scope) {
            $all_scope_classnames[] = get_class($scope);
        }

        $this->assertEqualsCanonicalizing(
            [$key_scope_1_class_name, $key_scope_2_class_name],
            $all_scope_classnames
        );
    }
}
