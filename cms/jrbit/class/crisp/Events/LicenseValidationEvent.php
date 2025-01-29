<?php

namespace crisp\Events;

use crisp\api\License;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched each time the license verified.
 */
final class LicenseValidationEvent extends Event
{

    private array $errorMessages = [];

    public function __construct(
        private ? License $license
    ) {
    }

    public function getLicense(): ?License
    {
        return $this->license;
    }

    public function addErrorMessage(string $message): void
    {
        $this->errorMessages[] = $message;
    }

    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }
}
