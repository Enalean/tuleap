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

namespace Tuleap\REST\Specification\Swagger;

use Tuleap\Event\Dispatchable;

final class SwaggerJsonSecurityDefinitionsCollection implements Dispatchable
{
    public const string NAME = 'retrieveRESTSwaggerJsonSecurityDefinitions';

    public const string TYPE_NAME_ACCESS_KEY = 'api_access_key';
    public const string TYPE_NAME_OAUTH2     = 'oauth2';

    /**
     * @var SwaggerJsonSecurityDefinition[]
     *
     * @psalm-var array<string,SwaggerJsonSecurityDefinition>
     */
    private $security_definitions;

    public function __construct()
    {
        $this->security_definitions = [self::TYPE_NAME_ACCESS_KEY => new SwaggerJsonAPIAccessKey()];
    }

    /**
     * @return SwaggerJsonSecurityDefinition[]
     *
     * @psalm-return array<string,SwaggerJsonSecurityDefinition>
     */
    public function getSecurityDefinitions(): array
    {
        return $this->security_definitions;
    }

    public function addSecurityDefinition(string $name, SwaggerJsonSecurityDefinition $security_definition): void
    {
        $this->security_definitions[$name] = $security_definition;
    }
}
