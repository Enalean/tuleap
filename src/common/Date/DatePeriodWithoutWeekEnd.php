<?php
/*
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Date;

class DatePeriodWithoutWeekEnd implements DatePeriod
{
    /**
     * The time period start date, as a Unix timestamp.
     */
    private ?int $start_date;

    /**
     * The time period duration, in days.
     */
    private ?int $duration;

    /**
     * The time period end date, as a Unix timestamp.
     */
    private ?int $end_date;

    private string $error_message;

    private function __construct(?int $start_date, int|string|null $duration, ?int $end_date, string $error_message)
    {
        $this->start_date    = $start_date;
        $this->duration      = $duration === null ? $duration : (int) $duration;
        $this->end_date      = $end_date;
        $this->error_message = $error_message;
    }

    public static function buildFromDuration(?int $start_date, int|float|string|null $duration): DatePeriodWithoutWeekEnd
    {
        if (is_numeric($duration)) {
            $duration = (int) ceil((float) $duration);
        }

        if ($duration === null || ! $start_date) {
            return new DatePeriodWithoutWeekEnd($start_date, $duration, null, '');
        }

        $day_offsets = self::getDayOffsetsFromStartDateAndDuration($start_date, (int) $duration);
        $last_offset = end($day_offsets);
        $end_date    = (int) strtotime("+$last_offset days", $start_date);

        return new DatePeriodWithoutWeekEnd(
            $start_date,
            $duration,
            $end_date,
            ''
        );
    }

    public static function buildFromEndDate(?int $start_date, ?int $end_date, \Psr\Log\LoggerInterface $logger): DatePeriodWithoutWeekEnd
    {
        if ($start_date === null) {
            return new self(null, null, $end_date, '');
        }
        if ($end_date === null) {
            return new self($start_date, null, null, '');
        }

        if ($end_date < $start_date) {
            $logger->warning(
                sprintf(
                    'Inconsistent TimePeriod: end date %s is lesser than start date %s.',
                    (new \DateTimeImmutable())->setTimestamp($end_date)->format('Y-m-d'),
                    (new \DateTimeImmutable())->setTimestamp($start_date)->format('Y-m-d')
                )
            );
            $duration = -self::getNumberOfDaysWithoutWeekEndBetweenTwoDates($end_date, $start_date);
        } else {
            $duration = self::getNumberOfDaysWithoutWeekEndBetweenTwoDates($start_date, $end_date);
        }

        return new self($start_date, $duration, $end_date, '');
    }

    public static function buildFromNothingWithErrorMessage(string $error_message): DatePeriodWithoutWeekEnd
    {
        return new self(null, null, null, $error_message);
    }

    public static function buildWithoutAnyDates(): DatePeriodWithoutWeekEnd
    {
        return new self(null, null, null, '');
    }

    /**
     * @psalm-pure
     */
    private static function getNextDay(int $next_day_number, int $date): int
    {
        return (int) strtotime("+$next_day_number days", $date);
    }

    /**
     * @psalm-pure
     */
    public static function isNotWeekendDay(int $day): bool
    {
        return ! ((int) date('N', $day) === 6 || (int) date('N', $day) === 7);
    }

    public function getStartDate(): ?int
    {
        return $this->start_date;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * @psalm-mutation-free
     */
    public function getEndDate(): ?int
    {
        return $this->end_date;
    }

    /**
     * @return string[]
     */
    public function getHumanReadableDates(): array
    {
        $dates = [];

        foreach ($this->getDayOffsets() as $day_offset) {
            $day     = strtotime("+$day_offset days", (int) $this->getStartDate());
            $dates[] = date('D d', $day);
        }

        return $dates;
    }

    /**
     * @psalm-mutation-free
     */
    public function isTodayBeforeDatePeriod(): bool
    {
        return $this->getStartDate() > $this->getTodayTimestamp();
    }

    /**
     * @psalm-mutation-free
     */
    private function getTodayTimestamp(): int
    {
        return (int) strtotime($this->getTodayDate());
    }

    /**
     * Set to protected because it makes testing possible.
     *
     * @psalm-mutation-free
     */
    protected function getTodayDate(): string
    {
        if (isset($_SERVER['REQUEST_TIME'])) {
            return date('Y-m-d', $_SERVER['REQUEST_TIME']);
        }
        return date('Y-m-d');
    }

    /**
     * To be used to iterate consistently over the time period
     *
     * @return int[]
     *
     * @psalm-mutation-free
     */
    public function getDayOffsets(): array
    {
        return self::getDayOffsetsFromStartDateAndDuration((int) $this->getStartDate(), (int) $this->getDuration());
    }

    /**
     * @return int[]
     *
     * @psalm-pure
     */
    private static function getDayOffsetsFromStartDateAndDuration(int $start_date, int $duration): array
    {
        if ($duration <= 0) {
            return self::getDayOffsetsWithInconsistentDuration($start_date);
        }

        return self::getDayOffsetsWithConsistentDuration($start_date, $duration);
    }

    /**
     * @psalm-mutation-free
     */
    public function getCountDayUntilDate(int $date): int
    {
        if ($date < $this->getEndDate()) {
            return self::getNumberOfDaysWithoutWeekEndBetweenTwoDates((int) $this->getStartDate(), $date);
        } else {
            return count($this->getDayOffsets());
        }
    }

    /**
     * @return int[]
     *
     * @psalm-pure
     */
    private static function getDayOffsetsWithConsistentDuration(int $start_date, int $duration): array
    {
        $day_offsets_excluding_we = [];
        $day_offset               = 0;
        while (count($day_offsets_excluding_we) - 1 !== $duration) {
            $day = self::getNextDay($day_offset, $start_date);
            if (self::isNotWeekendDay($day)) {
                $day_offsets_excluding_we[] = $day_offset;
            }
            $day_offset++;
        }
        return $day_offsets_excluding_we;
    }

    /**
     * @return int[]
     *
     * @psalm-pure
     */
    private static function getDayOffsetsWithInconsistentDuration(int $start_date): array
    {
        $day_offset = 0;
        $day        = self::getNextDay($day_offset, $start_date);
        while (! self::isNotWeekendDay($day)) {
            $day_offset++;
            $day = self::getNextDay($day_offset, $start_date);
        }

        return [$day_offset];
    }

    /**
     * The number of days until the end of the period
     */
    public function getNumberOfDaysUntilEnd(): int
    {
        if ($this->getTodayTimestamp() > $this->getEndDate()) {
            return -self::getNumberOfDaysWithoutWeekEndBetweenTwoDates(
                (int) $this->getEndDate(),
                $this->getTodayTimestamp()
            );
        } else {
            return self::getNumberOfDaysWithoutWeekEndBetweenTwoDates(
                $this->getTodayTimestamp(),
                (int) $this->getEndDate()
            );
        }
    }

    /**
     * @psalm-pure
     */
    private static function getNumberOfDaysWithoutWeekEndBetweenTwoDates(int $start_date, int $end_date): int
    {
        $real_number_of_days_after_start = 0;
        $day                             = $start_date;
        if (self::isNotWeekendDay($day)) {
            $day_offset = -1;
        } else {
            $day_offset = 0;
        }

        do {
            if (self::isNotWeekendDay($day)) {
                $day_offset++;
            }
            $day = self::getNextDay($real_number_of_days_after_start, $start_date);
            $real_number_of_days_after_start++;
        } while ($day < $end_date);

        return $day_offset;
    }

    /**
     * The number of days since the start.
     * Is not limited by the duration of the time period.
     */
    public function getNumberOfDaysSinceStart(): int
    {
        if ($this->isToday((int) $this->getStartDate()) || $this->getStartDate() > $this->getTodayTimestamp()) {
            return 0;
        }

        return self::getNumberOfDaysWithoutWeekEndBetweenTwoDates(
            (int) $this->getStartDate(),
            $this->getTodayTimestamp()
        );
    }

    private function isToday(int $day): bool
    {
        return $this->getTodayDate() === date('Y-m-d', $day);
    }

    public function isTodayWithinDatePeriod(): bool
    {
        if (
            $this->getStartDate() <= $this->getTodayTimestamp() &&
            $this->getNumberOfDaysSinceStart() <= $this->getDuration()
        ) {
            return true;
        }

        return false;
    }

    public function getErrorMessage(): string
    {
        return $this->error_message;
    }
}
