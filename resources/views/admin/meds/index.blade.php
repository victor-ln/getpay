@extends('layouts/contentNavbarLayout')

@section('title', 'Gestão de Disputas (MED)')
@vite('resources/assets/js/admin-meds.js')

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Admin /</span> Gestão de Disputas (MED)
</h4>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Disputas por Liquidante</h5>
        <small class="text-muted">Veja as disputas de MED recebidas de cada liquidante ativa.</small>
    </div>

    @if($banks->isEmpty())
    <div class="card-body">
        <div class="alert alert-warning" role="alert">
            Nenhum banco/liquidante ativo encontrado no sistema.
        </div>
    </div>
    @else
    <div class="card-body">
        {{-- Estrutura das Abas --}}
        <ul class="nav nav-tabs" role="tablist">
            @foreach ($banks as $bank)
            <li class="nav-item">
                <button
                    type="button"
                    class="nav-link @if($loop->first) active @endif"
                    role="tab"
                    data-bs-toggle="tab"
                    data-bs-target="#navs-med-{{ $bank->id }}"
                    aria-controls="navs-med-{{ $bank->id }}"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    data-bank-id="{{ $bank->id }}">
                    {{ $bank->name }}
                </button>
            </li>
            @endforeach
        </ul>

        {{-- Conteúdo das Abas --}}
        <div class="tab-content">
            @foreach ($banks as $bank)
            <div class="tab-pane fade @if($loop->first) show active @endif" id="navs-med-{{ $bank->id }}" role="tabpanel">
                <div class="pt-4" @if($loop->first) data-loaded="true" @endif>
                    @if($loop->first)
                    {{-- A primeira aba já vem com os dados carregados --}}
                    @include('_partials.meds-table', [
                    'med' => $initialMedData['med'] ?? [],
                    'total' => $initialMedData['total'] ?? 0,
                    'pagination' => $initialMedData['pagination'] ?? []
                    ])
                    @else
                    {{-- As outras abas mostram um estado de carregamento --}}
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">A carregar disputas...</p>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection