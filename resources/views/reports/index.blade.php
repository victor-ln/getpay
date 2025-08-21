@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard - Analytics')

@section('vendor-style')
@vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('page-script')
@vite('resources/assets/js/dashboards-analytics.js')
@endsection



@section('content')
<div class="row">

    <!-- Total Revenue -->
    <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6">
        <div class="card">
            <div class="row row-bordered g-0">
                <div class="col-lg-8">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="card-title mb-0">
                            <h5 class="m-0 me-2">Total Transactions</h5>
                        </div>
                        {{-- <div class="dropdown">
                            <button class="btn p-0" type="button" id="totalRevenue" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded bx-lg text-muted"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="totalRevenue">
                                <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                                <a class="dropdown-item" href="#" onclick="renderRevenue()">30 Last days</a>
                                <a class="dropdown-item" href="#" onclick="renderRevenue()">7 Last days</a>
                                <a class="dropdown-item" href="#" onclick="renderRevenue()">30 Last days</a>
                            </div>
                        </div> --}}
                    </div>
                    <div id="totalRevenueChart" class="px-3"></div>
                </div>
                <div class="col-lg-4 d-flex align-items-center">
                    <div class="card-body px-xl-9">
                        <div class="text-center mb-6">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary">
                                    <script>
                                        document.write(new Date().getFullYear())
                                    </script>
                                </button>
                                <button type="button"
                                    class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">2024</a></li>
                                </ul>
                            </div>
                        </div>

                        <div id="growthChart"></div>
                        <div class="text-center fw-medium my-6">{{ $growthData['percentage'] }}% Transactions Growth
                        </div>

                        <div class="d-flex gap-3 justify-content-between">
                            <div class="d-flex">
                                <div class="avatar me-2">
                                    <span class="avatar-initial rounded-2 bg-label-primary"><i
                                            class="bx bx-dollar bx-lg text-primary"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>
                                        Pay IN
                                    </small>
                                    <h6 class="mb-0">R${{ number_format($confirmedMounthlyPayments, 2, ',', '.')
                                        }}
                                    </h6>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="avatar me-2">
                                    <span class="avatar-initial rounded-2 bg-label-info"><i
                                            class="bx bx-wallet bx-lg text-info"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>
                                        Pay Out
                                    </small>
                                    <h6 class="mb-0">R${{ number_format($confirmedPayOutMounthlyPayments, 2, ',', '.')
                                        }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ Total Revenue -->



    <div class="col-12 col-md-8 col-lg-12 col-xxl-4 order-3 order-md-2">
        <div class="row">
            <div class="col-6 mb-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img src="{{asset('assets/img/icons/unicons/paypal.png')}}" alt="paypal"
                                    class="rounded">
                            </div>
                            {{-- <div class="dropdown">
                                <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded text-muted"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                                    <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                </div>
                            </div> --}}
                        </div>
                        <p class="mb-1">Payouts</p>
                        <h4 class="card-title mb-3">$0,00</h4>
                        <small class="text-danger fw-medium"><i class='bx bx-down-arrow-alt'></i> -1%</small>
                    </div>
                </div>
            </div>
            <div class="col-6 mb-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img src="{{asset('assets/img/icons/unicons/cc-primary.png')}}" alt="Credit Card"
                                    class="rounded">
                            </div>
                            {{-- <div class="dropdown">
                                <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded text-muted"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="cardOpt1">
                                    <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                </div>
                            </div> --}}
                        </div>
                        <p class="mb-1">
                            @if(Auth::check() && Auth::user()->level == 'admin')
                            Partners Distribution
                            @else
                            Fee Applied
                            @endif
                        </p>
                        <h4 class="card-title mb-3">$0,00</h4>
                        <small class="text-success fw-medium"><i class='bx bx-up-arrow-alt'></i> +1%</small>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-sm-row flex-column gap-10">
                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                <div class="card-title mb-6">
                                    <h5 class="text-nowrap mb-1">Last 2 Months Report</h5>
                                    <span class="badge bg-label-warning">Month {{ date('M') }}</span>
                                </div>
                                <div class="mt-sm-auto">
                                    <span class="text-success text-nowrap fw-medium"><i class='bx bx-up-arrow-alt'></i>
                                        0%</span>
                                    <h4 class="mb-0">0,00</h4>
                                </div>
                            </div>
                            <div id="profileReportChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>
<div class="row">
    <!-- Order Statistics -->
    <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <div class="card-title mb-0">
                    <h5 class="mb-1 me-2">Order Statistics</h5>
                    <p class="card-subtitle">R$ {{ number_format($orderStatistics['totalAmount'], 2, ',', '.') }}
                    </p>
                </div>
                {{-- <div class="dropdown">
                    <button class="btn text-muted p-0" type="button" id="orederStatistics" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded bx-lg"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="orederStatistics">
                        <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                    </div>
                </div> --}}
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-6">
                    <div class="d-flex flex-column align-items-center gap-1">
                        <h3 class="mb-1">{{ $orderStatistics['totalValue'] }}</h3>
                        <small>Total Orders</small>
                    </div>
                    <div id="orderStatisticsChart"></div>
                </div>
                <ul class="p-0 m-0">
                    <li class="d-flex align-items-center mb-5">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success"><i
                                    class='bx bx-money-withdraw'></i></span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0">Confirmed</h6>
                                <small>Only Pay In</small>
                            </div>
                            <div class="user-progress">
                                <h6 class="mb-0">R$ {{ number_format($orderStatistics['confirmedAmount'], 2, ',', '.')
                                    }}</h6>
                            </div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-5">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary"><i
                                    class='bx bx-wallet-alt'></i></span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0">Pending</h6>
                                <small>Only Pay In</small>
                            </div>
                            <div class="user-progress">
                                <h6 class="mb-0">R$ {{ number_format($orderStatistics['pendingAmount'], 2, ',', '.') }}
                                </h6>
                            </div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-5">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-info"><i
                                    class='bx bx-expand-vertical'></i></span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0">Canceled</h6>
                                <small>Only Pay In</small>
                            </div>
                            <div class="user-progress">
                                <h6 class="mb-0">R$ {{ number_format($orderStatistics['canceledAmount'], 2, ',', '.') }}
                                </h6>
                            </div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-secondary"><i class='bx bx-block'></i></span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0">Refused</h6>
                                <small>Only Pay In</small>
                            </div>
                            <div class="user-progress">
                                <h6 class="mb-0">R$ {{ number_format($orderStatistics['refusedAmount'], 2, ',', '.') }}
                                </h6>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!--/ Order Statistics -->

    <!-- Expense Overview -->
    <div class="col-md-6 col-lg-4 order-1 mb-6">
        <div class="card h-100">
            <div class="card-header nav-align-top">
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-tabs-line-card-income" aria-controls="navs-tabs-line-card-income"
                            aria-selected="true">Pay In</button>
                    </li>

                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content p-0">
                    <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                        <div class="d-flex mb-6">
                            <div class="avatar flex-shrink-0 me-3">
                                <img src="{{asset('assets/img/icons/unicons/wallet.png')}}" alt="User">
                            </div>
                            <div>
                                <p class="mb-0">Total Balance</p>
                                <div class="d-flex align-items-center">
                                    <h6 class="mb-0 me-1">$0,00</h6>
                                    <small class="text-success fw-medium">
                                        <i class='bx bx-chevron-up bx-lg'></i>
                                        0%
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div id="incomeChart"></div>
                        <div class="d-flex align-items-center justify-content-center mt-6 gap-3">
                            <div class="flex-shrink-0">
                                <div id="expensesOfWeek"></div>
                            </div>
                            <div>
                                <h6 class="mb-0">Payouts this week</h6>
                                <small>R${{ $comparisonString }} </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ Expense Overview -->

    <!-- Transactions -->
    <div class="col-md-6 col-lg-4 order-2 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Transactions</h5>
                <div class="dropdown">
                    <button class="btn text-muted p-0" type="button" id="transactionID" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded bx-lg"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID">
                        <a class="dropdown-item" href="{{ route('transactions.index') }}">View All</a>
                    </div>
                </div>
            </div>
            <div class="card-body pt-4">
                <ul class="p-0 m-0">
                    @foreach ( $transactions as $transaction)
                    <li class="d-flex align-items-center mb-6">
                        <div class="avatar flex-shrink-0 me-3">
                            <img src="{{asset('assets/img/icons/unicons/paypal.png')}}" alt="User" class="rounded">
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <small class="d-block">{{ $transaction->user->name }}</small>
                                <h6 class="fw-normal mb-0">
                                    @if ($transaction->type_transaction == 'IN')
                                    Pay In
                                    @else
                                    Pay Out
                                    @endif
                                </h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-2">
                                <h6 class="fw-normal mb-0 {{ $transaction->status_class }}">
                                    {!! $transaction->signed_amount !!}
                                </h6>

                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <!--/ Transactions -->
</div>

<script>
    // Dados para o dashboard
    var dashboardData = {
        currentYear: {
            {
                $currentYear
            }
        },
        previousYear: {
            {
                $previousYear
            }
        },
        revenueData: @json($revenueData),
        growthData: @json($growthData),
        profileReportData: @json($profileReportData),
        orderStatistics: @json($orderStatistics),
        incomeChart: @json($incomeChartData),
        weeklyExpenses: @json($weeklyExpensesData)
    };
</script>
@endsection