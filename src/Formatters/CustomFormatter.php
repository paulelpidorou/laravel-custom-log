<?php

declare(strict_types=1);

namespace PaulElpidorou\CustomLog\Formatters;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Class CustomFormatter.
 */
final class CustomFormatter extends LineFormatter
{
    /**
     * CustomFormatter constructor.
     */
    public function __construct(private readonly bool $withStack = true)
    {
        parent::__construct(
            config('custom-log.log_format'),
            config('custom-log.date_format'),
            config('custom-log.allow_inline_breaks'),
            config('custom-log.ignore_empty_context_and_extra'),
        );
    }

    /**
     * Customize the given logger instance.
     *
     * @param  Logger  $logger
     */
    public function __invoke($logger): void
    {
        // formatter, ordering log rows
        $formatter = $this->withStack ? new self(true) : new self(false);

        foreach ($logger->getHandlers() as $handler) {
            /** @var AbstractProcessingHandler $handler */
            $handler->setFormatter($formatter);
        }
    }
}
