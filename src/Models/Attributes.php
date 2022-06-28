<?php

namespace romanzipp\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Builder;

trait Attributes
{
    /// Scopes /////////////////////////////

    public function scopeOrdered(Builder $query): void
    {
        $query
            ->orderBy('reserved_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /// Other Methods ///////////////////////////

    public function getDisplayNameAttribute()
    {
        return $this->payload['displayName'];
    }

    public function getMaxTriesAttribute()
    {
        return $this->payload['maxTries'];
    }

    public function getDelayAttribute()
    {
        return $this->payload['delay'];
    }

    public function getTimeoutAttribute()
    {
        return $this->payload['timeout'];
    }

    public function getTimeoutAtAttribute()
    {
        return !is_null($this->payload['timeout_at']) ? new \Carbon\Carbon($this->payload['timeout_at']) : null;
    }

    public function getCommandNameAttribute()
    {
        return $this->payload['data']['commandName'];
    }

    public function getCommandAttribute()
    {
        return unserialize($this->payload['data']['command']);
    }

    public function getReservedAtFormatedAttribute()
    {
        return $this->reserved_at ? $this->reserved_at->translatedFormat('d/m/Y H:i:s') : '';
    }
}
