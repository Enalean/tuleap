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

namespace Tuleap\FullTextSearchCommon\CLI;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Search\ProgressQueueIndexItemCategory;

final class ProgressQueueIndexItemCategorySymfonyOutput implements ProgressQueueIndexItemCategory
{
    private ProgressBar $progress_bar;

    public function __construct(private OutputInterface $output, private string $item_category)
    {
        $this->progress_bar = new ProgressBar($this->output);
    }

    public function start(int $total_items): void
    {
        $this->output->writeln(sprintf('Adding %s to the index queue', OutputFormatter::escape($this->item_category)));
        $this->progress_bar->start($total_items);
    }

    public function advance(): void
    {
        $this->progress_bar->advance();
    }

    public function done(): void
    {
        $this->progress_bar->finish();
        $this->output->write(PHP_EOL);
    }
}
