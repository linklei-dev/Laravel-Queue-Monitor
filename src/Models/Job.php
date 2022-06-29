<?php

namespace romanzipp\QueueMonitor\Models;

use Illuminate\Support\Facades\Config;
use romanzipp\QueueMonitor\Models\Contracts\JobContract;

class Job extends \Illuminate\Database\Eloquent\Model implements JobContract
{
    use Attributes;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'queue',
        'payload',
        'attempts',
        'reserved_at',
        'available_at',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'reserved_at' => 'datetime',
        'available_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // $this->table = Config::get('queue.connections.' . (Config::get('queue.default', 'database')) . '.table', 'jobs');

        $this->setTable(config('queue.connections.' . config('queue.default') . '.table'), 'jobs');

        /*if ($connection = config('queue.default')) {
            $this->setConnection($connection);
        }*/
    }
}
