<div class="div-item">

    <div class="panel panel-default">
        <div class="panel-heading" title="{{ __('Last :days days', ['days' => config('queue-monitor.ui.metrics_time_frame') ?? 14]) }}">
            {{ $metric->title }}
        </div>
        <div class="panel-body">

            {{ $metric->format($metric->value) }}

            @if($metric->previousValue !== null)

                <div class="text-has-changed {{ $metric->hasChanged() ? ($metric->hasIncreased() ? 'text-success' : 'text-danger') : 'text-seccondary' }}">
                    @if($metric->hasChanged())
                        @if($metric->hasIncreased())
                            @lang('Up from')
                        @else
                            @lang('Down from')
                        @endif
                    @else
                        @lang('No change from')
                    @endif
                    {{ $metric->format($metric->previousValue) }}
                </div>
            @endif
        </div>

    </div>

</div>
