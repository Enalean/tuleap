<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\SiteDeploy\Nginx;

final readonly class NginxServerNamesHashBucketSizeCalculator
{
    public function __construct(
        private CPUInformation $cpu_information,
    ) {
    }

    /**
     * @see https://github.com/nginx/nginx/blob/release-1.27.2/src/core/ngx_hash.c#L248-L249
     * @see https://github.com/kubernetes/ingress-nginx/blob/ingress-nginx-3.15.2/internal/ingress/controller/nginx.go#L710-L719
     */
    public function computeServerNamesHashBucketSize(string $server_name): int
    {
        $word_size = $this->cpu_information->wordSize();
        $size      = strlen($server_name) + 2;
        $raw_size  = $word_size + $this->nginxAlign($size, $word_size);

        $minimal_server_names_hash_bucket_size = $this->nextPowerOfTwo($raw_size);

        $server_l1_cache_line_size = $this->cpu_information->l1CacheLineSize();

        // Do not go below nginx default
        return max($minimal_server_names_hash_bucket_size, $server_l1_cache_line_size);
    }

    /**
     * @see https://github.com/nginx/nginx/blob/release-1.27.2/src/core/ngx_config.h#L100
     */
    private function nginxAlign(int $size, int $word_size): int
    {
        return ((($size) + ($word_size - 1)) & ~($word_size - 1));
    }

    private function nextPowerOfTwo(int $value): int
    {
        return (int) pow(2, ceil(log($value, 2)));
    }
}
