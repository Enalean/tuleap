<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Title;

use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Tracker;

final class CachedSemanticTitleFieldRetriever implements RetrieveSemanticTitleField
{
    private static ?self $instance = null;
    /** @var array<int, ?TextField> */
    private array $semantics_cache = [];

    public static function instance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = new self(new SemanticTitleFieldRetriever(new TitleSemanticDAO(), Tracker_FormElementFactory::instance()));
        return self::$instance;
    }

    public function __construct(private readonly RetrieveSemanticTitleField $semantic_retriever)
    {
    }

    public function fromTracker(Tracker $tracker): ?TextField
    {
        if (isset($this->semantics_cache[$tracker->getId()])) {
            return $this->semantics_cache[$tracker->getId()];
        }

        $field                                    = $this->semantic_retriever->fromTracker($tracker);
        $this->semantics_cache[$tracker->getId()] = $field;

        return $field;
    }
}
