<?php

declare(strict_types=1);

namespace staffutils;

use DateTime;

class BanEntry {

    /** @var int */
    public const BAN_TYPE = 1;
    public const MUTE_TYPE = 2;
    public const WARN_TYPE = 3;

    /**
     * @param string $xuid
     * @param string $name
     * @param string $address
     * @param string $who
     * @param string $whoName
     * @param bool   $ip
     * @param string $reason
     * @param string $createdAt
     * @param string $endAt
     * @param int    $type
     * @param int    $rowId
     */
    public function __construct(
        private string $xuid,
        private string $name,
        private string $address,
        private string $who,
        private string $whoName,
        private bool $ip,
        private string $reason = '',
        private string $createdAt = '',
        private string $endAt = '',
        private int $type = -1,
        private int $rowId = -1
    ) {}

    /**
     * @return string
     */
    public function getXuid(): string {
        return $this->xuid;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAddress(): string {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getWho(): string {
        return $this->who;
    }

    /**
     * @return string
     */
    public function getWhoName(): string {
        return $this->whoName;
    }

    /**
     * @return string
     */
    public function getReason(): string {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function setReason(string $reason = ''): void {
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string {
        return $this->createdAt;
    }

    /**
     * @param string|null $createdAt
     */
    public function setCreatedAt(string $createdAt = null): void {
        if ($createdAt == null) {
            $createdAt = StaffUtils::dateNow();
        }

        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function getEndAt(): string {
        return $this->endAt;
    }

    /**
     * @param string $endAt
     */
    public function setEndAt(string $endAt): void {
        $this->endAt = $endAt;
    }

    /**
     * @return int
     */
    public function getType(): int {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getRowId(): int {
        return $this->rowId;
    }

    /**
     * @return bool
     */
    public function isIp(): bool {
        return $this->ip;
    }

    /**
     * @return bool
     */
    public function isPermanent(): bool {
        return $this->endAt === '';
    }

    /**
     * @return bool
     */
    public function expired(): bool {
        return !$this->isPermanent() && StaffUtils::dateNow() > $this->endAt;
    }

    /**
     * @return string
     */
    public function remainingDurationString(): string {
        if ($this->expired() || $this->isPermanent()) {
            return 'Unknown';
        }

        if (!($now = date_create(StaffUtils::dateNow())) instanceof DateTime || !($end = date_create($this->endAt)) instanceof DateTime) {
            return 'Unknown';
        }

        $interval = date_diff($now, $end);

        $timeString = '';
        if (($days = $interval->days) > 0) $timeString .= StaffUtils::pluralize(StaffUtils::daysAsString(), $days) . ', ';
        if (($hours = $interval->h) > 0) $timeString .= StaffUtils::pluralize(StaffUtils::hoursAsString(), $hours) . ', ';
        if (($minutes = $interval->i) > 0) $timeString .= StaffUtils::pluralize(StaffUtils::minutesAsString(), $minutes) . ', ';

        return $timeString . StaffUtils::pluralize(StaffUtils::secondsAsString(), $interval->s);
    }

    /**
     * @return string
     */
    public function typeToString(): string {
        return match ($this->type) {
            self::BAN_TYPE => 'Ban',
            self::MUTE_TYPE => 'Mute',
            self::WARN_TYPE => 'Warn',
            default => 'Unknown'
        };
    }
}