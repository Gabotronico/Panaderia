<x-guest-layout>
    {{-- Dominio central: no pertenece a ninguna panadería. Cada cliente entra
         por su propio subdominio (luna.obrador.com). --}}
    @php
        $host = request()->getHost();
        $esIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
    @endphp

    <div class="auth-card">
        <div class="auth-header">
            <h3>Obrador</h3>
            <p>Gestión para panaderías</p>
        </div>

        <div class="auth-body text-center">
            <p>
                Para ingresar, usá la dirección de tu panadería,
                @if ($esIp)
                    por ejemplo <strong>tupanaderia.localhost{{ request()->getPort() && ! in_array(request()->getPort(), [80, 443]) ? ':'.request()->getPort() : '' }}</strong>.
                @else
                    por ejemplo <strong>tupanaderia.{{ $host }}</strong>.
                @endif
            </p>

            <a href="{{ route('central.login') }}" class="small text-muted text-decoration-none">
                <i class="fas fa-user-shield me-1"></i>Administración del servicio
            </a>
        </div>
    </div>
</x-guest-layout>
