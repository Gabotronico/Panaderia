@extends('layouts.app')

@section('page-title', 'Acerca del sistema')

@section('content')
<x-page-header title="Acerca del sistema" icon="circle-info"
    subtitle="Información de la aplicación, desarrollo y soporte." />

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-4 mb-4">
                    <div class="rounded-4 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:72px; height:72px; font-size:1.8rem;">
                        <i class="fas fa-bread-slice"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold mb-1">{{ config('app.name') }}</h2>
                        <p class="text-muted mb-2">Sistema local de ventas, inventario, caja y administración para panaderías.</p>
                        <span class="badge text-bg-light border fw-semibold">
                            Versión {{ config('nativephp.version', '1.0.0') }}
                        </span>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-4 align-items-center">
                    <div class="col-md-7">
                        <div class="text-uppercase text-muted fw-bold mb-2" style="font-size:.7rem; letter-spacing:.08em;">
                            Desarrollo y soporte
                        </div>
                        <h3 class="h5 fw-bold mb-2">INC Group</h3>
                        <p class="text-muted mb-1">Software a medida para empresas y emprendedores.</p>
                        <p class="fw-semibold text-primary mb-0">Desarrollamos soluciones, creamos futuro.</p>
                    </div>
                    <div class="col-md-5">
                        <div class="d-grid gap-2">
                            <a href="https://www.linkedin.com/company/inc-groupbo/"
                               target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">
                                <i class="fab fa-linkedin me-2"></i>LinkedIn
                            </a>
                            <a href="https://www.facebook.com/profile.php?id=61592687964822"
                               target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">
                                <i class="fab fa-facebook me-2"></i>Facebook
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
