<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\TextDiff;

use Codendi_UnifiedDiffFormatter;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DiffProcessorTest extends TestCase
{
    private DiffProcessor $diff_processor;
    private Tracker_Artifact_Changeset $changeset;
    private TrackerField $field;

    #[\Override]
    protected function setUp(): void
    {
        $this->field     = TextFieldBuilder::aTextField(145)->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(15)->build();

        $this->diff_processor = new DiffProcessor(new Codendi_UnifiedDiffFormatter());
    }

    public function testTextDiff(): void
    {
        $next = new Tracker_Artifact_ChangesetValue_Text(
            111,
            $this->changeset,
            $this->field,
            false,
            'Problems during <ins> installation',
            'text'
        );

        $previous = new Tracker_Artifact_ChangesetValue_Text(
            112,
            $this->changeset,
            $this->field,
            false,
            'FullTextSearch does not work on Wiki pages',
            'text'
        );

        self::assertStringContainsString(
            '- FullTextSearch does not work on Wiki pages',
            $this->diff_processor->processDiff($next, $previous, 'text')
        );
        self::assertStringContainsString(
            '+ Problems during <ins> installation',
            $this->diff_processor->processDiff($next, $previous, 'text')
        );

        self::assertStringContainsString(
            '+ FullTextSearch does not work on Wiki pages',
            $this->diff_processor->processDiff($previous, $next, 'text')
        );
        self::assertStringContainsString(
            '- Problems during <ins> installation',
            $this->diff_processor->processDiff($previous, $next, 'text')
        );
    }

    public function testHTMLDiff(): void
    {
        $next = new Tracker_Artifact_ChangesetValue_Text(
            111,
            $this->changeset,
            $this->field,
            false,
            'Problems during <ins> installation',
            'html'
        );

        $previous = new Tracker_Artifact_ChangesetValue_Text(
            112,
            $this->changeset,
            $this->field,
            false,
            'FullTextSearch does not work on Wiki pages',
            'html'
        );

        self::assertStringContainsString(
            '<tt class="prefix">-</tt><del>FullTextSearch does not work on Wiki pages</del>',
            $this->diff_processor->processDiff($next, $previous, 'html')
        );
        self::assertStringContainsString(
            '<tt class="prefix">+</tt><ins>Problems during &lt;ins&gt; installation',
            $this->diff_processor->processDiff($next, $previous, 'html')
        );

        self::assertStringContainsString(
            '<tt class="prefix">+</tt><ins>FullTextSearch does not work on Wiki pages',
            $this->diff_processor->processDiff($previous, $next, 'html')
        );
        self::assertStringContainsString(
            '<tt class="prefix">-</tt><del>Problems during &lt;ins&gt; installation',
            $this->diff_processor->processDiff($previous, $next, 'html')
        );
    }
}
