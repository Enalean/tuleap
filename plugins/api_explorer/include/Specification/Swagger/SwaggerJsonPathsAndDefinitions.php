<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 * Copyright (c) Luracast, 2010.
 *
 * This code has been extracted from Luracast\Restler 3.0.0RC6
 * in the class Luracast\Restler\Explorer\v2\Explorer.
 * Original license was LGPL-2.1.
 * See https://www.luracast.com/products/restler/ and https://github.com/Luracast/Restler
 *
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

use Luracast\Restler\Data\ValidationInfo;
use Luracast\Restler\Routes;
use Luracast\Restler\Util;
use stdClass;
use Tuleap\REST\Specification\Swagger\SwaggerJsonSecurityDefinition;
use Tuleap\REST\Specification\Swagger\SwaggerJsonSecurityDefinitionsCollection;

final class SwaggerJsonPathsAndDefinitions
{
    private const EXCLUDED_HTTP_METHOD = ['OPTIONS'];

    private const DATA_TYPE_ALIAS = [
        'int'       => 'integer',
        'number'    => 'number',
        'float'     => ['number', 'float'],
        'bool'      => 'boolean',
        'array'     => 'array',
        'stdClass'  => 'object',
        'mixed'     => 'string',
        'date'      => ['string', 'date'],
        'datetime'  => ['string', 'date-time'],
        'time'      => 'string',
        'timestamp' => 'string',
    ];

    private const API_DESCRIPTION_SUFFIX_SYMBOLS = [
        0 => ' 🔓', //'&nbsp; <i class="fa fa-lg fa-unlock-alt"></i>', //public api
        1 => ' ◑', //'&nbsp; <i class="fa fa-lg fa-adjust"></i>', //hybrid api
        2 => ' 🔐', //'&nbsp; <i class="fa fa-lg fa-lock"></i>', //protected api
    ];

    private const PREFIXES = [
        'get'    => 'retrieve',
        'index'  => 'list',
        'post'   => 'create',
        'put'    => 'update',
        'patch'  => 'modify',
        'delete' => 'remove',
    ];

    /**
     * @var array
     */
    private $paths;
    /**
     * @var array
     */
    private $models = [];
    /**
     * @var array
     */
    private $security_definitions;
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $html_purifier;

