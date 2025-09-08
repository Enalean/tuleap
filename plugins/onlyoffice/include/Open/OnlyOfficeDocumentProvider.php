<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

final class OnlyOfficeDocumentProvider implements ProvideOnlyOfficeDocument
{
    public function __construct(
        private ProvideDocmanFileLastVersion $docman_file_last_version_provider,
        private TransformDocmanFileLastVersionToOnlyOfficeDocument $transformer,
    ) {
    }

    /**
     * @psalm-return Ok<OnlyOfficeDocument>|Err<Fault>
     */
    #[\Override]
    public function getDocument(\PFUser $user, int $item_id): Ok|Err
    {
        return $this->docman_file_last_version_provider
            ->getLastVersionOfAFileUserCanAccess($user, $item_id)
            ->andThen(
                /** @psalm-return Ok<OnlyOfficeDocument>|Err<Fault> */
                function (DocmanFileLastVersion $file_last_version): Ok|Err {
                    return $this->transformer->transformToOnlyOfficeDocument($file_last_version);
                }
            );
    }
}
