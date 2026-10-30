<x-guest-layout>
    {{-- Portada del dominio: no pertenece a ninguna panadería. Cada cliente
         entra por la ruta de la suya (obrador.incgroupbo.com/luna). --}}
    <div class="auth-card">
        <div class="auth-header">
            <h3>Obrador</h3>
            <p>Gestión para panaderías</p>
        </div>

        <div class="auth-body text-center">
            <p>
                Para ingresar, usá la dirección de tu panadería,
                por ejemplo <strong>{{ request()->getHttpHost() }}/tupanaderia</strong>.
            </p>

            <a href="{{ route('central.login') }}" class="small text-muted text-decoration-none">
                <i class="fas fa-user-shield me-1"></i>Administración del servicio
            </a>
        </div>
    </div>
</x-guest-layout>
