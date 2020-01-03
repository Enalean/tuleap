<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

/**
 * @psalm-immutable
 */
final class RESTAccessKeyScope implements AccessKeyScope
{
    public const IDENTIFIER_KEY = 'write:rest';

    /**
     * @var AccessKeyScopeIdentifier
     */
    private $identifier;
    /**
     * @var AccessKeyScopeDefinition
     */
    private $definition;

    private function __construct(AccessKeyScopeIdentifier $identifier)
    {
        $this->identifier = $identifier;
        $this->definition = new /** @psalm-immutable */ class implements AccessKeyScopeDefinition
        {
            public function getName(): string
            {
                return _('REST');
            }

            public function getDescription(): string
            {
                return _('Access to the REST API');
            }
        };
    }

    /**
     * @psalm-pure
     */
    public static function fromIdentifier(AccessKeyScopeIdentifier $identifier): AccessKeyScope
    {
        $identifier_string = $identifier->toString();
        if ($identifier->toString() !== self::IDENTIFIER_KEY) {
            throw new \LogicException('Only ' . self::IDENTIFIER_KEY . ' is allowed, got ' . $identifier_string);
        }

        return new self($identifier);
    }

    public function getIdentifier(): AccessKeyScopeIdentifier
    {
        return $this->identifier;
    }

    public function getDefinition(): AccessKeyScopeDefinition
    {
        return $this->definition;
    }
}
