# login-php

Aplicación de autenticación y gestión de usuarios en PHP, estructurada con
**Clean Architecture / puertos y adaptadores** (hexagonal).

---

## La idea en una frase

Las dependencias apuntan **siempre hacia adentro**. El dominio no sabe que
existe MySQL, ni PHPMailer, ni `$_SESSION`, ni HTTP. Declara *qué* necesita
(puertos) y la infraestructura provee *cómo* (adaptadores).

```
        ┌─────────────────────────────────────────────┐
        │              Presentation                    │
        │   Router · Controllers · Vistas              │
        │        │                                     │
        │        ▼                                     │
        │  ┌───────────────────────────────────┐      │
        │  │        Application                 │      │
        │  │    Casos de uso · DTOs             │      │
        │  │        │                           │      │
        │  │        ▼                           │      │
        │  │  ┌─────────────────────────┐      │      │
        │  │  │       Domain             │      │      │
        │  │  │  Entidades · VOs         │      │      │
        │  │  │  PUERTOS (interfaces)    │      │      │
        │  │  └─────────────────────────┘      │      │
        │  └───────────────────────────────────┘      │
        └─────────────────────────────────────────────┘
                          ▲
                          │ implementan los puertos
        ┌─────────────────────────────────────────────┐
        │             Infrastructure                   │
        │  PdoUserRepository · PhpMailerMailer         │
        │  NativeSession · BcryptHasher · Storage      │
        └─────────────────────────────────────────────┘
```

El único punto donde un puerto se ata a su adaptador es
`src/Infrastructure/Container.php` (composition root). Cambiar de motor de
correo o de base de datos es editar ese archivo y nada más.

---

## Estructura

```
src/
├── Domain/                  ← núcleo, sin dependencias externas
│   ├── Entity/              User, Role
│   ├── ValueObject/         Email, Username, PlainPassword, HashedPassword,
│   │                        RecoveryToken, UserId, UserStatus, RoleName,
│   │                        Permission (enum), PermissionSet
│   ├── Port/                ← LAS INTERFACES (los "puertos")
│   │                        UserRepository, RoleRepository, PasswordHasher,
│   │                        Mailer, SessionStorage, TokenGenerator,
│   │                        ImageStorage, Clock, LoginLog
│   └── Exception/           errores de negocio
│
├── Application/             ← orquestación, un archivo por operación
│   ├── Dto/                 AuthenticatedUser, UserView, UploadedImage,
│   │                        NewUserData, RoleView, DashboardMetrics
│   └── UseCase/
│       ├── Auth/            LoginUser, RegisterUser, LogoutUser,
│       │                    GetAuthenticatedUser
│       ├── Dashboard/       GetDashboardMetrics
│       ├── Permission/      UserCan, GetPermissionMatrix,
│       │                    SyncRolePermissions
│       ├── Password/        RequestPasswordReset, ValidateRecoveryToken,
│       │                    ResetPassword
│       ├── Role/             CreateRole, UpdateRole, DeleteRole,
│       │                    ListRolesDetailed
│       └── User/            CreateUser, ListUsers, FindUser, UpdateUser,
│                            ToggleUserStatus, ListRoles, ListUserNames,
│                            ChangeProfileImage, RemoveProfileImage
│
├── Infrastructure/          ← LOS ADAPTADORES
│   ├── Persistence/Pdo/     PdoUserRepository, PdoRoleRepository,
│   │                        PdoLoginLog
│   ├── Security/            BcryptPasswordHasher, RandomTokenGenerator,
│   │                        CsrfGuard
│   ├── Session/             NativeSession
│   ├── Mail/                PhpMailerMailer, NullMailer
│   ├── Storage/             LocalImageStorage
│   ├── Config/              Env, Config
│   └── Container.php        ← composition root
│
└── Presentation/
    ├── Http/                Router, Request, Response, Route
    ├── Controller/          AuthController, PasswordController,
    │                        DashboardController, UserApiController,
    │                        RoleApiController
    └── View/                ViewRenderer, Escaper

resources/views/
├── layouts/                 public.php, dashboard.php
├── partials/                navbar, sidebar, breadcrumb, formularios
├── components/              card, input-group, modal, auth-card,
│                            data-table, avatar, stat-card
├── pages/                   auth/*, users/*, roles/*
└── errors/                  404, 403, 419, 500, generic
```

