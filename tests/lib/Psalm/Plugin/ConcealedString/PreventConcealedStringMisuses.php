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

namespace Tuleap\Test\Psalm\Plugin\ConcealedString;

use PhpParser\Node\Stmt;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\AfterStatementAnalysisInterface;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type\Union;
use Tuleap\Cryptography\ConcealedString;

final class PreventConcealedStringMisuses implements MethodReturnTypeProviderInterface, AfterStatementAnalysisInterface
{
    public static function getClassLikeNames(): array
    {
        return [ConcealedString::class];
    }

    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        ?array $template_type_parameters = null,
        ?string $called_fq_classlike_name = null,
        ?string $called_method_name_lowercase = null
    ) {
        if ($method_name_lowercase === 'getstring') {
            return new Union([new TUnwrappedConcealedString()]);
        }

        return null;
    }

    public static function afterStatementAnalysis(
        Stmt $stmt,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ) {
        if (! $stmt instanceof Stmt\Return_ || $stmt->expr === null) {
            return;
        }

        $return = $statements_source->getNodeTypeProvider()->getType($stmt->expr);
        if ($return === null) {
            return;
        }

        $return_types = $return->getAtomicTypes();

        if (! isset($return_types['string']) || ! ($return_types['string'] instanceof TUnwrappedConcealedString)) {
            return;
        }

        \Psalm\IssueBuffer::accepts(
            new NoReturnUnwrappedConcealedString(
                new CodeLocation($statements_source, $stmt->expr)
            ),
            $statements_source->getSuppressedIssues()
        );
    }
}
