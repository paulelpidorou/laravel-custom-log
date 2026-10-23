<?php

namespace PaulElpidorou\CustomLog;


use PaulElpidorou\CustomLog\Formatters\CustomFormatter;
use PaulElpidorou\CustomLog\Processors\RequestProcessor;
use Throwable;
use function app;

use App\Custom\Logs\Processors\UserProcessor;

use function array_filter;
use function count;

use const DIRECTORY_SEPARATOR;

use Illuminate\Support\Str;
use Log;
use Monolog\Level;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\ProcessorInterface;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\TagProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function request;
use function storage_path;

use Stringable;
use UnexpectedValueException;

final class CustomLog implements LoggerInterface
{
    private string $name;

    private string $filename;

    private LoggerInterface $defaultLogger;

    private array $processors;

    /**
     *
     * @param string|null $name The name that will appear inside logs,by default CustomLog
     * @param string|null $filename The file name of log file,by default $name.log
     * @param bool $isRotating Indicates that will save daily logs
     * @param bool $isCommand Set to true to add appropriate processors for a Command
     * @param bool $isPlain Set to true if log must be with fewer processors
     * @param bool $extraProcessors To add process id and memory usage
     * @param Level $level Set the lower debugging level
     * @param array $tags Optionally you can add tags for log
     * @throws Throwable
     */
    public function __construct(
        ?string         $name = null,
        ?string         $filename = null,
        protected bool  $isRotating = true,
        protected bool  $isCommand = false,
        protected bool  $isPlain = false,
        protected bool  $extraProcessors = false,
        protected Level $level = Level::Debug,
        protected array $tags = [],
    ) {
        $this->name     = $name ?? config('custom-log.default_name');
        $this->filename = $filename ?? $name . '.log';

        $this->createLog();
    }


    /**
     * @param string $name
     * @param array $arguments
     * @throws Throwable
     */
    public function __call(string $name, array $arguments): void
    {
        $this->createLog();
        $this->{$name}($arguments);
    }

    public function __serialize(): array
    {
        // Exclude the $defaultLogger
        return [
            'name'            => $this->name,
            'filename'        => $this->filename,
            'isRotating'      => $this->isRotating,
            'isCommand'       => $this->isCommand,
            'isPlain'         => $this->isPlain,
            'extraProcessors' => $this->extraProcessors,
            'level'           => $this->level,
            'tags'            => $this->tags,
        ];
    }

    /**
     * @throws Throwable
     */
    public function __unserialize(array $data): void
    {
        $this->name            = $data['name'];
        $this->filename        = $data['filename'];
        $this->isRotating      = $data['isRotating'];
        $this->isCommand       = $data['isCommand'];
        $this->isPlain         = $data['isPlain'];
        $this->extraProcessors = $data['extraProcessors'];
        $this->level           = $data['level'];
        $this->tags            = $data['tags'];

        $this->createLog(); // Reinitialize the logger
    }

    /**
     * Dynamically pass log calls into the writer.
     *
     *
     *
     * @throws \InvalidArgumentException
     */
    public function write(
        mixed $level,
        mixed $message,
        array $context = [],
    ): void {
        $this->defaultLogger->log($message, $level, $context);
    }

    /**
     * Warn in log.
     */
    public function warn(mixed $message, array $context = []): void
    {
        $this->defaultLogger->warning($message, $context);
    }


    public function emergency(
        string|Stringable $message,
        array             $context = [],
    ): void {
        $this->defaultLogger->emergency($message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->defaultLogger->alert($message, $context);
    }


    public function critical(
        string|Stringable $message,
        array             $context = [],
    ): void {
        $this->defaultLogger->critical($message, $context);
    }


    public function error(string|Stringable $message, array $context = []): void
    {
        $this->defaultLogger->error($message, $context);
    }

    public function warning(
        string|Stringable $message,
        array             $context = [],
    ): void {
        $this->defaultLogger->warning($message, $context);
    }


    public function notice(
        string|Stringable $message,
        array             $context = [],
    ): void {
        $this->defaultLogger->notice($message, $context);
    }


    public function info(string|Stringable $message, array $context = []): void
    {
        $this->defaultLogger->info($message, $context);
    }


    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->defaultLogger->debug($message, $context);
    }


    public function log(
        $level,
        string|Stringable $message,
        array $context = [],
    ): void {
        $this->defaultLogger->log($level, $message, $context);
    }