---

## Rutas

El router usa una **tabla explícita** (`src/Presentation/Http/Router.php`). El
router anterior hacía `new $_GET['controller']` y llamaba `$_GET['action']`, lo
que dejaba instanciar cualquier clase e invocar cualquier método público desde
la URL. Ahora solo existe lo declarado, y cada ruta lleva escrito su nivel de
acceso.

| Método | Ruta                          | Permiso exigido        |
|--------|-------------------------------|------------------------|
| GET/POST | `/login` `/register` `/logout` | — (público)         |
| GET/POST | `/password/forgot` `/password/reset` | — (público)   |
| GET    | `/profile`                    | — (solo sesión)        |
| GET    | `/dashboard`                  | `panel.ver`            |
| POST   | `/api/profile/image`          | `perfil.editar`        |
| POST   | `/api/profile/image/delete`   | `perfil.editar`        |
| GET    | `/users`                      | `usuarios.ver`         |
| POST   | `/api/users/list` `/find`     | `usuarios.ver`         |
| GET    | `/api/users/names`            | `usuarios.ver`         |
| POST   | `/api/users/create`           | `usuarios.crear`       |
| POST   | `/api/users/update`           | `usuarios.editar`      |
| POST   | `/api/users/toggle`           | `usuarios.cambiar_estado` |
| GET    | `/roles`                      | `roles.ver`            |
| GET    | `/api/roles` `/api/roles/list` | `roles.ver`           |
| POST   | `/api/roles/create` `/update` `/delete` | `roles.gestionar` |
| GET    | `/roles/permisos`             | `permisos.gestionar`   |
| POST   | `/api/roles/permissions`      | `permisos.gestionar`   |

Todo POST exige token CSRF; lo aplica el router, no cada controlador.

---

## Vistas

Las vistas usan un **layout maestro**. Antes, `header.admin.php` abría
`<div class="content-wrapper">`, `container.admin.php` abría `<section>` y
`footer.admin.php` los cerraba: etiquetas partidas entre tres archivos.

Ahora una página declara su layout y su contenido se inyecta entero:

```php
<?php $view->layout('dashboard'); ?>

<?= $view->partial('components/card', [
    'title' => 'Usuarios',
    'body'  => $tabla,
]) ?>
```

Reglas:

- Todo dato que se imprime pasa por `e()` (escapado HTML).
- Las variables que transportan HTML ya construido (`$body`, `$content`) se
  imprimen crudas a propósito.
- El menú lateral se declara como array de datos y se pinta en un bucle.


**Una trampa del renderizador.** `ViewRenderer` incluye cada plantilla dentro de
una función cuyo ámbito solo contiene `$__path` y `$__vars`, y extrae los datos
con `EXTR_OVERWRITE`. No es un capricho: antes el `include` ocurría en un método
con locales llamados `$file`, `$data` y `$view`, y `extract(..., EXTR_SKIP)`
descartaba **en silencio** cualquier dato de la vista que se llamara igual. Por
eso `components/avatar.php`, que recibía un parámetro `$file`, terminaba usando
la ruta de la propia plantilla como nombre de la foto y generaba
`/assets/uploads/D:\laragon\...\avatar.php`. El verificador estático incluye
ahora una comprobación de esas colisiones.

---

## Puesta en marcha

```bash
cp .env.example .env      # y completar valores
composer install
composer dump-autoload    # importante si venís de la versión anterior
mysql -u root -p < database/schema.sql
```

