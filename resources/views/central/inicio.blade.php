<x-guest-layout>
    {{-- Dominio central: no pertenece a ninguna panadería. Cada cliente entra
         por su propio subdominio (luna.obrador.com). --}}
    <div class="auth-card">
        <div class="auth-header">
            <h3>Obrador</h3>
            <p>Gestión para panaderías</p>
        </div>

        <div class="auth-body text-center">
            <p class="mb-0">
                Para ingresar, usá la dirección de tu panadería,
                por ejemplo <strong>tupanaderia.{{ request()->getHost() }}</strong>.
            </p>
        </div>
    </div>
</x-guest-layout>
