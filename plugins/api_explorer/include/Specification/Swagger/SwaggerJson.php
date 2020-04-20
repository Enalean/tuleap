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

namespace Tuleap\APIExplorer\Specification\Swagger;

use Tuleap\REST\Specification\Swagger\SwaggerJsonSecurityDefinition;

/**
 * @see https://swagger.io/docs/specification/2-0/
 */
final class SwaggerJson
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $swagger = '2.0';
    /**
     * @var string
     * @psalm-readonly
     */
    public $host;
    /**
     * @var string
     * @psalm-readonly
     */
    public $basePath = '/api';
    /**
     * @var string[]
     * @psalm-readonly
     */
    public $produces;
    /**
     * @var string[]
     * @psalm-readonly
     */
    public $consumes;
    /**
     * @var SwaggerJsonInfo
     * @psalm-readonly
     */
    public $info;
    /**
     * @var array
     * @psalm-readonly
     */
    public $paths;
    /**
     * @var array
     * @psalm-readonly
     */
    public $definitions;
    /**
     * @var array SwaggerJsonSecurityDefinition[]
     * @psalm-var array<string,SwaggerJsonSecurityDefinition>
     * @psalm-readonly
     */
    public $securityDefinitions;

    /**
     * @param SwaggerJsonSecurityDefinition[] $security_definitions
     * @psalm-param array<string,SwaggerJsonSecurityDefinition> $security_definitions
     */
    public function __construct(
        string $host,
        array $produces,
        array $consumes,
        SwaggerJsonInfo $info,
        SwaggerJsonPathsAndDefinitions $paths_and_models,
        array $security_definitions
    ) {
        $this->host                = $host;
        $this->produces            = $produces;
        $this->consumes            = $consumes;
        $this->info                = $info;
        $this->paths               = $paths_and_models->getPaths();
        $this->definitions         = $paths_and_models->getDefinitions();
        $this->securityDefinitions = $security_definitions;
    }
}
