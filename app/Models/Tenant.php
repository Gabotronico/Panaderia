<?php

namespace App\Models;

use Illuminate\Http\Request;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Una panadería cliente. Vive en la base central; todos sus datos de negocio
 * (ventas, inventario, planillas, usuarios) están en su propia base, que se
 * crea y migra al darla de alta. El id es un slug legible ("luna") que
 * también da nombre a esa base: obrador_luna.
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Sin generador de ids, stancl asume una clave autoincremental entera y
     * castea "luna" a 0. El id siempre es el slug que se pasa al crearla.
     */
    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Dirección de la panadería en el dominio de la petición:
     * obrador.incgroupbo.com/luna.
     */
    public function urlDesde(Request $request): string
    {
        return $request->getSchemeAndHttpHost().'/'.$this->getTenantKey();
    }
}