Si ya tenías la base con el esquema viejo:

```bash
mysql -u root -p < database/migration-2.0.sql
mysql -u root -p < database/migration-2.1.sql   # gestión de roles
mysql -u root -p < database/migration-2.2.sql   # foto de perfil
mysql -u root -p < database/migration-2.3.sql   # registro de accesos
mysql -u root -p < database/migration-2.4.sql   # permisos por rol
```

`migration-2.1.sql` incluye al final una consulta de comprobación: si tenés
roles con nombres que no cumplen el formato nuevo, corregilos antes de seguir.

Con `SMTP_HOST` vacío la app no intenta enviar correo: escribe el mensaje al log
de PHP, lo que alcanza para probar el flujo de recuperación en local.

---

## Tests

```bash
composer install
vendor/bin/phpunit          # tests
vendor/bin/phpstan analyse  # análisis estático, nivel 9
composer check              # ambos
```

El proyecto requiere **PHP 8.1+** (`readonly` en los DTOs) y está configurado
para PHPStan nivel 9 en `phpstan.neon`.

Los tests de casos de uso corren **sin base de datos, sin SMTP y sin disco**:
los puertos se sustituyen por dobles en `tests/Double/`
(`InMemoryUserRepository`, `SpyImageStorage`, `FixedClock`,
`InMemoryRoleRepository`). Eso es lo que compra la arquitectura de puertos:
`CreateUserTest` verifica el hasheo, la unicidad, el rechazo de rol inexistente
y —lo más difícil de probar de otro modo— que una imagen subida se borre si el
INSERT falla, sin escribir un solo archivo real.

`RoleManagementTest` cubre la protección de los roles de sistema: que
`ROL_ADMIN` no se pueda renombrar ni borrar, que un rol con usuarios asignados
se rechace con el conteo en el mensaje, y que `ventas` y `VENTAS` colisionen.

---

## Alta de usuarios

Hay dos caminos distintos y no deben confundirse:

| | Auto-registro | Alta por admin |
|---|---|---|
| Ruta | `POST /register` | `POST /api/users/create` |
| Caso de uso | `RegisterUser` | `CreateUser` |
| Quién | cualquiera | solo `ROL_ADMIN` |
| Rol | siempre `ROL_USER` | lo elige el admin |
| Estado | siempre activo | activo o inactivo |
| Foto | no | opcional |

`CreateUser` valida **todo** (unicidad de usuario y correo, coincidencia y
longitud de contraseña, existencia del rol) *antes* de escribir la imagen en
disco. Si validara después, cada intento fallido dejaría un archivo basura en
`assets/uploads/` — un llenado de disco trivial de provocar. Y si el INSERT
falla igual por una carrera contra el índice único, la imagen ya subida se
borra en el `catch`.

---

## Gestión de roles

`/roles` (solo admin) permite crear, renombrar y eliminar roles.

**Roles de sistema.** `ROL_ADMIN` y `ROL_USER` están cableados en el código:
`isAdmin()` compara contra la cadena `ROL_ADMIN` y el registro público asigna
`ROL_USER`. Si se pudieran renombrar o borrar desde la interfaz, un clic dejaría
la aplicación sin administradores y rompería el alta de usuarios, recuperable
solo por consola de MySQL.

Por eso la tabla `rol` tiene una columna `es_sistema` y la protección está
aplicada en tres capas:

1. **Entidad** — `Role::rename()` y `Role::ensureDeletable()` lanzan
   `ProtectedRoleException`. La regla vive en el dominio, así que da igual desde
   qué caso de uso se intente.
2. **SQL** — el `UPDATE` y el `DELETE` llevan `AND es_sistema = 0`. Aunque
   alguien saltease la capa de dominio, la sentencia no tocaría un rol protegido.
   `es_sistema` nunca se escribe desde la aplicación: solo la pone la migración.
3. **Interfaz** — los botones aparecen deshabilitados con un candado.

