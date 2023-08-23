<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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

final class Codendi_HTMLPurifierTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private function getHTMLPurifier()
    {
        return new class extends Codendi_HTMLPurifier {
            public function __construct()
            {
            }
        };
    }

    public function testPurifySimple()
    {
        $p = $this->getHTMLPurifier();
        $this->assertEquals('&lt;script&gt;alert(1);&lt;/script&gt;', $p->purify('<script>alert(1);</script>'));
    }

    public function testStripLightForbidden(): void
    {
        $p   = \Mockery::mock(\Codendi_HTMLPurifier::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $rm  = $this->createMock(\ReferenceManager::class);
        $val = 'bugtest #123';
        $rm->expects(self::once())->method('insertReferences')->willReturn($val);
        $p->shouldReceive('getReferenceManager')->andReturns($rm);

        $this->assertEquals('', $p->purify('<script>alert(1);</script>', CODENDI_PURIFIER_LIGHT));
        $this->assertEquals('Bolded', $p->purify('<s>Bolded</s>', CODENDI_PURIFIER_LIGHT));
        $this->assertEquals($val, $p->purify('bugtest #123', CODENDI_PURIFIER_LIGHT, 102));
        $this->assertEquals('', $p->purify('<form name="test" method="post" action="?"><input type="submit" /></form>', CODENDI_PURIFIER_LIGHT));
        $this->assertEquals('<a href="ftp://test.com">ftp://test.com</a>', $p->purify('ftp://test.com', CODENDI_PURIFIER_LIGHT));
        $this->anchorJsInjection(CODENDI_PURIFIER_LIGHT);
    }

    private function anchorJsInjection($level)
    {
        $p = $this->getHTMLPurifier();
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onblur="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onclick="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" ondbclick="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onfocus="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onkeydown="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onkeypress="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onkeyup="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmousedown="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmousemove="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmouseout="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmouseover="evil">Text</a>', $level));
        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmouseup="evil">Text</a>', $level));
    }

    public function testStripLightAllowed()
    {
        $p = $this->getHTMLPurifier();

        $this->assertEquals('<p>Text</p>', $p->purify('<p>Text</p>', CODENDI_PURIFIER_LIGHT));
        $this->assertEquals('Text<br />', $p->purify('Text<br />', CODENDI_PURIFIER_LIGHT));

        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net">Text</a>', CODENDI_PURIFIER_LIGHT));

        $this->assertEquals('<strong>Text</strong>', $p->purify('<strong>Text</strong>', CODENDI_PURIFIER_LIGHT));
    }

    public function testStripLightTidy()
    {
        $p = $this->getHTMLPurifier();
        $this->assertEquals('<p>Text</p>', $p->purify('<p>Text', CODENDI_PURIFIER_LIGHT));
        $this->assertEquals('Text<br />', $p->purify('Text<br>', CODENDI_PURIFIER_LIGHT));

        $this->assertEquals('<a href="http://php.net">Text</a>', $p->purify('<a href=\'http://php.net\'>Text', CODENDI_PURIFIER_LIGHT));
    }

    public function testPurifyArraySimple()
    {
        $p = $this->getHTMLPurifier();

        $vRef    = ['<script>alert(1);</script>',
            'toto',
            '<h1>title</h1>',
            '<b>bold</b>',
        ];
        $vExpect = ['&lt;script&gt;alert(1);&lt;/script&gt;',
            'toto',
            '&lt;h1&gt;title&lt;/h1&gt;',
            '&lt;b&gt;bold&lt;/b&gt;',
        ];
        $this->assertSame($vExpect, $p->purifyMap($vRef));
    }

    public function testSingleton()
    {
        $p1 = Codendi_HTMLPurifier::instance();
        $p2 = Codendi_HTMLPurifier::instance();
        $this->assertSame($p1, $p2);
        $this->assertInstanceOf(Codendi_HTMLPurifier::class, $p1);
    }

    public function testPurifyJsQuoteAndDQuote()
    {
        $p = Codendi_HTMLPurifier::instance();
        $this->assertEquals('\u003C\/script\u003E', $p->purify('</script>', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEquals('a\u0022a', $p->purify('a"a', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEquals('\u0022a', $p->purify('"a', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEquals('a\u0022', $p->purify('a"', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEquals('\u0022', $p->purify('"', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEquals('\"', $p->purify('"', CODENDI_PURIFIER_JS_QUOTE));
        $this->assertEquals(
            '\u003C\/script\u003E\\\nbla bla\\\n\u003C\/script\u003E\\\nbla bla\\\n\u003C\/script\u003E',
            $p->purify('</script>\nbla bla\n</script>\nbla bla\n</script>', CODENDI_PURIFIER_JS_DQUOTE)
        );
        $this->assertEquals('\u003C\/script\u003E', $p->purify('</script>', CODENDI_PURIFIER_JS_QUOTE));
        $this->assertEquals('100', $p->purify(100, CODENDI_PURIFIER_JS_QUOTE));
    }

    public function testBasicNobr()
    {
        $p = $this->getHTMLPurifier();
        $this->assertEquals("a<br />\nb", $p->purify("a\nb", CODENDI_PURIFIER_BASIC));
        $this->assertEquals("a\nb", $p->purify("a\nb", CODENDI_PURIFIER_BASIC_NOBR));
    }

    public function testMakeLinks()
    {
        $p = \Mockery::mock(\Codendi_HTMLPurifier::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertEquals('', $p->purify('', CODENDI_PURIFIER_BASIC));
        $this->assertEquals('<a href="https://www.example.com">https://www.example.com</a>', $p->purify('https://www.example.com', CODENDI_PURIFIER_BASIC));
        $this->assertEquals('"<a href="https://www.example.com">https://www.example.com</a>"', $p->purify('"https://www.example.com"', CODENDI_PURIFIER_BASIC));
        $this->assertEquals('\'<a href="https://www.example.com">https://www.example.com</a>\'', $p->purify('\'https://www.example.com\'', CODENDI_PURIFIER_BASIC));
        $this->assertEquals('&lt;<a href="https://www.example.com">https://www.example.com</a>&gt;', $p->purify('<https://www.example.com>', CODENDI_PURIFIER_BASIC));
        $this->assertEquals('<a href="mailto:john.doe@example.com">john.doe@example.com</a>', $p->purify('john.doe@example.com', CODENDI_PURIFIER_BASIC));
        $this->assertEquals('"<a href="mailto:john.doe@example.com">john.doe@example.com</a>"', $p->purify('"john.doe@example.com"', CODENDI_PURIFIER_BASIC));
        $this->assertEquals('\'<a href="mailto:john.doe@example.com">john.doe@example.com</a>\'', $p->purify('\'john.doe@example.com\'', CODENDI_PURIFIER_BASIC));
        $this->assertEquals('&lt;<a href="mailto:john.doe@example.com">john.doe@example.com</a>&gt;', $p->purify('<john.doe@example.com>', CODENDI_PURIFIER_BASIC));
        $this->assertEquals('<a href="ssh://gitolite@example.com/tuleap/stable.git">ssh://gitolite@example.com/tuleap/stable.git</a>', $p->purify('ssh://gitolite@example.com/tuleap/stable.git', CODENDI_PURIFIER_BASIC));
        self::assertEquals('<a href="https://sub_domain.example.com">https://sub_domain.example.com</a>', $p->purify('https://sub_domain.example.com', CODENDI_PURIFIER_BASIC));
        $reference_manager = Mockery::mock(ReferenceManager::class);
        $reference_manager->shouldReceive('insertReferences')->withArgs(function (&$data, $group_id) {
            $data = preg_replace('/art #1/', '<a href="link-to-art-1">art #1</a>', $data);
            return true;
        });
        $p->shouldReceive('getReferenceManager')->andReturns($reference_manager);
        $this->assertStringContainsString('link-to-art-1', $p->purify('art #1', CODENDI_PURIFIER_BASIC, 1));
    }

    public function testPurifierLight()
    {
        $p = Codendi_HTMLPurifier::instance();
        $this->assertEquals("foo\nbar", $p->purify("foo\nbar", CODENDI_PURIFIER_LIGHT));
        $this->assertEquals("foo\nbar", $p->purify("foo\r\nbar", CODENDI_PURIFIER_LIGHT));
    }

    public function testItDoesNotDoubleEscapeLinks()
    {
        $reference_manager = Mockery::mock(ReferenceManager::class);
        $reference_manager->shouldReceive('insertReferences')->withArgs(function (&$data, $group_id) {
            $data = preg_replace('/art #1/', '<a href="link-to-art-1">art #1</a>', $data);
            return true;
        });
        $p = \Mockery::mock(\Codendi_HTMLPurifier::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('getReferenceManager')->andReturns($reference_manager);

        $html     = 'Text with <a href="http://tuleap.net/">link</a> and a reference to art #1';
        $expected = 'Text with <a href="http://tuleap.net/">link</a> and a reference to <a href="link-to-art-1">art #1</a>';

        $this->assertEquals($expected, $p->purifyHTMLWithReferences($html, 123));
    }

    public function testItAllowsMermaidCustomElementOnlyForFullConfig(): void
    {
        $p = $this->getHTMLPurifier();
        self::assertEquals(
            '<tlp-mermaid-diagram><pre>Foo</pre></tlp-mermaid-diagram>',
            $p->purify('<tlp-mermaid-diagram><pre>Foo</pre></tlp-mermaid-diagram>', Codendi_HTMLPurifier::CONFIG_FULL)
        );
        self::assertEquals(
            '<tlp-mermaid-diagram><pre><code>Foo</code></pre></tlp-mermaid-diagram>',
            $p->purify('<tlp-mermaid-diagram><pre><code>Foo</code></pre></tlp-mermaid-diagram>', Codendi_HTMLPurifier::CONFIG_FULL)
        );
        self::assertEquals(
            '<tlp-mermaid-diagram class="whatever"><pre>Foo</pre></tlp-mermaid-diagram>',
            $p->purify('<tlp-mermaid-diagram class="whatever"><pre>Foo</pre></tlp-mermaid-diagram>', Codendi_HTMLPurifier::CONFIG_FULL)
        );
        self::assertEquals(
            '<tlp-mermaid-diagram><pre><code>Foo</code></pre></tlp-mermaid-diagram><b>Bar</b>',
            $p->purify('<tlp-mermaid-diagram><pre><code>Foo</code></pre><b>Bar</b></tlp-mermaid-diagram>', Codendi_HTMLPurifier::CONFIG_FULL)
        );
        self::assertEquals(
            '<pre>Foo</pre>',
            $p->purify('<tlp-mermaid-diagram><pre>Foo</pre></tlp-mermaid-diagram>', Codendi_HTMLPurifier::CONFIG_LIGHT)
        );
    }

    public function testItAllowsSyntaxHighlightingCustomElementOnlyForFullConfig(): void
    {
        $p = $this->getHTMLPurifier();
        self::assertEquals(
            '<tlp-syntax-highlighting><pre>Foo</pre></tlp-syntax-highlighting>',
            $p->purify('<tlp-syntax-highlighting><pre>Foo</pre></tlp-syntax-highlighting>', Codendi_HTMLPurifier::CONFIG_FULL)
        );
        self::assertEquals(
            '<tlp-syntax-highlighting><pre><code>Foo</code></pre></tlp-syntax-highlighting>',
            $p->purify('<tlp-syntax-highlighting><pre><code>Foo</code></pre></tlp-syntax-highlighting>', Codendi_HTMLPurifier::CONFIG_FULL)
        );
        self::assertEquals(
            '<tlp-syntax-highlighting class="whatever"><pre>Foo</pre></tlp-syntax-highlighting>',
            $p->purify('<tlp-syntax-highlighting class="whatever"><pre>Foo</pre></tlp-syntax-highlighting>', Codendi_HTMLPurifier::CONFIG_FULL)
        );
        self::assertEquals(
            '<tlp-syntax-highlighting><pre><code>Foo</code></pre></tlp-syntax-highlighting><b>Bar</b>',
            $p->purify('<tlp-syntax-highlighting><pre><code>Foo</code></pre><b>Bar</b></tlp-syntax-highlighting>', Codendi_HTMLPurifier::CONFIG_FULL)
        );
        self::assertEquals(
            '<pre>Foo</pre>',
            $p->purify('<tlp-syntax-highlighting><pre>Foo</pre></tlp-syntax-highlighting>', Codendi_HTMLPurifier::CONFIG_LIGHT)
        );
    }
}
