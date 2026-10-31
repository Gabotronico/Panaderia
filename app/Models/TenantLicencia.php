<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Licencia de una panadería, tal como la dejó el último mensaje del panel
 * central. Es de la base central: el estado se consulta desde adentro de la
 * panadería (para los cupos) y desde el panel del sistema (para mostrarlo).
 */
class TenantLicencia extends Model
{
    protected $table = 'tenant_licenses';

    protected $fillable = [
        'tenant_id',
        'status',
        'plan',
        'max_admins',
        'max_cajeros',
        'valid_until',
        'version',
        'synced_at',
        'payload',
    ];

    protected $casts = [
        'max_admins' => 'integer',
        'max_cajeros' => 'integer',
        'valid_until' => 'datetime',
        'version' => 'integer',
        'synced_at' => 'datetime',
        'payload' => 'array',
    ];

    /**
     * Siempre la conexión central: con la tenancy iniciada la conexión por
     * defecto es la de la panadería, y esta tabla no vive ahí.
     */
    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
