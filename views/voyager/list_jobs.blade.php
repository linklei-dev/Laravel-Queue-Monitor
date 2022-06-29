@extends('voyager::master')

@section('page_header')
    <div class="page-monitor-jobs container-fluid">
        <h1 class="page-title">
            <i class="glyphicon glyphicon-cog"></i> @lang("Jobs na Fila")
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
            <div class="div-metrics">
                <ul class="list-metrics">
                    @foreach($metrics->all() as $metric)
                        <li>
                            <span class="title">{{ $metric->title }}:</span>
                            <span class="value">{{ $metric->value }}</span>
                        </li>
                    @endforeach
                </ul>
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
                                    </select>
                                    <input type="hidden" value="" name="ids" id="input_ids" />
                                    <button type="submit" class="btn btn-primary">
                                        @lang('Executar')
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table id="tableListJobs" class="table table-hover table-condensed">
                                <thead>
                                <tr>
                                    <th class="dt-not-orderable">
                                        <input type="checkbox" class="select_all"> #
                                    </th>
                                    <th class="actions dt-not-orderable">
                                        {{ __('voyager::generic.actions') }}
                                    </th>
                                    <th class="">@lang('Queue')</th>
                                    <th class="">@lang('Job')</th>
                                    <th class="">@lang('Run in')</th>
                                    <th class="">@lang('Max tries')</th>
                                    <th class="">@lang('Created at')</th>
                                    <th class="">@lang('Payload')</th>
                                </tr>
                                </thead>
                                <tbody>
                                    @forelse($jobs as $job)
                                        <tr class="item_{{ $job->id }}">
                                            <td>
                                                <input type="checkbox" name="row_id" id="checkbox_{{ $job->id }}" class="input_check" value="{{ $job->id }}">
                                                {{ $job->id }}
                                            </td>
                                            <td class="">
                                                <button class="btn btn-danger btn-delete" data-id="{{ $job->id }}">@lang("Delete")</button>
                                            </td>
                                            <td>
                                                <div class="label label-default">{{ $job->queue }}</div>
                                            </td>
                                            <td>
                                                {{ $job->display_name }}
                                            </td>
                                            <td>
                                                {{ $job->reserved_at_formated }}
                                            </td>
                                            <td>
                                                {{ $job->max_tries }}
                                            </td>
                                            <td>
                                                {{ $job->created_at->translatedFormat('d/m/Y H:i:s') }}
                                            </td>
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
                            .delete(route('queue-monitor::delete_job_queue', [ id ]))
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
                                .post(route('queue-monitor::batch_action_job_queue'), formData)
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
