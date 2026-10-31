# Checklist: desplegar licencias y actualizador

Orden importa: **primero Obrador, después el central**. Si el panel se despliega
antes, los envíos de licencia quedan en 202 (`synced: false`) hasta que Obrador
tenga el endpoint; no se pierde nada, pero hay que reintentar.

Ventana: la app se reinicia sola al reconstruir la imagen (30-60 s).

## 1. Generar el secreto (una vez)

```bash
openssl rand -hex 32
```

Guardalo: el **mismo** valor va en los dos lados.

## 2. Obrador (VPS)

```bash
cd /opt/obrador

# .env.produccion: agregar una línea
#   LICENCIA_SECRETO=<el secreto del paso 1>

git pull                     # trae el merge de RM (f711799 o posterior)
docker compose up -d --build

# tiene que terminar con "ready to handle connections"
docker compose logs -f app
```

Comprobar que el endpoint existe y rechaza sin firma:

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://obrador.incgroupbo.com/interno/licencias
# esperado: 401 (sin firma) — nunca 404 ni 500
```

## 3. Central (INC Group)

```bash
cd <repo incgroup_internal_app>
supabase migration list      # solo deben quedar pendientes las 2 de licencias
supabase db push
```

```bash
supabase secrets set \
  OBRADOR_URL=https://obrador.incgroupbo.com \
  OBRADOR_LICENSE_SECRET=<el secreto del paso 1>

supabase functions deploy tenant-license-admin tenant-license-overview
```

Panel (`incgroup_web`):

```bash
npm run build                # ya está compilado en el repo
# rebuild + deploy del contenedor incgroup-web
```

## 4. Verificaciones

1. `admin.incgroupbo.com` → **Obrador → Licencias**: aparece **Panadería Luna**
   con plan *Ilimitado*, estado *Activa*, sin vencimiento.
2. Luna → menú de acciones → **Reenviar a Obrador**. Debe decir
   *"Obrador recibió la licencia"* (si dice 202, repetir).
3. En Obrador, `luna` → Usuarios: crear un cajero funciona (sin cupo).
   En `sol` (Básico, 2 cajeros): el tercero se rechaza con
   *"El plan permite 2 cajeros y ya hay 2"*.

## 5. Si algo falla

| Síntoma | Causa probable |
|---|---|
| `/interno/licencias` → 503 | Falta `LICENCIA_SECRETO` en Obrador o no se reinició |
| Panel dice "no sincronizado" | Secreto distinto en la función o en Obrador |
| Panel vacío, sin panaderías | `tenant-license-overview` no llega a Obrador (`OBRADOR_URL`) |
| Migración falla por `license_model` | Quedó el check anterior: revisar que el bloque `do $$` de la migración corrió |

Rollback: `docker compose up -d --build` con el commit anterior en Obrador; las
migraciones del central son aditivas y no rompen lo existente.

## Lo que no hay que hacer

- No borrar volúmenes (`down -v`): se pierden las bases de las panaderías.
- No tocar `.env.produccion` más allá de agregar `LICENCIA_SECRETO`.
- No aplicar la migración con `psql` a mano: usar `supabase db push` para que
  quede registrada en el historial.