    /**
     * Get the underlying logger implementation.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->defaultLogger;
    }

    public function addMemoryProcessor(): self
    {
        // processor, memory usage
        $this->pushProcessor(new MemoryUsageProcessor());
        //Injects memory_get_peak_usage in all records
        $this->pushProcessor(new MemoryPeakUsageProcessor());

        return $this;
    }

    /**
     * @throws Throwable
     */
    private function createLog(): void
    {
        if (isset($this->defaultLogger)) {
            return;
        }

        throw_if(
            $this->isCommand && $this->isPlain,
            InvalidArgumentException::class,
            'Cannot use isCommand and isPlain flags together',
        );

        $days = null;

        if ($this->isRotating) {
            $driver = 'daily';
            $days   = config('custom-log.max_files');
        } else {
            $driver = 'single';
        }

        //add default processors
        $this->addProcessors();

        //add extras processors
        if ($this->extraProcessors) {
            $this->addMemoryProcessor();
            $this->addProcessIdProcessor();
        }
        if (0 !== count($this->tags)) {
            $this->addTagProcessor($this->tags);
        }

        $parameters = [
            'driver'     => $driver,
            'path'       => config('custom-log.storage_path').DIRECTORY_SEPARATOR.$this->filename,
            'processors' => $this->processors,
            'level'      => Str::lower($this->level->name),
            'formatter'  => config('custom-log.formatter_class'),
        ];

        if ($this->isRotating) {
            $parameters['days'] = $days;
        }

        $this->defaultLogger = Log::build($parameters);
    }

    private function addProcessors(): void
    {
        if ($this->isCommand || app()->runningInConsole()) {
            $this->addCommandProcessors();

            return;
        }

        if ($this->isPlain) {
            $this->addPlainProcessors();

            return;
        }

        $this->addDefaultProcessors();

        $this->addProductionProcessors();
    }

    private function addCommandProcessors(): void
    {
        //Injects line/file:class/function where the log message came from
        $this->pushProcessor(new IntrospectionProcessor($this->level));
        //Processes a record's message according to PSR-3 rules
        $this->pushProcessor(new PsrLogMessageProcessor());
    }

    private function addDefaultProcessors(): void
    {
        //Injects line/file:class/function where the log message came from
        $this->pushProcessor(new IntrospectionProcessor($this->level));
        //Processes a record's message according to PSR-3 rules
        $this->pushProcessor(new PsrLogMessageProcessor());
        //Inject subdomain in log
        try {
            //Inject user in log
            $this->pushProcessor(new UserProcessor($this->level));
        } catch (InvalidArgumentException $invalidArgumentException) {
            Log::channel('emergency')->error($invalidArgumentException);
        }
    }

    private function addProductionProcessors(): void
    {
        try {
            $serverData = $this->getServerData();
            // processor, adding URI, IP address etc. to the log
            $this->pushProcessor(new WebProcessor($serverData));
        } catch (UnexpectedValueException $unexpectedValueException) {
            Log::channel('emergency')->error($unexpectedValueException);
        }

        //Inject request in log
        $this->pushProcessor(new RequestProcessor($this->level));
        // Injects Git branch and Git commit SHA in all records
        $this->pushProcessor(new GitProcessor($this->level));
    }

    private function addPlainProcessors(): void
    {
        //Processes a record's message according to PSR-3 rules
        $this->pushProcessor(new PsrLogMessageProcessor());
    }

    private function addLocalProcessors(): void
    {
        $serverData = $this->getServerData();
        // processor, adding URI, IP address etc. to the log
        try {
            $this->pushProcessor(new WebProcessor($serverData));
        } catch (UnexpectedValueException $unexpectedValueException) {
            Log::channel('emergency')->error($unexpectedValueException);
        }

        //Inject request in log
        $this->pushProcessor(new RequestProcessor($this->level));
    }

    private function addProcessIdProcessor(): void
    {
        // Adds value of getmypid into records
        $this->pushProcessor(new ProcessIdProcessor());

    }

    private function addTagProcessor(array $tags): void
    {
        // add tags in logs
        $this->pushProcessor(new TagProcessor($tags));

    }

    private function getServerData(): array
    {
        $serverData = [
            'REQUEST_URI'    => request()?->server('REQUEST_URI'),
            'REMOTE_ADDR'    => request()?->server('REMOTE_ADDR'),
            'REQUEST_METHOD' => request()?->server('REQUEST_METHOD'),
            'SERVER_NAME'    => request()?->server('SERVER_NAME'),
            'HTTP_REFERER'   => request()?->server('HTTP_REFERER'),
        ];

        return array_filter($serverData);
    }

    private function pushProcessor(ProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }
}

