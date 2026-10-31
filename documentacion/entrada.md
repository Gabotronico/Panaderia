# Entrada al sistema con cuenta única

Una sola cuenta (correo y contraseña) para todas las panaderías donde la
persona tenga usuario. Si tiene una sola, entra directo; si tiene varias,
elige.

- **Web**: `https://obrador.incgroupbo.com/cuenta/entrar`
- **Escritorio**: el `.exe` abre esa misma dirección.

## Cómo está armado

| Pieza | Dónde | Qué hace |
|---|---|---|
| `personas` | Base central | Correo, nombre y contraseña. Única por correo. |
| `persona_tenant` | Base central | Qué usuario de cada panadería corresponde a la persona. |
| `tokens_entrada` | Base central | Pase de un solo uso (hash, 2 minutos) para abrir la sesión en la panadería. |

Cada panadería **conserva su tabla de usuarios** con el rol, el almacén y todo
lo que depende de su base. La cuenta global solo decide quién es y a cuáles
puede entrar.

## Flujo

```
/cuenta/entrar  (correo + contraseña)
        │
        ├─ no coincide con ninguna persona → error genérico
        │
        ├─ una panadería  → pase → /<panadería>/entrada/<token> → sesión
        │
        └─ dos o más      → /cuenta/panaderia → elegir → pase → sesión
```

El pase se consume una sola vez y solo sirve en la panadería para la que se
emitió. Una panadería suspendida o revocada aparece deshabilitada en el
selector.

## Alta y cambios de usuarios

Cada vez que se crea un usuario en una panadería (asistente de instalación,
alta desde el panel central, alta desde Usuarios) se crea o reutiliza la
persona por correo:

- Mismo correo = misma cuenta. Si la persona ya existía, la contraseña que se
  acaba de definir pasa a ser la de **todas** sus panaderías.
- Cambiar el correo de un usuario mueve la cuenta; si ese correo ya pertenece
  a otra persona, se rechaza.
- Borrar un usuario quita su vínculo; si era el último, la persona se borra.

## Usuarios que ya existían

```bash
php artisan obrador:personas-sincronizar --dry   # ver qué haría
php artisan obrador:personas-sincronizar         # crear cuentas y vínculos
```

La contraseña de la persona queda con el hash del primer usuario que tenga ese
correo. Si un mismo correo aparece en varias panaderías con contraseñas
distintas, el comando las lista al final: no se puede saber si eran iguales
(el hash tiene sal), así que hay que decidir cuál vale y volver a definirla.

## Notas

- El login de cada panadería (`/<panadería>/login`) sigue funcionando como
  respaldo para soporte y enlaces directos.
- El panel de INC Group (`/panel`) es otra cosa: usa la guarda `superadmin`.
- La pantalla por código de panadería de la app de escritorio (`/interno/escritorio`)
  quedó sin uso desde que la ventana abre el login central.
