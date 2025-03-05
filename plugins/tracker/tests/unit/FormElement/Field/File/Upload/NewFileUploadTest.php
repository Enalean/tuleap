<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File\Upload;

use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewFileUploadTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FILE_FIELD_ID     = 63;
    private const UPLOADING_USER_ID = 165;
    private const NAME              = 'tipster.txt';
    private const SIZE              = 1408;
    private const TYPE              = 'text/plain';
    private const DESCRIPTION       = 'aerolite arseniuret';

    public function testItBuildsFromComponents(): void
    {
        $file_field = new \Tracker_FormElement_Field_File(
            self::FILE_FIELD_ID,
            67,
            1,
            'attachments',
            'Attachments',
            'Irrelevant',
            1,
            'P',
            false,
            '',
            1
        );

        $uploader = UserTestBuilder::aUser()->withId(self::UPLOADING_USER_ID)->build();

        $expiration_date = new \DateTimeImmutable();

        $new_upload = NewFileUpload::fromComponents(
            $file_field,
            self::NAME,
            self::SIZE,
            self::TYPE,
            self::DESCRIPTION,
            $uploader,
            $expiration_date
        );

        self::assertSame(self::FILE_FIELD_ID, $new_upload->file_field_id);
        self::assertSame(self::NAME, $new_upload->file_name);
        self::assertSame(self::SIZE, $new_upload->file_size);
        self::assertSame(self::TYPE, $new_upload->file_type);
        self::assertSame(self::DESCRIPTION, $new_upload->description);
        self::assertSame(self::UPLOADING_USER_ID, $new_upload->uploading_user_id);
        self::assertSame($expiration_date, $new_upload->expiration_date);
    }
}
