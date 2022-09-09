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

namespace Tuleap\FullTextSearchDB\CLI;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Search\IndexAllPossibleItemsEvent;
use Tuleap\Search\ItemToIndexQueue;
use Tuleap\Search\ProgressQueueIndexItemCategory;

final class IndexAllItemsCommand extends Command
{
    public const NAME = 'full-text-search:index-all-items';

    public function __construct(
        private EventDispatcherInterface $event_dispatcher,
        private ItemToIndexQueue $item_to_index_queue,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Add all existing items into queue to be indexed');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Start queueing existing items into the index queue</info>');
        $this->event_dispatcher->dispatch(
            new IndexAllPossibleItemsEvent(
                $this->item_to_index_queue,
                function (string $item_category) use ($output): ProgressQueueIndexItemCategory {
                    return new ProgressQueueIndexItemCategorySymfonyOutput($output, $item_category);
                }
            )
        );

        return 0;
    }
}
