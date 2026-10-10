<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Lo consulta Caddy (on_demand_tls → ask) antes de pedir un certificado
 * HTTPS para un dominio. Solo se emiten para el dominio central, los
 * subdominios de panaderías existentes y sus dominios propios: si no,
 * cualquiera podría apuntar un dominio al servidor y agotar el cupo de
 * certificados de Let's Encrypt.
 *
 * Solo responde a pedidos de la red interna de Docker: desde afuera llegan
 * por Caddy, con la IP pública del cliente en X-Forwarded-For.
 */
class PermisoCertificadoController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless(IpUtils::isPrivateIp((string) $request->ip()), 404);

        $dominio = strtolower(trim((string) $request->query('domain')));

        return response('', $this->permitido($dominio) ? 200 : 404);
    }

    private function permitido(string $dominio): bool
    {
        if ($dominio === '') {
            return false;
        }

        $centrales = config('tenancy.central_domains');

        if (\in_array($dominio, $centrales, true)) {
            return true;
        }

        // luna.obrador.com: el subdominio "luna" registrado como dominio.
        foreach ($centrales as $central) {
            if (str_ends_with($dominio, '.'.$central)) {
                $subdominio = substr($dominio, 0, -\strlen('.'.$central));

                return ! str_contains($subdominio, '.')
                    && Domain::query()->where('domain', $subdominio)->exists();
            }
        }

        // Dominio propio de una panadería: panaderialuna.com.
        return Domain::query()->where('domain', $dominio)->exists();
    }
}
