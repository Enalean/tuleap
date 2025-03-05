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

namespace Tuleap\FullTextSearchCommon\REST\v1;

use Tuleap\Glyph\Glyph;
use Tuleap\QuickLink\SwitchToQuickLink;
use Tuleap\Search\SearchResultEntryBadge;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\SearchResultEntryBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchResultEntryRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TYPE            = 'countersale';
    private const PER_TYPE_ID     = 85;
    private const XREF            = 'countersale #' . self::PER_TYPE_ID;
    private const URI             = '/plugins/overpopulousness?id=' . self::PER_TYPE_ID;
    private const TITLE           = 'incristaliser';
    private const COLOR_NAME      = 'sherwood-green';
    private const ICON_NAME       = 'fa-solid fa-hashtag';
    private const PROJECT_ID      = 231;
    private const SMALL_ICON_SVG  = '<svg>small</svg>';
    private const NORMAL_ICON_SVG = '<svg>normal</svg>';
    private const CROPPED_CONTENT = '...dumontite carbonatization...';

    private const FIRST_QUICK_LINK_NAME  = 'rambong';
    private const FIRST_QUICK_LINK_URI   = '/plugins/unhypothecated?id=' . self::PER_TYPE_ID;
    private const FIRST_QUICK_LINK_ICON  = 'fa-solid fa-business-time';
    private const SECOND_QUICK_LINK_NAME = 'nonsuit';
    private const SECOND_QUICK_LINK_URI  = '/plugins/featured?id=' . self::PER_TYPE_ID;
    private const SECOND_QUICK_LINK_ICON = 'fa-brands fa-java';

    private const FIRST_BADGE_LABEL  = 'Featured';
    private const FIRST_BADGE_COLOR  = 'lilac-purple';
    private const SECOND_BADGE_LABEL = 'Renowned';

    public function testItBuilds(): void
    {
        $first_badge  = new SearchResultEntryBadge(self::FIRST_BADGE_LABEL, self::FIRST_BADGE_COLOR);
        $second_badge = new SearchResultEntryBadge(self::SECOND_BADGE_LABEL, null);

        $first_quick_link  = new SwitchToQuickLink(
            self::FIRST_QUICK_LINK_NAME,
            self::FIRST_QUICK_LINK_URI,
            self::FIRST_QUICK_LINK_ICON
        );
        $second_quick_link = new SwitchToQuickLink(
            self::SECOND_QUICK_LINK_NAME,
            self::SECOND_QUICK_LINK_URI,
            self::SECOND_QUICK_LINK_ICON
        );

        $project        = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $entry          = SearchResultEntryBuilder::anEntry()
            ->withCrossReference(self::XREF)
            ->withLink(self::URI)
            ->withTitle(self::TITLE)
            ->withColorName(self::COLOR_NAME)
            ->withType(self::TYPE)
            ->withPerTypeId(self::PER_TYPE_ID)
            ->withIconName(self::ICON_NAME)
            ->withSmallIcon(new Glyph(self::SMALL_ICON_SVG))
            ->withNormalIcon(new Glyph(self::NORMAL_ICON_SVG))
            ->withCroppedContent(self::CROPPED_CONTENT)
            ->inProject($project)
            ->withQuickLinks($first_quick_link, $second_quick_link)
            ->withBadges($first_badge, $second_badge)
            ->build();
        $representation = SearchResultEntryRepresentation::fromSearchResultEntry($entry);

        self::assertSame(self::XREF, $representation->xref);
        self::assertSame(self::URI, $representation->html_url);
        self::assertSame(self::TITLE, $representation->title);
        self::assertSame(self::COLOR_NAME, $representation->color_name);
        self::assertSame(self::TYPE, $representation->type);
        self::assertSame(self::PER_TYPE_ID, $representation->per_type_id);
        self::assertSame(self::ICON_NAME, $representation->icon_name);
        self::assertSame(self::SMALL_ICON_SVG, $representation->small_icon);
        self::assertSame(self::NORMAL_ICON_SVG, $representation->icon);
        self::assertSame(self::CROPPED_CONTENT, $representation->cropped_content);
        self::assertSame(self::PROJECT_ID, $representation->project->id);

        self::assertCount(2, $representation->quick_links);
        [$first_quick_link_representation, $second_quick_link_representation] = $representation->quick_links;
        self::assertSame(self::FIRST_QUICK_LINK_NAME, $first_quick_link_representation->name);
        self::assertSame(self::FIRST_QUICK_LINK_URI, $first_quick_link_representation->html_url);
        self::assertSame(self::FIRST_QUICK_LINK_ICON, $first_quick_link_representation->icon_name);
        self::assertSame(self::SECOND_QUICK_LINK_NAME, $second_quick_link_representation->name);
        self::assertSame(self::SECOND_QUICK_LINK_URI, $second_quick_link_representation->html_url);
        self::assertSame(self::SECOND_QUICK_LINK_ICON, $second_quick_link_representation->icon_name);

        self::assertCount(2, $representation->badges);
        [$first_badge_representation, $second_badge_representation] = $representation->badges;
        self::assertSame(self::FIRST_BADGE_LABEL, $first_badge_representation->label);
        self::assertSame(self::FIRST_BADGE_COLOR, $first_badge_representation->color);
        self::assertSame(self::SECOND_BADGE_LABEL, $second_badge_representation->label);
        self::assertNull($second_badge_representation->color);
    }

    public function testItBuildsNullableProperties(): void
    {
        $project        = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $entry          = SearchResultEntryBuilder::anEntry()
            ->withTitle(self::TITLE)
            ->withLink(self::URI)
            ->withColorName(self::COLOR_NAME)
            ->withType(self::TYPE)
            ->withPerTypeId(self::PER_TYPE_ID)
            ->withIconName(self::ICON_NAME)
            ->inProject($project)
            ->build();
        $representation = SearchResultEntryRepresentation::fromSearchResultEntry($entry);

        self::assertSame(self::TITLE, $representation->title);
        self::assertSame(self::URI, $representation->html_url);
        self::assertSame(self::COLOR_NAME, $representation->color_name);
        self::assertSame(self::TYPE, $representation->type);
        self::assertSame(self::PER_TYPE_ID, $representation->per_type_id);
        self::assertSame(self::ICON_NAME, $representation->icon_name);
        self::assertSame(self::PROJECT_ID, $representation->project->id);
        self::assertNull($representation->xref);
        self::assertNull($representation->small_icon);
        self::assertNull($representation->icon);
        self::assertNull($representation->cropped_content);
        self::assertEmpty($representation->quick_links);
        self::assertEmpty($representation->badges);
    }
}
