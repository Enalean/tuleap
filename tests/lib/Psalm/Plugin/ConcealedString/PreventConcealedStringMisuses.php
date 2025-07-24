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
use Psalm\CodeLocation;
use Psalm\Plugin\EventHandler\AfterStatementAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterStatementAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;
use Tuleap\Cryptography\ConcealedString;

final class PreventConcealedStringMisuses implements MethodReturnTypeProviderInterface, AfterStatementAnalysisInterface
{
    #[\Override]
    public static function getClassLikeNames(): array
    {
        return [ConcealedString::class];
    }

    #[\Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Type\Union
    {
        if ($event->getMethodNameLowercase() === 'getstring') {
            return new Union([new TUnwrappedConcealedString()]);
        }

        return null;
    }

    #[\Override]
    public static function afterStatementAnalysis(AfterStatementAnalysisEvent $event): ?bool
    {
        $stmt = $event->getStmt();
        if (! $stmt instanceof Stmt\Return_ || $stmt->expr === null) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $return            = $statements_source->getNodeTypeProvider()->getType($stmt->expr);
        if ($return === null) {
            return null;
        }

        $return_types = $return->getAtomicTypes();

        if (! isset($return_types['string']) || ! ($return_types['string'] instanceof TUnwrappedConcealedString)) {
            return null;
        }

        \Psalm\IssueBuffer::accepts(
            new NoReturnUnwrappedConcealedString(
                new CodeLocation($statements_source, $stmt->expr)
            ),
            $statements_source->getSuppressedIssues()
        );
        return null;
    }
}
