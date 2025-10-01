<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Upload\Section\File;

use DateInterval;
use DateTimeImmutable;
use Override;
use PFUser;
use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tus\Identifier\FileIdentifier;

final readonly class FileToUploadCreator implements CreateFileToUpload
{
    private const int EXPIRATION_DELAY_IN_HOURS = 4;

    public function __construct(
        private OngoingUploadDao $dao,
        private SaveFileUpload $save,
        private DBTransactionExecutor $transaction_executor,
        private int $max_size_upload,
    ) {
    }

    #[Override]
    public function create(
        Artidoc $artidoc,
        PFUser $user,
        DateTimeImmutable $current_time,
        string $filename,
        int $filesize,
    ): Ok|Err {
        if ($filesize > $this->max_size_upload) {
            return Result::err(UploadMaxSizeExceededFault::build($filesize, $this->max_size_upload));
        }

        $new_upload = InsertFileToUpload::fromComponents(
            $artidoc,
            $filename,
            $filesize,
            $user,
            $filesize === 0 ? null : $this->getExpirationDate($current_time)
        );

        return $this->transaction_executor->execute(
            function () use ($new_upload, $current_time): Ok|Err {
                return $this->searchFileOngoingUpload($new_upload, $current_time)
                    ->andThen(
                        function (?FileIdentifier $id) use ($new_upload) {
                            return Result::ok(
                                $id ?? $this->save->saveFileOngoingUpload($new_upload)
                            );
                        }
                    );
            }
        )->map(static fn (FileIdentifier $id) => new FileToUpload($id, $filename));
    }

    /**
     * @return Ok<FileIdentifier>|Ok<null>|Err<Fault>
     */
    private function searchFileOngoingUpload(InsertFileToUpload $new_upload, \DateTimeImmutable $current_time): Ok|Err
    {
        $rows = $this->dao->searchFileOngoingUploadByItemIdNameAndExpirationDate(
            $new_upload->artidoc_id,
            $new_upload->name,
            $current_time,
        );

        foreach ($rows as $row) {
            if ($row['user_id'] !== $new_upload->user_id && $new_upload->size === $row['file_size']) {
                return Result::err(UploadCreationConflictFault::build());
            }

            if ($new_upload->size === $row['file_size']) {
                return Result::ok($row['id']);
            }
        }

        return Result::ok(null);
    }

    private function getExpirationDate(DateTimeImmutable $current_time): DateTimeImmutable
    {
        return $current_time->add(new DateInterval('PT' . self::EXPIRATION_DELAY_IN_HOURS . 'H'));
    }
}
