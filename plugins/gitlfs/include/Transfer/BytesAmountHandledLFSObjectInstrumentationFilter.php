<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\GitLFS\Transfer;

use Tuleap\GitLFS\StreamFilter\FilterInterface;
use Tuleap\Instrument\Prometheus\Prometheus;

final class BytesAmountHandledLFSObjectInstrumentationFilter implements FilterInterface
{
    /**
     * @var Prometheus
     */
    private $prometheus;
    /**
     * @var string
     */
    private $instrumentation_key_name;
    /**
     * @var string
     */
    private $instrumentation_key_help;
    /**
     * @var int
     */
    private $transferred_bytes = 0;
    /**
     * @var string
     */
    private $transfer_type;

    private function __construct(
        Prometheus $prometheus,
        string $instrumentation_key_name,
        string $instrumentation_key_help,
        string $transfer_type
    ) {
        $this->prometheus               = $prometheus;
        $this->instrumentation_key_name = $instrumentation_key_name;
        $this->instrumentation_key_help = $instrumentation_key_help;
        $this->transfer_type            = $transfer_type;
    }

    public static function buildTransmittedBytesFilter(Prometheus $prometheus, string $transfer_type): self
    {
        return new self(
            $prometheus,
            'gitlfs_object_transmit_bytes',
            'Total number of bytes transmitted for Git LFS objects',
            $transfer_type
        );
    }

    public static function buildReceivedBytesFilter(Prometheus $prometheus): self
    {
        return new self(
            $prometheus,
            'gitlfs_object_receive_bytes',
            'Total number of bytes received for Git LFS objects',
            'basic'
        );
    }

    public function process($data_chunk): string
    {
        $this->transferred_bytes += \strlen($data_chunk);
        return $data_chunk;
    }

    public function getFilteredChainIdentifier(): int
    {
        return STREAM_FILTER_READ;
    }

    public function filterDetachedEvent(): void
    {
        $this->prometheus->incrementBy(
            $this->instrumentation_key_name,
            $this->instrumentation_key_help,
            $this->transferred_bytes,
            ['transfer' => $this->transfer_type]
        );
    }
}
