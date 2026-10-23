<?php

declare(strict_types=1);

namespace App\Custom\Logs\Processors;

use Illuminate\Support\Facades\DB;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class UserProcessor implements ProcessorInterface
{
    /** @var array{id:int|string, info:string}|array{}|null */
    private static ?array $cache = null;

    private readonly Level $level;

    /**
     * User Processor constructor.
     */
    public function __construct(string|int|Level $level = Level::Debug)
    {
        $this->level = Logger::toMonologLevel($level);
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        // return if the level is not high enough
        if ($record->level->isLowerThan($this->level)) {
            return $record;
        }

        $record['extra']['user'] = self::getUserInfo();

        return $record;
    }

    /**
     * Gets user information
     *
     * @return array{id:int|string, info:string}|array{}
     */
    private function getUserInfo(): array
    {
        if (self::$cache) {
            return self::$cache;
        }

        if (! empty(DB::getDatabaseName()) && auth()->check()) {
            $user = auth()->user();

            return self::$cache = [
                'id' => config('custom-log.user_tracking.user_id') ? $user->id ?? '' : '',
                'info' => config('custom-log.user_tracking.user_name') ? $user->name ?? '' : '',
            ];
        }

        return self::$cache = [];
    }
}
