<?php

namespace romanzipp\QueueMonitor\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use romanzipp\QueueMonitor\Controllers\Payloads\Metric;
use romanzipp\QueueMonitor\Controllers\Payloads\Metrics;
use romanzipp\QueueMonitor\Services\QueueMonitor;
use TCG\Voyager\Facades\Voyager;

class JobsController
{
    public function list_jobs(Request $request)
    {
        $list_queue_types = QueueMonitor::getListQueueTypes();

        $data = $request->validate([
            // 'type' => ['nullable', 'string', Rule::in(['all', 'running', 'failed', 'succeeded'])],
            'queue' => ['nullable', 'string', Rule::in($list_queue_types)],
        ]);

        $filters = [
            'type' => $data['type'] ?? 'all',
            'queue' => $data['queue'] ?? 'all',
        ];

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
            'list_queue_types' => $list_queue_types,
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
