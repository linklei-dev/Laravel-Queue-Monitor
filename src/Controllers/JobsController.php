<?php

namespace romanzipp\QueueMonitor\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use romanzipp\QueueMonitor\Controllers\Payloads\Metric;
use romanzipp\QueueMonitor\Controllers\Payloads\Metrics;
use romanzipp\QueueMonitor\Models\Contracts\MonitorContract;
use romanzipp\QueueMonitor\Services\QueueMonitor;
use TCG\Voyager\Facades\Voyager;

class JobsController
{
    public function list_jobs(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['all', 'running', 'failed', 'succeeded'])],
            'queue' => ['nullable', 'string'],
        ]);

        $filters = [
            'type' => $data['type'] ?? 'all',
            'queue' => $data['queue'] ?? 'all',
        ];

        $queues = QueueMonitor::getModel()
            ->newQuery()
            ->select('queue')
            ->groupBy('queue')
            ->get()
            ->map(function (MonitorContract $monitor) {
                return $monitor->queue;
            })
            ->toArray();

        $jobs = QueueMonitor::getModelQueueJobs()
            ->newQuery()
            ->when(($queue = $filters['queue']) && 'all' !== $queue, static function (Builder $builder) use ($queue) {
                $builder->where('queue', $queue);
            })
            ->ordered()
            ->paginate(
                config('queue-monitor.ui.per_page')
            )
            ->appends(
                $request->all()
            );

        $metrics = $this->collectMetrics();

        return Voyager::view('queue-monitor::voyager.list_jobs', [
            'jobs' => $jobs,
            'filters' => $filters,
            'queues' => $queues,
            'metrics' => $metrics,
        ]);
    }

    private function collectMetrics()
    {
        $aggregationColumns = [
            DB::raw('COUNT(id) as total'),
        ];

        $list_queue_types = QueueMonitor::getListQueueTypes();
        foreach ($list_queue_types as $queue_type) {
            $aggregationColumns[] = DB::raw("SUM(if(queue = '{$queue_type}', 1, 0)) AS `{$queue_type}`");
        }

        $result = QueueMonitor::getModelQueueJobs()
            ->newQuery()
            ->select($aggregationColumns)
            ->first();

        $metrics = new Metrics();
        $attributes = $result->getAttributes();

        $metrics->push(new Metric('Total Jobs', $attributes['total'] ?? 0, null, '%d'));
        unset($attributes['total']);

        foreach ($attributes as $key => $value) {
            $metrics->push(new Metric("Total {$key}", $value ?? 0, null, '%d'));
        }

        return $metrics;
    }
}
