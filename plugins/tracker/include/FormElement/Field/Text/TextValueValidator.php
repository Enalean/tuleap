<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Text;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreation;

final class TextValueValidator
{
    public const MAX_TEXT_SIZE = 65535;

    /**
     * @return Ok<true> | Err<Fault>
     */
    public function isValueValid(TextField $field_text, mixed $value): Ok|Err
    {
        $content_value = $this->getContentFromValue($value);

        if (! is_string($content_value)) {
            return Result::err(
                Fault::fromMessage(
                    sprintf(
                        dgettext('tuleap-tracker', '%1$s is not a text.'),
                        $field_text->getLabel(),
                    )
                )
            );
        }

        if (strlen($content_value) > self::MAX_TEXT_SIZE) {
            return Result::err(
                Fault::fromMessage(
                    sprintf(
                        dgettext('tuleap-tracker', '%1$s can not contain more than %2$s characters.'),
                        $field_text->getLabel(),
                        self::MAX_TEXT_SIZE,
                    )
                )
            );
        }

        return Result::ok(true);
    }

    /**
     * @return Ok<true> | Err<Fault>
     */
    public function isCommentContentValid(CommentCreation $comment): Ok|Err
    {
        if (strlen($comment->getBody()) > self::MAX_TEXT_SIZE) {
            return Result::err(
                Fault::fromMessage(
                    sprintf(
                        dgettext('tuleap-tracker', 'Comment content can not contain more than %1$s characters.'),
                        self::MAX_TEXT_SIZE,
                    )
                )
            );
        }

        return Result::ok(true);
    }

    private function getContentFromValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists('content', $value)) {
            return $value['content'];
        }

        return $value;
    }
}
