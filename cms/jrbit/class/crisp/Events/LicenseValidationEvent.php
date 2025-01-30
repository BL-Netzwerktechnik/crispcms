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
        private null|false|License $license
    ) {
    }

    public function getLicense(): null|false|License
    {
        return $this->license;
    }

    public function addErrorMessage(string $message): void
    {
        $this->errorMessages[] = $message;
    }

    public function getErrorMessages(): array
    {
        if($this->license === false) {
            return ['License is invalid or not installed'];
        }
        return $this->license->getErrors() + $this->errorMessages;
    }
}
