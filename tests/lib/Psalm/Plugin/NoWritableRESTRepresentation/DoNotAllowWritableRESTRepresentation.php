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

namespace Tuleap\Test\Psalm\Plugin\NoWritableRESTRepresentation;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use Psalm\Codebase;
use Psalm\IssueBuffer;
use Psalm\Plugin\Hook\AfterClassLikeAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

final class DoNotAllowWritableRESTRepresentation implements AfterClassLikeAnalysisInterface
{
    public static function afterStatementAnalysis(
        Node\Stmt\ClassLike $stmt,
        ClassLikeStorage $classlike_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ): void {
        if (! self::mightBeARESTResource($statements_source)) {
            return;
        }

        foreach ($classlike_storage->methods as $method_name => $method_storage) {
            $method = $stmt->getMethod($method_name);
            if (! self::isARESTEndpointMethod($method)) {
                continue;
            }

            foreach ($method_storage->params as $param) {
                if ($param->location !== null && ! self::isTypeSafeToUseAsRESTRepresentation($codebase, $param->type)) {
                    IssueBuffer::accepts(
                        new NoMutableRESTRepresentation($param->location),
                        $statements_source->getSuppressedIssues()
                    );
                }
            }

            $return_type_location = $method_storage->return_type_location ?? $method_storage->signature_return_type_location;
            if ($return_type_location !== null && ! self::isTypeSafeToUseAsRESTRepresentation($codebase, $method_storage->return_type)) {
                IssueBuffer::accepts(
                    new NoMutableRESTRepresentation($return_type_location),
                    $statements_source->getSuppressedIssues()
                );
            }
        }
    }

    private static function mightBeARESTResource(StatementsSource $statements_source): bool
    {
        $namespace = $statements_source->getNamespace();
        return $namespace !== null && \strpos($namespace, '\\REST\\') !== false;
    }

    private static function isARESTEndpointMethod(?ClassMethod $method): bool
    {
        if ($method === null) {
            return false;
        }

        $doc_comment = $method->getDocComment();
        if ($doc_comment === null) {
            return false;
        }

        return \preg_match('/@url\s+\w+/', $doc_comment->getText()) === 1;
    }

    private static function isTypeSafeToUseAsRESTRepresentation(Codebase $codebase, ?Union $type): bool
    {
        if ($type === null) {
            return true;
        }

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TArray) {
                foreach ($atomic_type->type_params as $type_array) {
                    if (! self::isTypeSafeToUseAsRESTRepresentation($codebase, $type_array)) {
                        return false;
                    }
                }
                return true;
            }

            if (! $atomic_type instanceof TNamedObject) {
                continue;
            }

            $atomic_type_id = $atomic_type->getId();

            /**
             * @psalm-suppress InternalMethod We need access to the dependant classlikes of the resource and this
             * seems to be the only to get access to it. Worst case scenario the internal API change and the plugin
             * crashes when we update Psalm, it's not production code anyway ¯\_(ツ)_/¯
             */
            if (! $codebase->classlike_storage_provider->has($atomic_type_id)) {
                // In this situation we cannot determine if the type is safe or not because we cannot get access to its
                // information. Let's consider it safe because it's likely the annotation is incorrect and mutability is
                // not the biggest issue at the moment.
                return true;
            }

            /**
             * @psalm-suppress InternalMethod
             */
            $classlike_storage = $codebase->classlike_storage_provider->get($atomic_type_id);

            if (! $classlike_storage->mutation_free) {
                return false;
            }
        }

        return true;
    }
}
