<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\PdfTemplate\Variable;

use Tuleap\Test\PHPUnit\TestCase;

final class VariableMisusageCollectorTest extends TestCase
{
    public function testItReturnsEmptyArrayIfTextDoesNotUseVariables(): void
    {
        $text = 'Lorem ipsum';

        $collector = new VariableMisusageCollector();

        self::assertEmpty($collector->getMisusages($text));
    }

    /**
     * @dataProvider getVariables
     */
    public function testItReturnsEmptyArrayIfTextUsesCorrectlyTheVariables(string $variable): void
    {
        $text = sprintf('Lorem ipsum ${%s}', $variable);

        $collector = new VariableMisusageCollector();

        self::assertEmpty($collector->getMisusages($text));
    }

    /**
     * @dataProvider getVariables
     */
    public function testItReturnsMisusageIfThereIsASpaceAtTheBeginning(string $variable): void
    {
        $bad  = sprintf('${ %s}', $variable);
        $good = sprintf('${%s}', $variable);
        $text = "Lorem ipsum $bad";

        $collector = new VariableMisusageCollector();

        $misusages = $collector->getMisusages($text);
        self::assertCount(1, $misusages);
        self::assertEquals(
            "Syntax error with variable $bad: spaces are not allowed, the variable will not be interpreted. Expected: $good.",
            $misusages[0],
        );
    }

    /**
     * @dataProvider getVariables
     */
    public function testItReturnsMisusageIfThereIsASpaceAtTheEnd(string $variable): void
    {
        $bad  = sprintf('${%s }', $variable);
        $good = sprintf('${%s}', $variable);
        $text = "Lorem ipsum $bad";

        $collector = new VariableMisusageCollector();

        $misusages = $collector->getMisusages($text);
        self::assertCount(1, $misusages);
        self::assertEquals(
            "Syntax error with variable $bad: spaces are not allowed, the variable will not be interpreted. Expected: $good.",
            $misusages[0],
        );
    }

    /**
     * @dataProvider getVariables
     */
    public function testItReturnsMisusageIfTheVariableIsInLowerCase(string $variable): void
    {
        $bad  = sprintf('${%s}', strtolower($variable));
        $good = sprintf('${%s}', $variable);
        $text = "Lorem ipsum $bad";

        $collector = new VariableMisusageCollector();

        $misusages = $collector->getMisusages($text);
        self::assertCount(1, $misusages);
        self::assertEquals(
            "Syntax error with variable $bad, the variable must be in uppercase. Expected: $good.",
            $misusages[0],
        );
    }

    public function testItReturnsMisusageIfThereIsATypoInTheVariable(): void
    {
        $text = 'Lorem ipsum ${DOCUMENTTITLE}';

        $collector = new VariableMisusageCollector();

        $misusages = $collector->getMisusages($text);
        self::assertCount(1, $misusages);
        self::assertEquals(
            'Unknown variable ${DOCUMENTTITLE}, the variable will not be interpreted.',
            $misusages[0],
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function getVariables(): array
    {
        $variables = array_map(
            static fn(Variable $variable) => [$variable->value],
            Variable::cases(),
        );

        return array_combine(
            array_column($variables, 0),
            $variables,
        );
    }
}
