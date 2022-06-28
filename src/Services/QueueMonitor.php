<?php

namespace romanzipp\QueueMonitor\Services;

use romanzipp\QueueMonitor\Models\Job as JobModel;
use Facade\Ignition\JobRecorder\JobRecorder;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use romanzipp\QueueMonitor\Models\Contracts\MonitorContract;
use romanzipp\QueueMonitor\Traits\IsMonitored;
use Throwable;

class QueueMonitor
{
    private const TIMESTAMP_EXACT_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @var bool
     */
    public static $loadMigrations = false;

    /**
     * @var \romanzipp\QueueMonitor\Models\Contracts\MonitorContract
     */
    public static $model;

    /**
     * Model da fila de Jobs.
     * @var \romanzipp\QueueMonitor\Models\Contracts\JobContract
     */
    public static $model_queue_jobs;

    /**
     * Model da fila de Jobs.
     * @var \romanzipp\QueueMonitor\Models\Contracts\JobFailedContract
     */
    public static $model_jobs_failed;


    /**
     * Get the model used to store the monitoring data.
     *
     * @return \romanzipp\QueueMonitor\Models\Contracts\MonitorContract
     */
    public static function getModel(): MonitorContract
    {
        return new self::$model();
    }

    public static function getModelQueueJobs(): \romanzipp\QueueMonitor\Models\Contracts\JobContract
    {
        return new self::$model_queue_jobs();
    }

    public static function getModelJobsFailed(): JobFailed
    {
        return new self::$model_jobs_failed();
    }

    /**
     * Handle Job Processing.
     *
     * @param \Illuminate\Queue\Events\JobProcessing $event
     *
     * @return void
     */
    public static function handleJobProcessing(JobProcessing $event): void
    {
        self::jobStarted($event->job);
    }

    /**
     * Handle Job Processed.
     *
     * @param \Illuminate\Queue\Events\JobProcessed $event
     *
     * @return void
     */
    public static function handleJobProcessed(JobProcessed $event): void
    {
        self::jobFinished($event->job);
    }

    /**
     * Handle Job Failing.
     *
     * @param \Illuminate\Queue\Events\JobFailed $event
     *
     * @return void
     */
    public static function handleJobFailed(JobFailed $event): void
    {
        self::jobFinished($event->job, true, $event->exception);
    }

    /**
     * Handle Job Exception Occurred.
     *
     * @param \Illuminate\Queue\Events\JobExceptionOccurred $event
     *
     * @return void
     */
    public static function handleJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        self::jobFinished($event->job, true, $event->exception);
    }

    /**
     * Get Job ID.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     *
     * @return string|int
     */
    public static function getJobId(JobContract $job)
    {
        if ($jobId = $job->getJobId()) {
            return $jobId;
        }

        return sha1($job->getRawBody());
    }

    /**
     * Start Queue Monitoring for Job.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     *
     * @return void
     */
    protected static function jobStarted(JobContract $job): void
    {
        if ( ! self::shouldBeMonitored($job)) {
            return;
        }

        $now = Carbon::now();

        $model = self::getModel();

        Log::debug('getJobId: ', ['jobId' => self::getJobId($job)]);

        $data = [
            'job_id' => self::getJobId($job),
            'name' => $job->resolveName(),
            'queue' => $job->getQueue(),
            'started_at' => $now,
            'started_at_exact' => $now->format(self::TIMESTAMP_EXACT_FORMAT),
            'attempt' => $job->attempts(),

            'uuid' => $job->uuid(),
            'connection' => $job->getConnectionName(),
            'queue' => $job->getQueue(),
            'payload' => $job->payload(),
        ];

        // ////////////////////////////////////////

        /*
        Exemplo de dados em $event->job->payload() = array:
        [uuid] => a505a60c-006e-4193-ae63-5169dc8abacf
        [displayName] => Modules\\Notification\\Jobs\\SendEmailRegisterJob
        [job] => Illuminate\\Queue\\CallQueuedHandler@call
        [maxTries] =>
        [maxExceptions] =>
        [failOnTimeout] =>
        [backoff] =>
        [timeout] =>
        [retryUntil] =>
        [data] => Array
            (
                [commandName] => Modules\\Notification\\Jobs\\SendEmailRegisterJob
                [command] => O:46:\"Modules\\Notification\\Jobs\\SendEmailRegisterJob\":12:{s:10:\"\u0000*\u0000user_id\";i:848;s:13:\"\u0000*\u0000user_email\";s:22:\"roberzguerra@gmail.com\";s:3:\"job\";N;s:10:\"connection\";N;s:5:\"queue\";s:10:\"send-email\";s:15:\"chainConnection\";N;s:10:\"chainQueue\";N;s:19:\"chainCatchCallbacks\";N;s:5:\"delay\";O:13:\"Carbon\\Carbon\":3:{s:4:\"date\";s:26:\"2022-06-23 14:48:00.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:17:\"America/Sao_Paulo\";}s:11:\"afterCommit\";N;s:10:\"middleware\";a:0:{}s:7:\"chained\";a:0:{}}
            )
        */
        // //////////////////////////////////////////////////

        $model::query()->create($data);
    }

    /**
     * Finish Queue Monitoring for Job.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     * @param bool $failed
     * @param \Throwable|null $exception
     *
     * @return void
     */
    protected static function jobFinished(JobContract $job, bool $failed = false, ?Throwable $exception = null): void
    {
        if ( ! self::shouldBeMonitored($job)) {
            return;
        }

        $model = self::getModel();

        $monitor = $model::query()
            ->where('job_id', self::getJobId($job))
            ->where('attempt', $job->attempts())
            ->orderByDesc('started_at')
            ->first();

        if (null === $monitor) {
            return;
        }

        /** @var MonitorContract $monitor */
        $now = Carbon::now();

        if ($startedAt = $monitor->getStartedAtExact()) {
            $timeElapsed = (float) $startedAt->diffInSeconds($now) + $startedAt->diff($now)->f;
        }

        $resolvedJob = $job->resolveName();

        if (null === $exception && false === $resolvedJob::keepMonitorOnSuccess()) {
            $monitor->delete();

            return;
        }

        $attributes = [
            'finished_at' => $now,
            'finished_at_exact' => $now->format(self::TIMESTAMP_EXACT_FORMAT),
            'time_elapsed' => $timeElapsed ?? 0.0,
            'failed' => $failed,
        ];

        if (null !== $exception) {
            $attributes += [
                'exception' => mb_strcut((string) $exception, 0, config('queue-monitor.db_max_length_exception', 4294967295)),
                'exception_class' => get_class($exception),
                'exception_message' => mb_strcut($exception->getMessage(), 0, config('queue-monitor.db_max_length_exception_message', 65535)),
            ];
        }

        $monitor->update($attributes);
    }

    /**
     * Determine weather the Job should be monitored, default true.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     *
     * @return bool
     */
    public static function shouldBeMonitored(JobContract $job): bool
    {
        return array_key_exists(IsMonitored::class, ClassUses::classUsesRecursive(
            $job->resolveName()
        ));
    }

    /**
     * Retorna toda a config de Tipos de Filas no config queue.queue_types.
     *
     * @return array
     */
    public static function getQueueTypes(): array
    {
        return config('queue.queue_types', []);
    }

    /**
     * Retorna um array com os tipos de Filas disponiveis no config queues.queue_types
     * @return array
     */
    public static function getListQueueTypes(): array
    {
        $list = self::getQueueTypes();
        if ($list) {
            $list = array_keys($list);
        }
        return $list;
    }
}
