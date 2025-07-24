<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace TuleapDev\Rector;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Tuleap\TemporaryTestDirectory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ConvertAddHookToAttributesRuleTest extends AbstractRectorTestCase
{
    use TemporaryTestDirectory;

    private array $globals = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->globals = $GLOBALS;
        parent::setUp();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ($GLOBALS['__composer_autoload_files'] as $autoload_file => $nope) {
            if (! isset($this->globals['__composer_autoload_files'][$autoload_file])) {
                unset($GLOBALS['__composer_autoload_files'][$autoload_file]);
            }
        }
        unset($GLOBALS['_ENV']['SHELL_VERBOSITY']);
        unset($GLOBALS['_SERVER']['SHELL_VERBOSITY']);
    }

    #[RunInSeparateProcess]
    public function test(): void
    {
        copy(__DIR__ . '/_fixtures/plugin-with-add-hook-calls.php.inc', $this->getTmpDir() . '/plugin-with-add-hook-calls.php.inc');
        $this->doTestFile($this->getTmpDir() . '/plugin-with-add-hook-calls.php.inc');
    }

    #[\Override]
    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
