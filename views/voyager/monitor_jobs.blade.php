@extends('voyager::master')

@section('page_header')
    <div class="page-monitor-jobs container-fluid">
        <h1 class="page-title">
            <i class="glyphicon glyphicon-cog"></i> @lang("Queue Monitor")
        </h1>
        {{--
        // BOTOES DE ACAO //
        <a href="https://lar.linklei.com.br/admin/ez-user/create" class="btn btn-success btn-add-new">
            <i class="voyager-plus"></i> <span>Adicionar</span>
        </a>
        <a class="btn btn-danger" id="bulk_delete_btn"><i class="voyager-trash"></i> <span>Exclusão em massa</span></a>
        <a href="https://lar.linklei.com.br/admin/ez-user/order" class="btn btn-primary btn-add-new">
            <i class="voyager-list"></i> <span>Ordenar</span>
        </a>
        --}}
    </div>
@endsection

@section('content')
    <div class="page-monitor-jobs page-content browse container-fluid">
        <div class="alerts"></div>

        @isset($metrics)
            <p>@lang("Métricas nos últimos :days dias.", ['days' => $timeFrame])</p>
            <div class="div-metrics">

                @foreach($metrics->all() as $metric)
                    @include('queue-monitor::voyager.partials.metrics-card', [
                        'metric' => $metric,
                    ])
                @endforeach
            </div>
        @endisset

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <h2 class="sub-title">@lang("Filtros")</h2>
                        <div class="row">
                            <form action="" method="get">
                                <div class="form-group col-sm-6">
                                    <label for="filter_queues">@lang('Queues')</label>
                                    <select name="queue" id="filter_queues" class="form-control">
                                        <option value="all">All</option>
                                        @foreach($list_queue_types as $queue)
                                            <option @if($filters['queue'] === $queue) selected @endif value="{{ $queue }}">
                                                {{ $queue }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-sm-6">
                                    <label for="filter_show">@lang('Show jobs')</label>
                                    <select name="type" id="filter_show" class="form-control">
                                        @foreach($list_job_status_data as $job_status_key => $job_status_data)
                                            <option @if($filters['type'] === $job_status_key) selected @endif value="{{ $job_status_key }}">{{ $job_status_data['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-sm-12">
                                    <button type="submit" class="btn btn-primary">
                                        @lang('Filter')
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="row">
                            <form action="{{ route('queue-monitor::batch_action') }}" id="formBatchAction" class="form-inline" method="get">
                                <div class="form-group col-sm-6">
                                    <label for="select_batch_actions">@lang('Ações em lote')</label>
                                    <select name="action" id="select_batch_actions" class="form-control">
                                        <option value="">---</option>
                                        <option value="destroy">@lang("Delete")</option>
                                        <option value="restart_job_monitor">@lang("Restart")</option>
                                    </select>
                                    <input type="hidden" value="" name="ids" id="input_ids" />
                                    <button type="submit" class="btn btn-primary">
                                        @lang('Executar')
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive">
                            {{--
                            <table id="tableEngagementHistoric" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Total Usuários</th>
                                        <th>Usuários Ativos</th>
                                        <th>Usuários status cadastro incompleto</th>
                                        <th>Usuários email verificado</th>
                                        <th>Usuários OAB verificada</th>
                                        <th>Usuários email e OAB verificados</th>
                                        <th>Total LinkLikes</th>
                                        <th>Total Artigos</th>
                                        <th>Total Posts</th>
                                        <th>Total Peças e Modelos</th>
                                        <th>Total Comentários</th>
                                        <th>Total Grupos de Discussão</th>
                                        <th>Total usuários adicionados a rede pessoal</th>
                                        <th>Total usuários distintos adicionados a rede pessoal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                            --}}

                            <table id="tableListJobs" class="table table-hover table-condensed">
                                <thead>
                                <tr>
                                    <th class="dt-not-orderable">
                                        <input type="checkbox" class="select_all"> #
                                    </th>
                                    <th class="actions dt-not-orderable">
                                        {{ __('voyager::generic.actions') }}
                                    </th>
                                    <th class="">@lang('Status')</th>
                                    <th class="">@lang('Queue')</th>
                                    <th class="">@lang('Job')</th>
                                    <th class="">@lang('Details')</th>
                                    <th class="">@lang('Progress')</th>
                                    <th class="">@lang('Duration')</th>
                                    <th class="">@lang('Started')</th>
                                    <th class="">@lang('Error')</th>
                                    @if(config('queue-monitor.ui.show_custom_data'))
                                        <th class="">@lang('Custom Data')</th>
                                    @endif
                                    <th class="">@lang('Payload')</th>
                                </tr>
                                </thead>
                                <tbody>
                                    @forelse($jobs as $job)
                                        <tr class="item_{{ $job->id }}">
                                            <td>
                                                <input type="checkbox" name="row_id" class="input_check" id="checkbox_{{ $job->id }}" value="{{ $job->id }}">
                                                {{ $job->id }}
                                            </td>
                                            <td class="">
                                                <button class="btn btn-danger btn-delete" data-id="{{ $job->id }}">@lang("Delete")</button>
                                                <button class="btn btn-primary btn-restart" data-id="{{ $job->id }}">@lang("Restart")</button>
                                            </td>
                                            <td class="">
                                                {!! $job->getJobStatusHtml() !!}
                                                {{--
                                                @if(!$job->isFinished())
                                                    <div class="label label-primary">
                                                        @lang('Running')
                                                    </div>
                                                @elseif($job->hasSucceeded())
                                                    <div class="label label-success">
                                                        @lang('Success')
                                                    </div>
                                                @else
                                                    <div class="label label-danger">
                                                        @lang('Failed')
                                                    </div>
                                                @endif
                                                --}}
                                            </td>
                                            <td>
                                                <div class="label label-default">{{ $job->queue }}</div>
                                            </td>
                                            <td>
                                                {{ $job->getBaseName() }}
                                                @if($job->uuid)
                                                    <br />
                                                    <div class="label label-default label-uuid">
                                                        {{ $job->uuid }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="text-detail">
                                                    <span class="text-title">@lang('Queue'):</span>
                                                    <span class="text-muted">{{ $job->queue }}</span>
                                                </div>

                                                <div class="text-detail">
                                                    <span class="text-title">@lang('Attempt'):</span>
                                                    <span class="text-muted">{{ $job->attempt }}</span>
                                                </div>
                                            </td>

                                            <td>
                                                @if($job->progress !== null)
                                                    <div class="text-detail">
                                                        <div class="progress active progress-striped" >
                                                            <div class="progress-bar progress-bar-success" style="width: {{ $job->progress }}%;"></div>
                                                        </div>
                                                        <div class="text-muted">
                                                            {{ $job->progress }}%
                                                        </div>
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            <td class="">
                                                {{ $job->getElapsedInterval()->format('%H:%I:%S') }}
                                            </td>
                                            <td class="">
                                                {{ $job->started_at->translatedFormat('d/m/Y H:i:s') }}
                                                <div class="text-detail">
                                                    <div class="text-muted">{{ $job->started_at->diffForHumans() }}</div>
                                                </div>
                                            </td>
                                            <td class="">
                                                @if($job->hasFailed() && $job->exception_message !== null)
                                                    <textarea rows="4" class="form-control" readonly>{{ $job->exception_message }}</textarea>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            @if(config('queue-monitor.ui.show_custom_data'))
                                                <td class="">
                                                    <textarea rows="4" class="form-control" readonly>{{ json_encode($job->getData(), JSON_PRETTY_PRINT) }}</textarea>
                                                </td>
                                            @endif
                                            <td class="">
                                                <textarea rows="4" class="form-control" readonly>{{ json_encode($job->payload, JSON_PRETTY_PRINT) }}</textarea>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="100" class="">
                                                <div class="">
                                                    @lang('No Jobs')
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        </div>
                        <div class="pull-left">
                            @lang('Showing')
                            @if($jobs->total() > 0)
                                <span class="text-muted">{{ $jobs->firstItem() }}</span> @lang('to')
                                <span class="text-muted">{{ $jobs->lastItem() }}</span> @lang('of')
                            @endif
                            <span class="text-muted">{{ $jobs->total() }}</span> @lang('result')
                        </div>
                        <div class="pull-right">

                            @if($jobs->hasPages())
                                <nav>
                                    <ul class="pagination">
                                        <li class=" @if($jobs->onFirstPage()) active @endif ">
                                            <a href="{{ $jobs->previousPageUrl() }}" class="">
                                                @lang("« Anterior")
                                            </a>
                                        </li>
                                        <li class=" @if(!$jobs->hasMorePages()) active @endif ">
                                            <a href="{{ $jobs->url($jobs->currentPage() + 1) }}">
                                                @lang("Próximo »")
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            @endif
                            {{--
                            <a class="btn- @if(!$jobs->onFirstPage()) bg-gray-200 hover:bg-gray-300 cursor-pointer @else text-gray-600 bg-gray-100 cursor-not-allowed @endif rounded"
                               @if(!$jobs->onFirstPage()) href="{{ $jobs->previousPageUrl() }}" @endif>
                                @lang('Previous')
                            </a>
                            <a class="py-2 px-4 mx-1 text-xs font-medium @if($jobs->hasMorePages()) bg-gray-200 hover:bg-gray-300 cursor-pointer @else text-gray-600 bg-gray-100 cursor-not-allowed @endif rounded"
                               @if($jobs->hasMorePages()) href="{{ $jobs->url($jobs->currentPage() + 1) }}" @endif>
                                @lang('Next')
                            </a>
                            --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function () {

            let $tableListJobs = $("#tableListJobs");

            // Seleciona/deseleciona todas as linhsa da tabela:
            $tableListJobs.find(".select_all").on('change', function(e) {
                if (this.checked) {
                    // Seleciona todos:
                    $tableListJobs.find('.input_check').prop('checked', true);
                } else {
                    // Deseleciona todos:
                    $tableListJobs.find('.input_check').prop('checked', false);
                }
            });

            $tableListJobs.find(".btn-delete").on('click', function(e) {
                e.preventDefault();
                let $this = $(this);
                let id = $this.data('id');

                Swal.fire({
                    html: "<p>Tem certeza que deseja deletar este Job?</p>",
                    icon: 'warning',
                    showCancelButton: true,
                    showConfirmButton: true,
                    scrollbarPadding: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Não, cancelar',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        api
                            .delete(route('queue-monitor::delete_job_monitor', [ id ]))
                            .then((response) => {
                                if (response.status) {
                                    Swal.fire({
                                        html: "Job removido",
                                        icon: "success",
                                    });
                                    $tableListJobs.find(".item_" + id).remove();
                                }

                            }).catch((response) => {
                                console.error("Error", response);
                                Swal.fire({
                                    html: "Ops! Ocorreu um erro inesperado.",
                                    icon: "error",
                                });
                        });

                    },
                });
            });

            $tableListJobs.find(".btn-restart").on('click', function(e) {
                e.preventDefault();
                let $this = $(this);
                let id = $this.data('id');

                Swal.fire({
                    html: "<p>Tem certeza que deseja Reiniciar este Job?</p>",
                    icon: 'info',
                    showCancelButton: true,
                    showConfirmButton: true,
                    scrollbarPadding: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    confirmButtonText: 'Sim, enviar para a fila!',
                    cancelButtonText: 'Não, cancelar',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        api
                            .post(route('queue-monitor::restart_job_monitor', [ id ]))
                            .then((response) => {
                                if (response.data.status) {
                                    Swal.fire({
                                        html: "Job enviado para a fila<br><pre>" + response.data.message + "</pre>",
                                        icon: "success",
                                    });
                                    $tableListJobs.find(".item_" + id).remove();
                                } else {
                                    Swal.fire({
                                        html: "<pre>" + response.data.message + "</pre>",
                                        icon: "error",
                                    });
                                }
                            }).catch((response) => {
                                console.error("Error", response);
                                Swal.fire({
                                    html: "Ops! Ocorreu um erro inesperado.",
                                    icon: "error",
                                });
                            });
                    },
                });
            });

            let $formBatchAction = $("#formBatchAction");
            $formBatchAction.on('submit', function (e) {
                e.preventDefault();
                let $inputIds = $formBatchAction.find('#input_ids');
                let $selectBatchActions = $formBatchAction.find('#select_batch_actions');

                $inputIds.val('');
                let listIds = [];
                $tableListJobs
                    .find('.input_check:checked')
                    .serializeArray()
                    .map(function(item, i) {
                        listIds.push(item.value);
                    });
                $inputIds.val(listIds);

                let action = $selectBatchActions.val();
                if (action && listIds.length > 0) {

                    Swal.fire({
                        html: `<p>Tem certeza que deseja executar [${action}] para ${listIds.length} Jobs?</p>`,
                        icon: 'info',
                        showCancelButton: true,
                        showConfirmButton: true,
                        scrollbarPadding: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        confirmButtonText: 'Sim!',
                        cancelButtonText: 'Não, cancelar',
                        customClass: {
                            confirmButton: 'btn btn-primary',
                            cancelButton: 'btn btn-secondary'
                        },
                        buttonsStyling: false,
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            let formData = new FormData($formBatchAction[0]);
                            api
                                .post(route('queue-monitor::batch_action'), formData)
                                .then((response) => {
                                    let message = '';
                                    if (response.data.list_messages.length) {

                                        response.data.list_messages.map((msg, i) => {
                                            message += `<li>${msg}</li>`;
                                        });
                                    }

                                    Swal.fire({
                                        html: `Resultado: <ul style="text-align: left;padding: 0 0 0 24px;">${message}</ul>`,
                                        icon: "success",
                                    }).then((res) => {
                                        window.location.reload();
                                    });

                                }).catch((response) => {
                                console.error("Error", response);
                                Swal.fire({
                                    html: "Ops! Ocorreu um erro inesperado.",
                                    icon: "error",
                                });
                            });
                        }
                    });
                }
                return false;
            });
        });
    </script>
@endsection
