<?php

namespace romanzipp\QueueMonitor\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use romanzipp\QueueMonitor\Controllers\Payloads\Metric;
use romanzipp\QueueMonitor\Controllers\Payloads\Metrics;
use romanzipp\QueueMonitor\Models\Contracts\MonitorContract;
use romanzipp\QueueMonitor\Models\Job;
use romanzipp\QueueMonitor\Models\Monitor;
use romanzipp\QueueMonitor\Services\QueueMonitor;
use TCG\Voyager\Facades\Voyager;

class ShowQueueMonitorController
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $timeFrame = config('queue-monitor.ui.metrics_time_frame') ?? 2;

        $list_queue_types = QueueMonitor::getListQueueTypes();
        $list_job_status_data = QueueMonitor::getListJobStatusData();

        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in(array_keys($list_job_status_data))],
            'queue' => ['nullable', 'string', Rule::in($list_queue_types)],
        ]);

        $filters = [
            'type' => $data['type'] ?? 'all',
            'queue' => $data['queue'] ?? 'all',
        ];

        $jobs = QueueMonitor::getModel()
            ->newQuery()
            ->when(($type = $filters['type']) && 'all' !== $type, static function (Builder $builder) use ($type) {
                switch ($type) {
                    case 'running':
                        $builder->whereNull('finished_at');
                        break;

                    case 'failed':
                        $builder->where('failed', 1)->whereNotNull('finished_at');
                        break;

                    case 'succeeded':
                        $builder->where('failed', 0)->whereNotNull('finished_at');
                        break;
                }
            })
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

        /*
        $list_queue_types = QueueMonitor::getModel()
            ->newQuery()
            ->select('queue')
            ->groupBy('queue')
            ->get()
            ->map(function (MonitorContract $monitor) {
                return $monitor->queue;
            })
            ->toArray();
        */

        $metrics = null;

        if (config('queue-monitor.ui.show_metrics')) {
            $metrics = $this->collectMetrics();
        }

        /*return view('queue-monitor::jobs', [
            'jobs' => $jobs,
            'filters' => $filters,
            'queues' => $queues,
            'metrics' => $metrics,
            'timeFrame' => $timeFrame,
        ]);*/

        return Voyager::view('queue-monitor::voyager.monitor_jobs', [
            'jobs' => $jobs,
            'filters' => $filters,
            'list_job_status_data' => $list_job_status_data,
            'list_queue_types' => $list_queue_types,
            'metrics' => $metrics,
            'timeFrame' => $timeFrame,
        ]);
    }

    private function collectMetrics(): Metrics
    {
        $timeFrame = config('queue-monitor.ui.metrics_time_frame') ?? 2;

        $metrics = new Metrics();

        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(time_elapsed) as total_time_elapsed'),
            DB::raw('AVG(time_elapsed) as average_time_elapsed'),
        ];

        $aggregatedInfo = QueueMonitor::getModel()
            ->newQuery()
            ->select($aggregationColumns)
            ->where('started_at', '>=', Carbon::now()->subDays($timeFrame))
            ->first();

        $aggregatedComparisonInfo = QueueMonitor::getModel()
            ->newQuery()
            ->select($aggregationColumns)
            ->where('started_at', '>=', Carbon::now()->subDays($timeFrame * 2))
            ->where('started_at', '<=', Carbon::now()->subDays($timeFrame))
            ->first();

        if (null === $aggregatedInfo || null === $aggregatedComparisonInfo) {
            return $metrics;
        }

        return $metrics
            ->push(
                new Metric('Total Jobs Executed', $aggregatedInfo->count ?? 0, $aggregatedComparisonInfo->count, '%d')
            )
            ->push(
                new Metric('Total Execution Time', $aggregatedInfo->total_time_elapsed ?? 0, $aggregatedComparisonInfo->total_time_elapsed, '%ds')
            )
            ->push(
                new Metric('Average Execution Time', $aggregatedInfo->average_time_elapsed ?? 0, $aggregatedComparisonInfo->average_time_elapsed, '%0.2fs')
            );
    }

    /**
     * Deleta registro de JobMonitor.
     *
     * @param Request $request
     * @param Monitor $monitor
     *
     * @return bool[]
     */
    public function destroy(Request $request, Monitor $monitor)
    {
        $status = $monitor->forceDelete();

        return [
            'status' => $status,
        ];
    }

    public function restart_job_monitor(Request $request, Monitor $monitor)
    {
        $respose = [
            'status' => false,
            'message' => '',
        ];

        if ($monitor) {
            $date_now_timestamp = Carbon::now()->getTimestamp();

            // Para testes:
            // $class_job_name = "\\{$monitor->display_name}";
            // dd($class_job_name::dispatch());
            // dd($monitor->payload);
            // 7af8c597-ae38-4a51-8531-66184746fb85

            Artisan::call("queue:retry {$monitor->uuid}");
            $output = Artisan::output();
            $compare = "The failed job [{$monitor->uuid}] has been pushed back onto the queue!\n";
            if ($output == $compare) {
                $respose['status'] = true;
                $monitor->forceDelete();
            }
            $respose['message'] = $output;

            return $respose;
        }
    }

    public function batch_action(Request $request)
    {
        $response = [
            'status' => false,
            'list_messages' => [],
        ];

        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(['destroy', 'restart_job_monitor'])],
            'ids' => ['required', 'string'],
        ]);

        @$data['ids'] = explode(',', $data['ids']);

        if ($data['ids']) {
            switch ($data['action']) {
                case 'destroy':
                    foreach ($data['ids'] as $id) {
                        $monitor = Monitor::find($id);
                        if ($monitor) {
                            $result = $this->destroy($request, $monitor);
                            if ($result['status']) {
                                $response['list_messages'][] = "Job [{$monitor->uuid}] deleted.";
                            } else {
                                $response['list_messages'][] = "<span class=\"label label-danger\">ERROR:</span> Job NOT [{$monitor->uuid}] deleted.";
                            }
                        }
                    }
                    $response['status'] = true;
                    break;

                case 'restart_job_monitor':
                    foreach ($data['ids'] as $id) {
                        $monitor = Monitor::find($id);
                        if ($monitor) {
                            $result = $this->restart_job_monitor($request, $monitor);
                            if ($result['status']) {
                                $response['list_messages'][] = $result['message'];
                            } else {
                                $response['list_messages'][] = "<span class=\"label label-danger\">ERROR:</span> {$result['message']}";
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
