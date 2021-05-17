<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Markdown\BlockRenderer;

use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Renderer\FencedCodeRenderer;
use League\CommonMark\Environment;
use League\CommonMark\MarkdownConverter;
use Mockery;
use Tuleap\Markdown\CodeBlockFeaturesInterface;

class EnhancedCodeBlockRendererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var MarkdownConverter
     */
    private $converter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CodeBlockFeaturesInterface
     */
    private $code_block_features;

    protected function setUp(): void
    {
        $this->code_block_features = Mockery::mock(CodeBlockFeaturesInterface::class);

        $environment = Environment::createCommonMarkEnvironment();
        $environment->addBlockRenderer(
            FencedCode::class,
            new EnhancedCodeBlockRenderer($this->code_block_features, new FencedCodeRenderer())
        );
        $this->converter = new MarkdownConverter($environment);
    }

    public function testItDoesNotConvertFencedCodesThatAreNotMermaid(): void
    {
        $this->code_block_features
            ->shouldReceive('needsMermaid')
            ->never();
        $this->code_block_features
            ->shouldReceive('needsSyntaxHighlight')
            ->never();

        $result = $this->converter->convertToHtml(
            <<<MARKDOWN
            See code below:

            ```
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

        self::assertEquals(
            <<<EXPECTED_HTML
            <p>See code below:</p>
            <pre><code>class Foo {}
            </code></pre>
            <pre><code>graph TD;
                A--&gt;B;
                A--&gt;C;
                B--&gt;D;
                C--&gt;D;
            </code></pre>\n
            EXPECTED_HTML,
            $result
        );
    }

    public function testItConvertFencedCodeThatIsFlaggedAsMermaid(): void
    {
        $this->code_block_features
            ->shouldReceive('needsMermaid')
            ->once();
        $this->code_block_features
            ->shouldReceive('needsSyntaxHighlight')
            ->never();

        $result = $this->converter->convertToHtml(
            <<<MARKDOWN
            See code below:

            ```
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

        self::assertEquals(
            <<<EXPECTED_HTML
            <p>See code below:</p>
            <pre><code>class Foo {}
            </code></pre>
            <tlp-mermaid-diagram><pre><code class="language-mermaid">graph TD;
                A--&gt;B;
                A--&gt;C;
                B--&gt;D;
                C--&gt;D;
            </code></pre></tlp-mermaid-diagram>\n
            EXPECTED_HTML,
            $result
        );
    }

    public function testItNeedsSyntaxHighlightingOnlyForNamedBlocksThatAreNotMermaid(): void
    {
        $this->code_block_features
            ->shouldReceive('needsMermaid')
            ->once();
        $this->code_block_features
            ->shouldReceive('needsSyntaxHighlight')
            ->once();

        $result = $this->converter->convertToHtml(
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

        self::assertEquals(
            <<<EXPECTED_HTML
            <p>See code below:</p>
            <tlp-syntax-highlighting><pre><code class="language-php">class Foo {}
            </code></pre></tlp-syntax-highlighting>
            <tlp-mermaid-diagram><pre><code class="language-mermaid">graph TD;
                A--&gt;B;
                A--&gt;C;
                B--&gt;D;
                C--&gt;D;
            </code></pre></tlp-mermaid-diagram>\n
            EXPECTED_HTML,
            $result
        );
    }
}
