<?php

declare(strict_types=1);

namespace PaulElpidorou\CustomLog\Processors;

use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Class RequestProcessor.
 */
final class RequestProcessor implements ProcessorInterface
{
    private Level $level;

    /**
     * Subdomain Processor constructor.
     */
    public function __construct(Level $level = Level::Debug)
    {
        $this->level = $level;
    }

    /**
     * @return LogRecord The processed record
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        // return if the level is not high enough
        if ($record->level->isLowerThan($this->level)) {
            return $record;
        }

        $record['extra']['request'] = self::getRequestExtra();

        return $record;
    }

    /**
     * Gets subdomain information.
     *
     * @return array<string, string>
     */
    private function getRequestExtra(): array
    {

        $requestExtras = [];

        if (app()->bound('request')) {
            $request = request();

            if (config('custom-log.request_tracking.origin')) {
                $requestExtras['origin'] = $request->header('origin') ?? $request->server('HTTP_REFERER') ?? '';
            }

            if (config('custom-log.request_tracking.origin')) {
                $requestExtras['url'] = $request->fullUrl();
            }

            if (config('custom-log.request_tracking.origin')) {
                $requestExtras['ip'] = $request->server('REMOTE_ADDR', '');
            }

            if (config('custom-log.request_tracking.origin')) {
                $requestExtras['origin'] = $request->server('HTTP_USER_AGENT', '');
            }

        }

        return $requestExtras;
    }
}
