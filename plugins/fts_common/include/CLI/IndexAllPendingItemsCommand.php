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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Search\ItemToIndexBatchQueue;
use Tuleap\Search\IndexAllPendingItemsEvent;
use Tuleap\Search\ProgressQueueIndexItemCategory;

final class IndexAllPendingItemsCommand extends Command
{
    public const NAME = 'full-text-search:index-all-pending-items';

    public function __construct(
        private EventDispatcherInterface $event_dispatcher,
        private ItemToIndexBatchQueue $item_to_index_batch_queue,
    ) {
        parent::__construct(self::NAME);
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Index all pending items');
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Start queueing items into the index queue</info>');
        $this->event_dispatcher->dispatch(
            new IndexAllPendingItemsEvent(
                $this->item_to_index_batch_queue,
                function (string $item_category) use ($output): ProgressQueueIndexItemCategory {
                    return new ProgressQueueIndexItemCategorySymfonyOutput($output, $item_category);
                }
            )
        );
        $output->writeln('<info>All pending items have been added to the index queue</info>');

        return 0;
    }
}
