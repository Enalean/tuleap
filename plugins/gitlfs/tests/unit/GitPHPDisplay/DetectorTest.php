<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\GitPHPDisplay;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DetectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('fileProvider')]
    public function testItDetectsLFSFile(string $file, bool $expected): void
    {
        $detector = new Detector();

        self::assertSame($expected, $detector->isFileALFSFile($file));
    }

    public static function fileProvider(): array
    {
        $file01 = <<<EOS
version https://git-lfs.github.com/spec/v1
oid sha256:eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a
size 12

EOS;
        $file02 = <<<EOS
versionhttps://git-lfs.github.com/spec/v1
oid sha256:eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a
size 12

EOS;

        $file03 = <<<EOS
version https://git-lfs.github.com/spec/v1 oid sha256:eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a
size 12

EOS;

        $file04 = <<<EOS
versionhttps://git-lfs.github.com/spec/v1
oid sha256:eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a size 12

EOS;

        $file05 = <<<EOS
versionhttps://git-lfs.github.com/spec/v1
oid sha256:eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a
size 12
EOS;

        $file06 = <<<EOS
version https://git-lfs.github.com/spec/v1
oid shaSTUFF:eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a
size 12

EOS;

        $file07 = <<<EOS
version https://git-lfs.github.com/spec/v1
oid sha256:eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a
size -12

EOS;

        $file08 = <<<EOS
version https://git-lfs.github.com/spec/v1
oid sha256:eca87262c137f4847c6a78fcb8e035a7cb725570f
size 12

EOS;

        return [
            [$file01, true],
            [$file02, false],
            [$file03, false],
            [$file04, false],
            [$file05, false],
            [$file06, false],
            [$file07, false],
            [$file08, false],
        ];
    }
}
