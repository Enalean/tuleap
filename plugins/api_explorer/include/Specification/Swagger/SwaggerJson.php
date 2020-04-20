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

/**
 * @psalm-immutable
 *
 * @see https://swagger.io/docs/specification/2-0/
 */
final class SwaggerJson
{
    /**
     * @var string
     */
    public $swagger = '2.0';
    /**
     * @var string
     */
    public $host;
    /**
     * @var string
     */
    public $basePath = '/api';
    /**
     * @var string[]
     */
    public $produces;
    /**
     * @var string[]
     */
    public $consumes;
    /**
     * @var SwaggerJsonInfo
     */
    public $info;
    /**
     * @var array
     */
    public $paths;
    /**
     * @var array
     */
    public $definitions;
    /**
     * @var array
     * @psalm-var array<string,object>
     */
    public $securityDefinitions;

    /**
     * @psalm-param array<string,object> $security_definitions
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
