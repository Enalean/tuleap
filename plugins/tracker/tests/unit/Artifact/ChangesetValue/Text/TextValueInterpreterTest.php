<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ChangesetValue\Text;

use Codendi_HTMLPurifier;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Markdown\CommonMarkInterpreter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TextValueInterpreterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CommonMarkInterpreter $commonmark_interpreter;
    private Codendi_HTMLPurifier $purifier;

    protected function setUp(): void
    {
        $this->purifier               = Codendi_HTMLPurifier::instance();
        $this->commonmark_interpreter = CommonMarkInterpreter::build($this->purifier);
    }

    private function interpretValueAccordingToFormat(string $format, string $text_to_interpret): string
    {
        $text_value_interpreter = new TextValueInterpreter(
            $this->purifier,
            $this->commonmark_interpreter
        );

        return $text_value_interpreter->interpretValueAccordingToFormat(
            $format,
            $text_to_interpret,
            101
        );
    }

    public function testItInterpretsHTMLContent(): void
    {
        $result = $this->interpretValueAccordingToFormat(
            Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
            '<p> Montego </p>'
        );

        self::assertSame($this->purifier->purifyHTMLWithReferences('<p> Montego </p>', 101), $result);
    }

    public function testItInterpretsMarkdownContent(): void
    {
        $result = $this->interpretValueAccordingToFormat(
            Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            '**Montego**'
        );
        self::assertSame($this->commonmark_interpreter->getInterpretedContentWithReferences('**Montego**', 101), $result);
    }

    public function testItInterpretsTextContent(): void
    {
        $result = $this->interpretValueAccordingToFormat(
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
            'Montego'
        );
        self::assertSame('Montego', $result);
    }
}
