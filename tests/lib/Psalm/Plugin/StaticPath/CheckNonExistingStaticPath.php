<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

namespace Tuleap\Test\Psalm\Plugin\StaticPath;

use Override;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\String_;
use Psalm\CodeLocation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use function Psl\Filesystem\exists;
use function Psl\Str\slice;

final class CheckNonExistingStaticPath implements AfterExpressionAnalysisInterface
{
    #[Override]
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();
        if (! $expr instanceof Concat) {
            return null;
        }

        if (! $expr->left instanceof Dir) {
            return null;
        }

        $right = $expr->right;
        if (! $right instanceof String_) {
            return null;
        }

        $full_path = dirname($event->getStatementsSource()->getFilePath()) . $right->value;
        if (str_contains($full_path, '*')) {
            // Remove everything after first *
            $full_path = slice($full_path, 0, strpos($full_path, '*'));
        }
        if (exists($full_path)) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        IssueBuffer::accepts(
            new NonExistingStaticPath(new CodeLocation($statements_source, $expr)),
            $statements_source->getSuppressedIssues(),
        );
        return null;
    }
}
