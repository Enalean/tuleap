<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\ItemType;

use Docman_Document;
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use PHPUnit\Framework\TestCase;

final class DoesItemHasExpectedTypeVisitorTest extends TestCase
{
    private const VISITOR_PROCESSABLE_CLASSES = [
        Docman_Folder::class,
        Docman_Wiki::class,
        Docman_Link::class,
        Docman_File::class,
        Docman_EmbeddedFile::class,
        Docman_Empty::class,
        Docman_Document::class,
        Docman_Item::class
    ];

    public function testExpectedTypeIsCorrectlyIdentified(): void
    {
        foreach (self::VISITOR_PROCESSABLE_CLASSES as $processed_class) {
            $processed_item = new $processed_class();
            foreach (self::VISITOR_PROCESSABLE_CLASSES as $visitor_accepted_class) {
                $visitor = new DoesItemHasExpectedTypeVisitor($visitor_accepted_class);

                if ($processed_item->accept($visitor)) {
                    $this->assertEquals($processed_class, $visitor_accepted_class, 'The visitor has accepted a invalid item');
                    $this->assertEquals($visitor_accepted_class, $visitor->getExpectedItemClass());
                } else {
                    $this->assertNotEquals($processed_class, $visitor_accepted_class, 'The visitor has rejected a valid item');
                }
            }
        }
    }
}
