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

namespace Tuleap\Git\User\AccessKey\Scope;

use Tuleap\User\AccessKey\Scope\AccessKeyScope;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDefinition;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeIdentifier;

/**
 * @psalm-immutable
 */
final class GitRepositoryAccessKeyScope implements AccessKeyScope
{
    private const IDENTIFIER_KEY = 'write:git_repository';

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
                return dgettext('tuleap-git', 'Git repository');
            }

            public function getDescription(): string
            {
                return dgettext('tuleap-git', 'Access to Git repositories');
            }
        };
    }

    /**
     * @psalm-pure
     */
    public static function fromItself(): AccessKeyScope
    {
        return new self(
            AccessKeyScopeIdentifier::fromIdentifierKey(self::IDENTIFIER_KEY)
        );
    }

    /**
     * @psalm-pure
     */
    public static function fromIdentifier(AccessKeyScopeIdentifier $identifier): ?AccessKeyScope
    {
        if ($identifier->toString() !== self::IDENTIFIER_KEY) {
            return null;
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

    public function covers(AccessKeyScope $scope): bool
    {
        return self::IDENTIFIER_KEY === $scope->getIdentifier()->toString();
    }
}
