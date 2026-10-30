# Licencias de las panaderías

Cada panadería tiene un plan: cuántos administradores y cajeros puede tener y
hasta cuándo está habilitada. El plan **no se toca desde el panel de Obrador**:
lo administra el panel central de INC Group (`admin.incgroupbo.com →
Obrador → Licencias`), que es la fuente de verdad y se lo empuja a este
servidor.

## Cómo se guarda

`tenant_licenses` (base central, una fila por panadería):

| Campo | Para qué |
|---|---|
| `status` | `pending`, `active`, `suspended`, `revoked`. `expired` se calcula. |
| `plan` | `basico`, `estandar`, `pro`, `ilimitado`. |
| `max_admins` / `max_cajeros` | Cupos. `null` = sin límite. |
| `valid_until` | Vencimiento. `null` = no vence. |
| `version` | La del central. Solo se aplican versiones mayores. |
| `payload` | Último estado recibido, tal cual, para auditar. |

## Qué pasa con cada estado

| Estado | Efecto en la panadería |
|---|---|
| Sin licencia (recién creada) o `pending` | Funciona normal, sin cupos. |
| `active` | Normal. Al crear usuarios se respetan los cupos. |
| `expired` | Solo lectura: consultan y exportan, no registran ventas ni cambios. |
| `suspended` / `revoked` | No entra nadie (`403` con pantalla de acceso suspendido). |

Al renovar, el período se suma desde el vencimiento actual. Bajar de plan no
borra usuarios: los existentes siguen y se bloquea crear más hasta que haya
cupo.

## Cupos

`App\Support\Licencias\Licencia::bloqueoPorRol($rol)` es el único lugar donde
se decide. Lo usan `UserController@store` y `@update` (altas y cambios de rol).
Los roles con cupo son `Administrador` y `Cajero` (`config/licencias.php`); los
demás no tienen tope.

## Endpoint interno

Solo lo llama el panel central, firmado con HMAC SHA-256
(`X-Inc-Timestamp` + `X-Inc-Signature`):

```bash
# estado de las panaderías con su consumo
GET /interno/licencias

# aplicar el estado canónico de una licencia
POST /interno/licencia
```

`config/licencias.php`:

- `LICENCIA_SECRETO`: el mismo valor que `OBRADOR_LICENSE_SECRET` en la función
  `tenant-license-admin`. **Sin esto el endpoint responde 503.**
- `LICENCIA_TOLERANCIA`: segundos de margen para el timestamp (300 por
  defecto).

Los dos se configuran en `.env.produccion` (o `.env.local` en desarrollo) y se
aplican al reconstruir/reiniciar el contenedor.

## Pruebas

`tests/Feature/LicenciaTest.php` cubre: sin licencia no limita, el plan corta
al llegar al cupo, el plan ilimitado no corta, vencida deja solo lectura,
suspendida bloquea, firma inválida rechaza, versión vieja no pisa y el listado
de consumo.
