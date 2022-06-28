<?php

namespace romanzipp\QueueMonitor\Models;

use Illuminate\Support\Facades\Config;
use romanzipp\QueueMonitor\Models\Contracts\JobFailedContract;

class JobFailed extends \Illuminate\Database\Eloquent\Model implements JobFailedContract
{
    use Attributes;

    public $timestamps = false;

    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::get('queue.failed.table', 'job_fails');
    }
}
