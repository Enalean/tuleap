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

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer;
use League\CommonMark\MarkdownConverter;
use Tuleap\Markdown\CodeBlockFeaturesInterface;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EnhancedCodeBlockRendererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MarkdownConverter $converter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CodeBlockFeaturesInterface
     */
    private $code_block_features;


    #[\Override]
    protected function setUp(): void
    {
        $this->code_block_features = $this->createMock(CodeBlockFeaturesInterface::class);

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addRenderer(
            FencedCode::class,
            new EnhancedCodeBlockRenderer($this->code_block_features, new FencedCodeRenderer())
        );
        $this->converter = new MarkdownConverter($environment);
    }

    public function testItDoesNotConvertFencedCodesThatAreNotMermaid(): void
    {
        $this->code_block_features
            ->expects($this->never())
            ->method('needsMermaid');
        $this->code_block_features
            ->expects($this->never())
            ->method('needsSyntaxHighlight');

        $result = $this->converter->convert(
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
            $result->getContent()
        );
    }

    public function testItConvertFencedCodeThatIsFlaggedAsMermaid(): void
    {
        $this->code_block_features
            ->expects($this->once())
            ->method('needsMermaid');
        $this->code_block_features
            ->expects($this->never())
            ->method('needsSyntaxHighlight');

        $result = $this->converter->convert(
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
            $result->getContent()
        );
    }

    public function testItNeedsSyntaxHighlightingOnlyForNamedBlocksThatAreNotMermaid(): void
    {
        $this->code_block_features
            ->expects($this->once())
            ->method('needsMermaid');
        $this->code_block_features
            ->expects($this->once())
            ->method('needsSyntaxHighlight');

        $result = $this->converter->convert(
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
            $result->getContent()
        );
    }
}
