<?php

declare(strict_types=1);

namespace staffutils;

class BanEntry {

    /** @var int */
    public const BAN_TYPE = 1;

    /**
     * @param string $xuid
     * @param string $name
     * @param string $address
     * @param string $who
     * @param bool   $ip
     * @param string $reason
     * @param string $createdAt
     * @param string $endAt
     * @param int    $type
     */
    public function __construct(
        private string $xuid,
        private string $name,
        private string $address,
        private string $who,
        private bool $ip,
        private string $reason = '',
        private string $createdAt = '',
        private string $endAt = '',
        private int $type = -1
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
}