**Eliminación.** Un rol con usuarios asignados no se borra. La clave foránea de
`usuario` ya lo impediría, pero eso llegaría como un error 500 del motor;
atrapado en `DeleteRole` el admin lee *"No se puede eliminar el rol X: 4
usuarios lo tienen asignado"*. El repositorio además traduce la violación de
clave foránea por si alguien asigna el rol entre la comprobación y el `DELETE`.

**Nombres.** `RoleName` normaliza a mayúsculas y exige `^[A-Z][A-Z0-9_]*$`, de 3
a 50 caracteres. Al normalizar, `ventas` y `VENTAS` no pueden coexistir como
roles distintos.

---

## Foto de perfil

En la base **solo se guarda el nombre del archivo** (`a3f1…c9.jpg`), nunca una
URL. Antes se guardaba la URL absoluta, así que mover el proyecto o cambiar
`BASE_URL` rompía todas las fotos ya subidas. La URL se arma al renderizar, en
`resources/views/components/avatar.php`, que es el único sitio que sabe dónde
viven los archivos.

`migration-2.2.sql` convierte las filas existentes quedándose con lo que hay
después de la última barra.

**Ciclo de vida del archivo.** Cambiar la foto borra la anterior y quitarla
borra la actual; si no, cada cambio dejaría un archivo muerto en
`assets/uploads/`. Al quitarla se actualiza primero la base y después el disco:
si fallara el borrado quedaría un huérfano, que es menos grave que una fila
apuntando a un archivo inexistente.

**Por qué no funcionaba antes.** El formulario usaba el `custom-file` de
Bootstrap, cuyo `<label>` solo se actualiza si está cargado el plugin
`bs-custom-file-input`, que no estaba. Elegías un archivo y no cambiaba nada en
pantalla. Ahora el `<input type="file">` va oculto y la interfaz la maneja
`public/js/profile.js`: vista previa, arrastrar y soltar, barra de progreso y
sustitución de la imagen sin recargar.

**Errores.** Cada código de `$_FILES['error']` tiene su mensaje. Y si el cuerpo
supera `post_max_size`, PHP vacía `$_POST` y `$_FILES` enteros: el router lo
detecta comparando `CONTENT_LENGTH` contra el límite y responde 413 explicando
el tamaño, en vez del "token CSRF inválido" que salía antes y mandaba a buscar
el problema al lugar equivocado.

---

## Métricas del panel

`/dashboard` muestra tres cifras: usuarios registrados, usuarios inactivos e
inicios de sesión del mes en curso.

**Registro de accesos.** La tabla `acceso` es un log append-only con una fila
por autenticación exitosa. Guarda solo `usuario_id` y `fecha`: **sin IP ni
user-agent**, porque son datos personales que la métrica no necesita. Se
registran únicamente los logins correctos; los fallidos son otra función
(detección de fuerza bruta) y no está implementada.

La escritura del log va envuelta en `try/catch` dentro de `LoginUser`. La
bitácora es una métrica, no parte del contrato de autenticación: si esa tabla
falla, el usuario ya está autenticado y no tiene por qué quedarse fuera. Hay un
test que fija ese comportamiento.

**El mes en curso** se calcula desde el `Clock` inyectado, nunca con `date()` ni
con `NOW()` en SQL. Dos motivos: es testeable sin esperar a que cambie el mes, y
no depende de la zona horaria del motor de base de datos, que puede diferir de
la de PHP. El rango es `[día 1 00:00, día 1 del mes siguiente 00:00)` — extremo
superior **exclusivo**, que evita el error clásico de perderse el último día al
usar `BETWEEN`. Hay tests para el cruce de año y para meses de distinta
longitud.

**La tabla crece sin límite.** `migration-2.3.sql` lleva comentada una sentencia
de purga por antigüedad; activarla es una decisión del negocio.

---

## Permisos

Los permisos se asignan al **rol**, no a la persona. `/roles/permisos` es la
matriz donde se marcan.

