<?php

namespace romanzipp\QueueMonitor\Routes;

use Closure;

class QueueMonitorRoutes
{
    /**
     * Scaffold the Queue Monitor UI routes.
     *
     * @return \Closure
     */
    public function queueMonitor(): Closure
    {
        return function (array $options = []) {
            /** @var \Illuminate\Routing\Router $this */
            $this->get('', '\romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController@index')->name('queue-monitor::index');
            $this->get('list-jobs', '\romanzipp\QueueMonitor\Controllers\JobsController@list_jobs')->name('queue-monitor::list_jobs');

            if (config('queue-monitor.ui.allow_deletion')) {
                // $this->delete('monitors/{monitor}', '\romanzipp\QueueMonitor\Controllers\DeleteMonitorController')->name('queue-monitor::destroy');
                $this->delete('delete-job-monitor/{monitor}', '\romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController@destroy')->name('queue-monitor::delete_job_monitor');
                $this->delete('delete-job-queue/{job}', '\romanzipp\QueueMonitor\Controllers\JobsController@destroy')->name('queue-monitor::delete_job_queue');
            }

            $this->post('restart-job-monitor/{monitor}', '\romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController@restart_job_monitor')->name('queue-monitor::restart_job_monitor');
            $this->post('batch-action', '\romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController@batch_action')->name('queue-monitor::batch_action');
            $this->post('batch-action-job-queue', '\romanzipp\QueueMonitor\Controllers\JobsController@batch_action')->name('queue-monitor::batch_action_job_queue');

            /*
            if (config('queue-monitor.ui.allow_purge')) {
                $this->delete('purge', '\romanzipp\QueueMonitor\Controllers\PurgeMonitorsController')->name('queue-monitor::purge');
            }
            */
        };
    }
}
