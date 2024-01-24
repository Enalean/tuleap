<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Language;

use Tuleap\Disposable\Disposable;
use Tuleap\Disposable\Dispose;

final class LocaleSwitcher implements Disposable
{
    private string $previous_locale = '';

    public function setLocale(string $locale): void
    {
        $this->setLocaleFromFullLocale("$locale.UTF-8");
    }

    /**
     * @template T
     * @psalm-param callable(): T $execution_context
     * @psalm-return T
     */
    public function setLocaleForSpecificExecutionContext(string $locale, callable $execution_context): mixed
    {
        $this->previous_locale = $this->currentLocale();
        return Dispose::using($this, function () use ($locale, $execution_context) {
            $this->setLocale($locale);
            return $execution_context();
        });
    }

    public function dispose(): void
    {
        if ($this->previous_locale !== '') {
            $this->setLocaleFromFullLocale($this->previous_locale);
        }
    }

    private function setLocaleFromFullLocale(string $full_locale): void
    {
        setlocale(LC_MESSAGES, $full_locale);
        setlocale(LC_CTYPE, $full_locale);
        setlocale(LC_TIME, $full_locale);
    }

    private function currentLocale(): string
    {
        return setlocale(LC_MESSAGES, '0');
    }
}