**El catálogo es un enum de PHP**, `Domain\ValueObject\Permission`, y no una
tabla. Un permiso solo significa algo si hay código que lo comprueba; si se
pudieran crear desde la interfaz, se acumularían entradas decorativas que no
protegen nada. La base solo guarda **qué rol tiene cuál**, en `rol_permiso`.

**Un único punto de decisión**: `Role::can()`. Ahí vive también el atajo de
`ROL_ADMIN`, que pasa siempre sin mirar el conjunto. Es una red de seguridad
deliberada: sin ella, desmarcar por error `permisos.gestionar` dejaría el panel
inaccesible y solo se recuperaría por consola de MySQL. Por eso la matriz
muestra ese rol bloqueado.

**Lectura fresca**: `UserCan` lee el rol de la base en cada petición, con caché
dentro del mismo request. Cachearlo en la sesión sería más barato pero
significaría que revocar un permiso a alguien conectado no surte efecto hasta
que cierre sesión — un agujero real. Hay un test que fija ese comportamiento.

**Defensa en profundidad**: el router rechaza la petición, el caso de uso
vuelve a comprobar donde la regla no es puramente de ruta (foto de otro
usuario), y el menú oculta lo que no se puede usar. Ocultar no es proteger: es
solo para no ofrecer lo que devolvería 403.

El verificador estático exige que **toda ruta declare un permiso que exista en
el enum**, y avisa de permisos declarados que ningún sitio comprueba.

---

## Decisiones de seguridad

| Riesgo | Cómo se aborda |
|---|---|
| SQL injection | Todas las consultas son preparadas con parámetros ligados. Los filtros opcionales se arman con placeholders; ningún dato del usuario se concatena al SQL. `ATTR_EMULATE_PREPARES = false`. |
| Credenciales en el código | Todo viene de `.env` vía `Config`. `.env` está en `.gitignore`. |
| XSS | `e()` en cada impresión de datos. |
| CSRF | Token por sesión comparado con `hash_equals`, exigido por el router en todo POST. |
| Session fixation | `session_regenerate_id(true)` al iniciar sesión. |
| Enumeración de usuarios | Login responde igual exista o no la cuenta, con hash señuelo para igualar el tiempo. La recuperación responde igual exista o no el correo. |
| Login por coincidencia parcial | El original buscaba con `LIKE BINARY '%valor%'`. Ahora es comparación exacta `= BINARY`. |
| Tokens predecibles | `random_bytes()`, nunca `rand()`. |
| Tokens de recuperación | Formato `selector:sha256(verificador):expira`. En base de datos solo queda el hash; vencen a los 30 min y son de un solo uso. |
| Manipulación del reset | El formulario manda el token, no un id de usuario. El servidor revalida antes de escribir. |
| Escalada de privilegios | El nivel de acceso está en la tabla de rutas. Cambiar la foto de otro exige ser admin. |
| Uploads maliciosos | Tipo detectado con `finfo` + `getimagesize`, extensión desde whitelist por MIME real, nombre generado con `random_bytes`, y `.htaccess` que desactiva la ejecución en `assets/uploads/`. La validación del navegador es solo cortesía: el servidor la repite entera. |
| Borrado fuera de sitio | `LocalImageStorage::delete()` pasa por `basename()` y confirma con `realpath()` que el destino sigue dentro de `assets/uploads` antes de desvincular nada. |
| Fuga por errores | `display_errors` off salvo en desarrollo; los detalles van al log. |

---

## Pendiente

1. **Rotar la App Password de Gmail** que quedó expuesta en el historial de git,
   y purgar el historial con `git filter-repo`.
2. HTTPS + descomentar `Strict-Transport-Security` en `.htaccess`.
3. Podar los plugins de AdminLTE sin usar en `assets/admin/plugins/` (se usan
   solo jquery, bootstrap, overlayScrollbars y fontawesome).
