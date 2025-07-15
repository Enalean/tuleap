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

declare(strict_types=1);

namespace Tuleap\PullRequest\Criterion;

use Tuleap\NeverThrow\Fault;

/**
 * @psalm-immutable
 */
final readonly class MalformedQueryFault extends Fault
{
    public static function build(): Fault
    {
        return new self('Query is malformed.');
    }

    public static function onlyOneItemAccepted(string $item_type): Fault
    {
        return new self('Query is malformed. Only one ' . $item_type . ' can be selected.');
    }

    public static function buildFromMappingErrors(\CuyZ\Valinor\Mapper\Tree\Message\Messages $errors): Fault
    {
        $all_error_messages = 'Query is malformed.';

        foreach ($errors as $error) {
            $all_error_messages .= "\n" . $error->path() . ': ' . $error->toString();
        }

        return new self($all_error_messages);
    }

    public static function relatedToCriterionCannotBeUsedWithAuthorOrReviewerCriteria(): Fault
    {
        return new self('Query is malformed. The related_to criterion cannot be used with the authors or reviewers criteria');
    }
}