    /**
     * @param SwaggerJsonSecurityDefinition[] $security_definitions
     * @psalm-param array<string,SwaggerJsonSecurityDefinition> $security_definitions
     */
    public function __construct(int $api_version, array $security_definitions, \Codendi_HTMLPurifier $html_purifier)
    {
        $this->security_definitions = $security_definitions;
        $this->html_purifier        = $html_purifier;
        $this->paths                = $this->paths($api_version);
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function getDefinitions(): array
    {
        return $this->models;
    }

    private function paths(int $version): array
    {
        $map = Routes::findAll([], self::EXCLUDED_HTTP_METHOD, $version);
        $paths = [];
        foreach ($map as $path => $data) {
            foreach ($data as $item) {
                $route = $item['route'];
                $url = $route['url'];
                $paths["/$url"][strtolower($route['httpMethod'])] = $this->operation($route);
            }
        }

        return $paths;
    }

    private function operation(array $route): stdClass
    {
        $r = new stdClass();
        $m = $route['metadata'];
        $r->operationId = $this->operationId($route);
        $base = strtok($route['url'], '/');
        if (empty($base)) {
            $base = 'root';
        }
        $r->tags = [$base];
        $r->parameters = $this->parameters($route);

        $r->summary  = $m['description'] ?? '';
        $r->summary .= $route['accessLevel'] > 2 ? self::API_DESCRIPTION_SUFFIX_SYMBOLS[2] : self::API_DESCRIPTION_SUFFIX_SYMBOLS[$route['accessLevel']];
        $r->description = $m['longDescription'] ?? '';
        $r->responses   = $this->responses($route);
        //TODO: avoid hard coding. Properly detect security
        if ($route['accessLevel']) {
            $r->security = [[SwaggerJsonSecurityDefinitionsCollection::TYPE_NAME_ACCESS_KEY => []]];
            if (isset($m['oauth2-scope'], $this->security_definitions[SwaggerJsonSecurityDefinitionsCollection::TYPE_NAME_OAUTH2])) {
                $r->security[] = [SwaggerJsonSecurityDefinitionsCollection::TYPE_NAME_OAUTH2 => [$m['oauth2-scope']]];

                $oauth2_token_description = sprintf(
                    "%s\n\n\nThis endpoint can be accessed with an OAuth 2.0 access token with the scope <strong>%s</strong>.",
                    $r->description,
                    $this->html_purifier->purify($m['oauth2-scope'])
                );
                $r->description = trim($oauth2_token_description);
            }
        }

        return $r;
    }

    private function parameters(array $route): array
    {
        $r = [];
        $children = [];
        $required = false;
        foreach ($route['metadata']['param'] as $param) {
            $info = new ValidationInfo($param);
            $description = $param['description'] ?? '';
            if ('body' == $info->from) {
                if ($info->required) {
                    $required = true;
                }
                $param['description'] = $description;
                $children[] = $param;
            } else {
                $r[] = $this->parameter($info, $description);
            }
        }
        if (! empty($children)) {
            if (
                1 === count($children) &&
                ! empty($children[0]['children'])
            ) {
                $firstChild = $children[0];
                $description = ''; //'<section class="body-param">';
                foreach ($firstChild['children'] as $child) {
                    $description .= isset($child['required']) && $child['required']
                        ? '**' . $child['name'] . '** (required)  ' . PHP_EOL
                        : $child['name'] . '  ' . PHP_EOL;
                }
                $r[] = $this->parameter(new ValidationInfo($firstChild), $description);
            } else {
                $description = '';
                foreach ($children as $child) {
                    $description .= isset($child['required']) && $child['required']
                        ? '**' . $child['name'] . '** (required)  ' . PHP_EOL
                        : $child['name'] . '  ' . PHP_EOL;
                }

                //lets group all body parameters under a generated model name
                $name = $this->modelName($route);
                $r[] = $this->parameter(
                    new ValidationInfo(
                        [
                           'name'     => $name,
                           'type'     => $name,
                           'from'     => 'body',
                           'required' => $required,
                           'children' => $children
                        ]
                    ),
                    $description
                );
            }
        }

        return $r;
    }

    private function parameter(ValidationInfo $info, string $description = ''): stdClass
    {
        $p = new stdClass();
        $p->name = $info->name;
        $this->setType($p, $info);
        if (empty($info->children) || $info->type != 'array') {
            //primitives
            if ($info->default) {
                $p->default = $info->default;
            }
            if ($info->choice) {
                $p->enum = $info->choice;
            }
            if ($info->min) {
                $p->minimum = $info->min;
            }
            if ($info->max) {
                $p->maximum = $info->max;
            }
            //TODO: $p->items and $p->uniqueItems boolean
        }
        $p->description = $description;
        $p->in = $info->from;
        $p->required = $info->required;

        //$p->allowMultiple = false;

        if (isset($p->{'$ref'})) {
            $p->schema = (object) ['$ref' => ($p->{'$ref'})];
            unset($p->{'$ref'});
        }

        return $p;
    }

    private function responses(array $route): array
    {
        $code = '200';
        $r = [
            $code => (object) [
                'description' => 'Success',
                'schema'      => new stdClass()
            ]
        ];
        $return = Util::nestedValue($route, ['metadata', 'return']);
        if (! empty($return)) {
            $this->setType($r[$code]->schema, new ValidationInfo($return));
        }

        if (is_array($throws = Util::nestedValue($route, ['metadata', 'throws']))) {
            foreach ($throws as $message) {
                $r[$message['code']] = ['description' => $message['message']];
            }
        }

        return $r;
    }

    private function model(string $type, array $children): stdClass
    {
        if (isset($this->models[$type])) {
            return $this->models[$type];
        }
        $r = new stdClass();
        $r->properties = [];
        $required = [];
        foreach ($children as $child) {
            $info = new ValidationInfo($child);
            $p = new stdClass();
            $this->setType($p, $info);
            $p->description = isset($child['description']) ? $child['description'] : '';
            if ($info->default) {
                $p->defaultValue = $info->default;
            }
            if ($info->choice) {
                $p->enum = $info->choice;
            }
            if ($info->min) {
                $p->minimum = $info->min;
            }
            if ($info->max) {
                $p->maximum = $info->max;
            }
            if ($info->required) {
                $required[] = $info->name;
            }
            $r->properties[$info->name] = $p;
        }
        if (! empty($required)) {
            $r->required = $required;
        }
        //TODO: add $r->subTypes https://github.com/wordnik/swagger-spec/blob/master/versions/1.2.md#527-model-object
        //TODO: add $r->discriminator https://github.com/wordnik/swagger-spec/blob/master/versions/1.2.md#527-model-object
        $this->models[$type] = $r;

        return $r;
    }

    private function setType(object $object, ValidationInfo $info): void
    {
        //TODO: proper type management
        $type = Util::getShortName($info->type);
        if ($info->type == 'array') {
            $object->type = 'array';
            if ($info->children) {
                $contentType = Util::getShortName($info->contentType);
                $object->items = (object) [
                    '$ref' => "#/definitions/$contentType"
                ];
            } elseif ($info->contentType && $info->contentType == 'associative') {
                unset($info->contentType);
                $this->model($info->type = 'Object', [
                    [
                        'name'        => 'property',
                        'type'        => 'string',
                        'default'     => '',
                        'required'    => false,
                        'description' => ''
                    ]
                ]);
            } elseif ($info->contentType && $info->contentType != 'indexed') {
                if (is_string($info->contentType) && $t = Util::nestedValue(self::DATA_TYPE_ALIAS, strtolower($info->contentType))) {
                    if (is_array($t)) {
                        $object->items = (object) [
                            'type'   => $t[0],
                            'format' => $t[1],
                        ];
                    } else {
                        $object->items = (object) [
                            'type' => $t,
                        ];
                    }
                } else {
                    $contentType = Util::getShortName($info->contentType);
                    $object->items = (object) [
                        '$ref' => "#/definitions/$contentType"
                    ];
                }
            } else {
                $object->items = (object) [
                    'type' => 'string'
                ];
            }
        } elseif ($info->children) {
            $this->model($type, $info->children);
            $object->{'$ref'} = "#/definitions/$type";
        } elseif (is_string($info->type) && $t = Util::nestedValue(self::DATA_TYPE_ALIAS, strtolower($info->type))) {
            if (is_array($t)) {
                $object->type = $t[0];
                $object->format = $t[1];
            } else {
                $object->type = $t;
            }
        } else {
            $object->type = 'string';
        }
        $has64bit = PHP_INT_MAX > 2147483647;
        if (isset($object->type)) {
            if ($object->type == 'integer') {
                $object->format = $has64bit
                    ? 'int64'
                    : 'int32';
            } elseif ($object->type == 'number') {
                $object->format = $has64bit
                    ? 'double'
                    : 'float';
            }
        }
    }

    private function operationId(array $route): string
    {
        static $hash = [];
        $id = $route['httpMethod'] . ' ' . $route['url'];
        if (isset($hash[$id])) {
            return $hash[$id];
        }
        $class = Util::getShortName($route['className']);
        $method = $route['methodName'];

        if (isset(self::PREFIXES[$method])) {
            $method = self::PREFIXES[$method] . $class;
        } else {
            $method = str_replace(
                array_keys(self::PREFIXES),
                array_values(self::PREFIXES),
                $method
            );
            $method = lcfirst($class) . ucfirst($method);
        }
        $hash[$id] = $method;

        return $method;
    }

    private function modelName(array $route): string
    {
        return $this->operationId($route) . 'Model';
    }
}
