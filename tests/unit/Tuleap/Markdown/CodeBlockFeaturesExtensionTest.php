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

namespace Tuleap\Markdown;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use Mockery;
use PHPUnit\Framework\TestCase;

final class CodeBlockFeaturesExtensionTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var CommonMarkConverter
     */
    private $converter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CodeBlockFeatures
     */
    private $code_block_features;

    protected function setUp(): void
    {
        $this->code_block_features = Mockery::mock(CodeBlockFeatures::class);

        $extension = new CodeBlockFeaturesExtension($this->code_block_features);

        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension($extension);
        $this->converter = new CommonMarkConverter([], $environment);
    }

    public function testItDoesNotNeedMermaid(): void
    {
        $this->code_block_features
            ->shouldReceive('isMermaidNeeded')
            ->andReturn(false);

        $this->code_block_features
            ->shouldReceive('needsMermaid')
            ->never();

        $this->converter->convertToHtml(
            <<<MARKDOWN
            See code below:

            ```php
            class Foo {}
            ```

            ```
            graph TD;
                A-->B;
                A-->C;
                B-->D;
                C-->D;
            ```
            MARKDOWN
        );
    }

    public function testItNeedsMermaid(): void
    {
        $this->code_block_features
            ->shouldReceive('isMermaidNeeded')
            ->andReturn(false);

        $this->code_block_features
            ->shouldReceive('needsMermaid')
            ->once();

        $this->converter->convertToHtml(
            <<<MARKDOWN
            See code below:

            ```php
            class Foo {}
            ```

            ```mermaid
            graph TD;
                A-->B;
                A-->C;
                B-->D;
                C-->D;
            ```
            MARKDOWN
        );
    }

    public function testItDoesNotOverwritePreviousResultOnConsecutiveExecution(): void
    {
        $this->code_block_features
            ->shouldReceive('isMermaidNeeded')
            ->andReturn(true);

        $this->code_block_features
            ->shouldReceive('needsMermaid')
            ->never();

        $this->converter->convertToHtml(
            <<<MARKDOWN
            See code below:

            ```php
            class Foo {}
            ```

            ```mermaid
            graph TD;
                A-->B;
                A-->C;
                B-->D;
                C-->D;
            ```
            MARKDOWN
        );
    }
}
