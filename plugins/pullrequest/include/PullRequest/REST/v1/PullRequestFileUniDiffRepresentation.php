<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\FileUniDiff;
use Tuleap\PullRequest\REST\v1\InlineComment\InlineCommentRepresentation;

/**
 * @psalm-immutable
 */
class PullRequestFileUniDiffRepresentation
{
    /**
     * @var PullRequestLineUniDiffRepresentation[] {@type PullRequestLineUniDiffRepresentation[]}
     */
    public $lines;

    /**
     * @var InlineCommentRepresentation[] $inline_comments {@type InlineCommentRepresentation}
     */
    public array $inline_comments;

    /**
     * @var string {@type string}
     */
    public $mime_type;

    /**
     * @var string {@type string}
     */
    public $charset;

    /**
     * @var string {@type string}
     */
    public $special_format = '';

    private function __construct()
    {
    }

    public static function build(FileUniDiff $diff, array $inline_comments, $mime_type, $charset, $special_format): self
    {
        $new_instance = new self();
        $lines        = [];
        foreach ($diff->getLines() as $line) {
            $lines[] = new PullRequestLineUniDiffRepresentation($line, $charset);
        }
        $new_instance->lines           = $lines;
        $new_instance->inline_comments = $inline_comments;
        $new_instance->mime_type       = $mime_type;
        $new_instance->charset         = $charset;
        $new_instance->special_format  = $special_format;

        return $new_instance;
    }
}
