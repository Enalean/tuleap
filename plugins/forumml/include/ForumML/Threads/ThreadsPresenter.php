<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML\Threads;

use Tuleap\Layout\PaginationPresenter;

/**
 * @psalm-immutable
 */
final class ThreadsPresenter
{
    /**
     * @var string
     */
    public $list_name;
    /**
     * @var int
     */
    public $nb_threads;
    /**
     * @var ThreadInfoPresenter[]
     */
    public $threads;
    /**
     * @var string
     */
    public $post_thread_url;
    /**
     * @var PaginationPresenter
     */
    public $pagination;
    /**
     * @var string
     */
    public $search;
    /**
     * @var bool
     */
    public $is_empty_state;

    /**
     * @param ThreadInfoPresenter[] $threads
     */
    public function __construct(
        string $list_name,
        int $nb_threads,
        array $threads,
        string $post_thread_url,
        string $search,
        PaginationPresenter $pagination,
    ) {
        $this->list_name       = $list_name;
        $this->nb_threads      = $nb_threads;
        $this->threads         = $threads;
        $this->post_thread_url = $post_thread_url;
        $this->search          = $search;
        $this->pagination      = $pagination;

        $this->is_empty_state = ! $search && ! $nb_threads;
    }
}
