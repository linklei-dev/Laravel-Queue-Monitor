<?php

namespace romanzipp\QueueMonitor\Controllers;

use App\Http\Controllers\VoyagerBaseController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Validation\Rule;
use romanzipp\QueueMonitor\Controllers\Payloads\Metric;
use romanzipp\QueueMonitor\Controllers\Payloads\Metrics;
use romanzipp\QueueMonitor\Models\Job;
use romanzipp\QueueMonitor\Services\QueueMonitor;
use TCG\Voyager\Facades\Voyager;

class JobsController extends VoyagerBaseController
{
    public function __construct()
    {
        $this->construct_has_permission('browse_queue-monitor-list-jobs');
        parent::__construct();
    }

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

    public function destroy(Request $request, $id = null)
    {
        $job = QueueMonitor::getModelQueueJobs()::find($id);
        $status = false;
        if ($job) {
            $status = $job->forceDelete();
        }

        return [
            'status' => $status,
        ];
    }

    public function batch_action(Request $request)
    {
        $response = [
            'status' => false,
            'list_messages' => [],
        ];

        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(['destroy',])],
            'ids' => ['required', 'string'],
        ]);

        @$data['ids'] = explode(',', $data['ids']);

        if ($data['ids']) {
            switch ($data['action']) {
                case 'destroy':
                    foreach ($data['ids'] as $id) {
                        $job = Job::find($id);
                        if ($job) {
                            $id_to_delete = $job->id;
                            $result = $this->destroy($request, $job);
                            if ($result['status']) {
                                $response['list_messages'][] = "Job [{$id_to_delete}] deleted.";
                            } else {
                                $response['list_messages'][] = "<span class=\"label label-danger\">ERROR:</span> Job NOT [{$monitor->uuid}] deleted.";
                            }
                        }
                    }
                    $response['status'] = true;
                    break;
            }
        }

        return $response;
    }
}
