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
                                        @foreach($queues as $queue)
                                            <option @if($filters['queue'] === $queue) selected @endif value="{{ $queue }}">
                                                {{ __($queue) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-sm-6">
                                    <label for="filter_show">@lang('Show jobs')</label>
                                    <select name="type" id="filter_show" class="form-control">
                                        <option @if($filters['type'] === 'all') selected @endif value="all">@lang('All')</option>
                                        <option @if($filters['type'] === 'running') selected @endif value="running">@lang('Running')</option>
                                        <option @if($filters['type'] === 'failed') selected @endif value="failed">@lang('Failed')</option>
                                        <option @if($filters['type'] === 'succeeded') selected @endif value="succeeded">@lang('Succeeded')</option>
                                    </select>
                                </div>

                                <div class="form-group col-sm-12">
                                    <button type="submit" class="btn btn-primary">
                                        @lang('Filter')
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
                                    <th class="">@lang('Queue')</th>
                                    <th class="">@lang('Job')</th>
                                    <th class="">@lang('Run in')</th>
                                    <th class="">@lang('Max tries')</th>
                                    <th class="">@lang('Created at')</th>
                                    <th class="actions dt-not-orderable">
                                        {{ __('voyager::generic.actions') }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                    @forelse($jobs as $job)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="row_id" id="checkbox_{{ $job->id }}" value="{{ $job->id }}">
                                                {{ $job->id }}
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
                                                <button class="btn btn-danger" data-id="{{ $job->id }}">@lang("Delete")</button>
                                                <button class="btn btn-primary" data-id="{{ $job->id }}">@lang("Restart")</button>
                                                <button class="btn btn-default" data-id="{{ $job->id }}">@lang("Show Payload")</button>
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

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/jszip-2.5.0/af-2.3.7/b-2.2.2/b-colvis-2.2.2/b-html5-2.2.2/b-print-2.2.2/cr-1.5.5/date-1.1.2/fc-4.0.2/fh-3.2.2/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.2/sp-2.0.0/sl-1.3.4/sr-1.1.0/datatables.min.css"/>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs/jszip-2.5.0/af-2.3.7/b-2.2.2/b-colvis-2.2.2/b-html5-2.2.2/b-print-2.2.2/cr-1.5.5/date-1.1.2/fc-4.0.2/fh-3.2.2/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.2/sp-2.0.0/sl-1.3.4/sr-1.1.0/datatables.min.js"></script>

    <script>
        $(document).ready(function () {

            /*
            let tableListJobsOptions = {!! json_encode(
                [
                    "dom" => 'Bfrtlip',
                    "buttons" => [
                        'copyHtml5',
                        'excelHtml5',
                        'csvHtml5',
                        'pdfHtml5',
                        'colvis',
                    ],
                    //"order" => $orderColumn,
                    "language" => __('voyager::datatable'),
                    "columnDefs" => [
                        ['targets' => 'dt-not-orderable', 'searchable' =>  false, 'orderable' => false],
                    ],
                    "ordering" => true,
                    "processing" => true,
                    "serverSide" => true,
                    "ajax" => route('voyager.users_engagement.historic'),
                    //"columns" => \dataTypeTableColumns($dataType, $showCheckboxColumn),
                ]
            , true) !!};

            let $tableEngagementHistoric = $('#tableListJobs').DataTable(tableListJobsOptions);
            */
        });
    </script>
@endsection
