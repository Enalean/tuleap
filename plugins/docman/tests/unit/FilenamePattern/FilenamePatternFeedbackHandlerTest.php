<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Docman\FilenamePattern;

use Tuleap\Docman\Tests\Stub\ResponseFeedbackWrapperStub;
use Tuleap\Docman\Tests\Stub\SettingsDAOStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FilenamePatternFeedbackHandlerTest extends TestCase
{
    public function testItLogsAnInfoMessageWhenTheUpdateIsOk(): void
    {
        $feedback_wrapper =  ResponseFeedbackWrapperStub::buildWithNoPredefinedLevel();

        $filename_pattern_feedback = new FilenamePatternFeedbackHandler(
            new FilenamePatternUpdater(SettingsDAOStub::buildSaveFilenamePatternMethodCounter()),
            $feedback_wrapper
        );

        $filename_pattern_feedback->getFilenamePatternUpdateFeedback(101, new FilenamePattern('Athena#${ID}', false));
        self::assertEquals('info', $feedback_wrapper->getLevel());
    }

    public function testItLogsAnErrorMessageWhenTheUpdateFails(): void
    {
        $feedback_wrapper =  ResponseFeedbackWrapperStub::buildWithNoPredefinedLevel();

        $filename_pattern_feedback = new FilenamePatternFeedbackHandler(
            new FilenamePatternUpdater(SettingsDAOStub::buildSaveFilenamePatternMethodCounter()),
            $feedback_wrapper
        );

        $filename_pattern_feedback->getFilenamePatternUpdateFeedback(101, new FilenamePattern("I'm sellin'#\${STATUS}", false));
        self::assertEquals('error', $feedback_wrapper->getLevel());
    }
}
