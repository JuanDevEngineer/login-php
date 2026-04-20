# Reporte de Escaneo del Proyecto `login-php`

**Fecha:** 17 de abril de 2026
**Proyecto:** Sistema de Login en PHP (login / registro / recuperación de contraseña)
**Autor del escaneo:** Asistente de Cowork

---

## 1. Resumen ejecutivo

El proyecto es una aplicación MVC en PHP 7+ que implementa autenticación con roles (ADMIN / USER), registro de usuarios, recuperación de contraseña por correo, y un panel con AdminLTE. Usa PDO (MySQL), PHPMailer y jQuery.

La arquitectura muestra un intento de migración desde un patrón "clásico" (`controllers/`, `model/`, `views/`) hacia uno inspirado en arquitectura limpia / DDD (`presentation/`, `application/`, `domain/`, `infrastrucure/`), pero **ambas coexisten duplicadas y la nueva todavía no está cableada**: el bootstrap (`index.php`) sigue usando exclusivamente los archivos antiguos.

El proyecto es **funcional para aprendizaje**, pero **NO está listo para producción**. Hay vulnerabilidades críticas (SQL injection, credenciales en código, `extract($_REQUEST)`, sin CSRF, passwords guardados en localStorage en claro) que deben corregirse antes de cualquier despliegue público.

### Semáforo de hallazgos

| Nivel | Cantidad | Ejemplos |
|---|---|---|
| Crítico | 5 | Credenciales Gmail hardcoded, SQLi en `UserGestor`, `extract($_REQUEST)`, XSS en sesión, SQLi en `User::recover`/`registrar` |
| Alto | 7 | Sin CSRF, password en localStorage, sin control de rol, key de recovery sin expiración, `session_destroy()` en cada request, `display_errors=TRUE` global, `paginaActual()` no funcional |
| Medio | 9 | Código duplicado (views/controllers/model), typos en carpetas, autoload roto, inconsistencias, `include_once` con `$_GET`, falta validación server-side, notación inconsistente |
| Bajo | 8+ | Convenciones (mezcla ES/EN), comentarios grandes, archivos vacíos, `.htaccess` poco estricto, CDN sin SRI en algunos, SQL dump con sintaxis inválida |

---

## 2. Estructura y arquitectura

### Árbol (resumen, omitiendo `assets/`, `.git/`, `libs/PHPMailer-master/`)

```
.
├── index.php                  ← Bootstrap
├── autoload.php               ← spl_autoload_register (solo carga controllers)
├── .htaccess                  ← Rewrite rule → index.php?controller=...&action=...
├── .gitignore                 ← php-error.log, /assets/uploads
├── mydb.sql                   ← Dump inicial (BD `test`)
├── config/
│   ├── Constants.php          ← BASE_URL, EMAIL_ADMIN
│   └── DataBase.php           ← Clase PDO
├── controllers/               ← LEGADO (en uso)
│   ├── Controller.php         ← Router
│   ├── AppController.php      ← login/registro/recover/logout
│   ├── AdminController.php
│   ├── UserController.php
│   ├── GestorController.php   ← CRUD usuarios
│   ├── MailController.php     ← PHPMailer con password hardcoded
│   ├── StoreController.php    ← vacío
│   └── StoreViewController.php
├── model/                     ← LEGADO (en uso)
│   ├── Model.php              ← con hashPassword, validate
│   ├── User.php               ← login, registrar, recover, UpdatePass
│   └── UserGestor.php         ← CRUD (con SQLi)
├── helpers/
│   ├── SesionController.php   ← isUser, redirect, validateFormat/Size
│   └── helpers.php            ← paginaActual() vacía
├── libs/
│   ├── view.php               ← Duplicado exacto de ViewService
│   └── PHPMailer-master/
├── views/                     ← LEGADO (en uso)
│   ├── layout/ (header, footer)
│   ├── app/ (login, register, recover, cambiopassoword[sic])
│   ├── dashboard/ (AdminLTE)
│   ├── usuarios/
│   ├── productos/
│   └── store/
├── public/
│   ├── css/ (main, dashboard, usuarios)
│   └── js/ (app.js, usuarios.js)
├── assets/                    ← AdminLTE + plugins + toastr
│
├── application/               ← NUEVO (NO cableado)
│   ├── dto/UserSesionDto.php  (vacío)
│   ├── services/
│   │   ├── SessionService.php (vacío)
│   │   ├── ViewService.php
│   │   └── users/UserService.php
├── domain/                    ← NUEVO (NO cableado)
│   ├── entities/ (User, Product[vacío])
│   ├── intefaces/ [sic]  (AuthInterfaceRepository, UserInterfaceRepository)
│   └── valueobjects/Email.php
├── infrastrucure/             ← NUEVO [typo] (NO cableado)
│   ├── config/constants.php   (duplicado)
│   ├── persistence/Database.php (versión estática)
│   └── repositories/UserRepository.php (referencia archivo que no existe)
└── presentation/              ← NUEVO (NO cableado)
    ├── controllers/ (Auth[vacío], Base[vacío], User)
    └── views/                 ← Duplicado EXACTO de /views
```

### Flujo actual

1. Apache/PHP recibe la request.
2. `.htaccess` convierte `/App/acceso` → `index.php?controller=App&action=acceso`.
3. `index.php` requiere config, helpers, libs, PHPMailer y crea `new Controller()`.
4. `Controller::App()` instancia `<Controller>Controller` y ejecuta `$action`.
5. El controlador usa un `View` para hacer `require_once 'views/<ruta>.php'`.

### Problema arquitectónico principal

La carpeta `presentation/`, `application/`, `domain/`, `infrastrucure/` (con typo) existe pero **nunca se usa**: `index.php` no hace `require` de ningún archivo de ese árbol, el autoloader sólo mira `controllers/`, y archivos como `UserRepository.php` importan un `UserRepositoryInterface.php` que no existe (el real se llama `UserInterfaceRepository.php` y vive en `domain/intefaces/`, con typo). Es código "muerto" que confunde el mapa del proyecto.

---

## 3. Auditoría de seguridad

### 3.1 Críticas

#### C‑1 · Credenciales SMTP hardcoded en el repositorio

**Archivo:** `controllers/MailController.php` (líneas 22‑23).

```php
$mail->Username = "restrepojuanjose8@gmail.com";
$mail->Password = "qdvl acpj lzhp slug";
```

Es un *App Password* de Gmail en texto plano, versionado en git. Cualquiera con acceso al repositorio puede enviar correos como ese buzón e iniciar sesión con OAuth en servicios ligados a Google.

**Mitigación inmediata:**
1. Revocar el App Password en https://myaccount.google.com/apppasswords.
2. Mover las credenciales a variables de entorno (`getenv('SMTP_USER')`) o a un `.env` (usando `vlucas/phpdotenv`).
3. Ejecutar `git filter-repo` (o BFG) para purgar el secreto del historial y forzar push.
4. Añadir `.env`, `config/*.local.php` y similares al `.gitignore`.

#### C‑2 · SQL Injection en `UserGestor`

**Archivo:** `model/UserGestor.php`.

- `getUser($id)` — línea 50: `WHERE u.id_usuario = $id` (concatenación directa del POST).
- `getUsers($id, $estado)` — líneas 73 y 77: `WHERE u.id_usuario = '$id'` y `AND u.estado = $estado`.

`$_POST['id']` / `$_POST['estado']` llegan directos a la query sin prepared statement ni cast.

**Fix:** usar `prepare` + `bindParam(..., PDO::PARAM_INT)` en todos los casos, como ya hace `updateUser`.

#### C‑3 · SQL Injection en `User::registrar()` y `User::recover()`

**Archivo:** `model/User.php`.

- Línea 199 (`registrar`): interpolación del username/email dentro del `INSERT`.
- Línea 227 (`recover`): concatena `$key` y `$usuario['data']['id_usuario']` en un `UPDATE` y lo ejecuta con `->query($sql)`.

El hecho de que `$key` sea generado internamente reduce el riesgo en `recover`, pero el `registrar` **acepta el username/email del POST sin ningún escape**. Un atacante puede registrar un usuario con `', 2, CURDATE()); DROP TABLE usuario; --` como username.

**Fix:** reescribir ambos con `prepare` + `bindParam` (ver `UpdatePass` como referencia correcta).

#### C‑4 · `extract($_REQUEST)` en `uploadImageProfile`

**Archivo:** `controllers/GestorController.php` línea 166.

```php
extract($_REQUEST);
$id_user = $id_usuario;
```

`extract()` sobre input del usuario crea variables arbitrarias en el scope actual, permitiendo sobrescribir variables previas del controlador (p. ej. `$path`, `$nombre_imagen`, variables de la clase base). Es una de las vulnerabilidades más conocidas en PHP.

**Fix:** `$id_user = (int) ($_POST['id_usuario'] ?? 0);` y eliminar `extract`.

#### C‑5 · XSS en vistas (session echo sin escapar)

**Archivos:** `views/usuarios/perfil.php` (líneas 24‑26, 69, 76), `views/dashboard/sidebar.admin.php` (línea 15, 21), `views/dashboard/header.admin.php` (línea 158).

```php
<img src="<?php echo $_SESSION['image']?>" ...>
<a ... id="usuario"><?php echo $_SESSION['email']; ?></a>
```

El `image_url` se construye a partir del nombre de archivo subido, pero el `email` y `usuario` provienen directo de la BD (que a su vez se registra sin ninguna validación de formato). Un usuario registrado con email `"><script>alert(1)</script>` ejecuta XSS en cualquier vista del dashboard.

**Fix:** envolver todo con `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')` (o crear un helper `e($v)`).

---

### 3.2 Altas

#### A‑1 · No hay tokens CSRF en ningún formulario

`signIn`, `signUp`, `UpdatePass`, `updateUser`, `updataState`, `uploadImageProfile` sólo comprueban `$_SERVER['REQUEST_METHOD'] === 'POST'`. Cualquier sitio externo puede enviar un POST con cookies del usuario.

**Fix:** generar un `csrf_token` en sesión, emitirlo como `<input type="hidden">` y validarlo en cada controlador POST.

#### A‑2 · Credenciales almacenadas en `localStorage` en texto plano

**Archivo:** `public/js/app.js` líneas 155‑170.

```js
storage.setItem("datos", JSON.stringify({nombre, password, check}))
```

Cualquier XSS en el sitio (ver C‑5) permite robar usuario + contraseña de todos los usuarios que marcaron "Recordarme". Además, se muestra `toastr.success("datos guardados!")` revelando la funcionalidad.

**Fix:** nunca guardar contraseñas en el cliente. Usar cookies "remember‑me" firmadas del lado servidor, con token rotatorio.

#### A‑3 · Sin control de autorización por rol

`AdminController::gestor()`, `creacionproductos()`, `productos()`, `perfil()` sólo comprueban `isUser()` (sesión activa). Un usuario con `rol = ROL_USER` puede acceder al gestor de usuarios, bloquear/desbloquear y editar roles libremente.

**Fix:** añadir `protected function isAdmin()` al `SesionController` y bloquear cualquier ruta admin.

#### A‑4 · Key de recuperación sin expiración ni uso único

**Archivo:** `model/User.php::recover()` / `validateKey()`.

La columna `recover` se llena con una cadena de 50 caracteres pero **no tiene `expires_at`** ni se borra tras usarse. Cualquiera que obtenga la key (logs, referer, bandeja spam, admin de BD) puede resetear la contraseña en cualquier momento. Además, `UpdatePass()` no invalida la key después de un cambio exitoso.

**Fix:** añadir `recover_expires_at DATETIME`, invalidar tras uso, regenerar al fallar.

#### A‑5 · `session_destroy()` incondicional en `AppController`

**Archivo:** `controllers/AppController.php` líneas 4‑8.

```php
session_destroy();
if (!session_start()) { session_start(); }
```

Está fuera de cualquier método, se ejecuta **cada vez** que se instancia el controlador (es decir, cada request que entra por `App/...`). Eso borra la sesión incluso al navegar a `App/acceso` tras logearse. También el patrón `if (!session_start()) session_start()` es incorrecto: `session_start()` devuelve `true` si tiene éxito y no debe llamarse dos veces.

**Fix:** eliminar la destrucción global; usar `session_start()` sólo si `session_status() === PHP_SESSION_NONE`.

#### A‑6 · `display_errors = TRUE` y `error_reporting(E_ALL)` global

**Archivo:** `index.php` líneas 3‑9.

Un error PDO filtrará la ruta absoluta, query y credenciales. Aceptable en local, peligroso en cualquier entorno público.

**Fix:** basar en `getenv('APP_ENV')` — `display_errors = 0` en producción, `log_errors = 1` siempre.

#### A‑7 · `paginaActual()` devuelve `"JFK"` hardcoded

**Archivo:** `helpers/helpers.php`.

```php
function paginaActual() { return "JFK"; }
```

No es una vulnerabilidad, pero se usa como `<title>` en todas las páginas: síntoma de código abandonado (posible vector si alguien lo "arregla" concatenando `$_SERVER['REQUEST_URI']` sin escape).

---

### 3.3 Medias

- **M‑1** · `cambioPass()` hace `include_once 'views/app/cambiopassoword.php'` tras validar `$_GET['key']`, pero la vista se sirve via `include` en lugar de `$this->view->render()` — inconsistente con el resto del controlador.
- **M‑2** · `validateUser()` usa `LIKE BINARY '%x%'`: un usuario `"ab"` colisionaría con `"cab"` o `"abc"`, impidiendo registrar nombres que sean substring de otro.
- **M‑3** · Registro: no valida formato de email, longitud mínima de contraseña, caracteres, unicidad de email (solo de username). El email ni siquiera pasa `filter_var(..., FILTER_VALIDATE_EMAIL)`.
- **M‑4** · Subida de archivos: se guarda en `assets/uploads/` dentro del *docroot* y **sin** `.htaccess` que deshabilite ejecución PHP. Si un atacante consigue subir un archivo con doble extensión (`foo.jpg.php`) o aprovecha un bug en `validateFormatImage` (que sólo valida el `type` reportado por el navegador, falsificable), logra RCE. El `validateSize` usa 10 MB hardcoded.
- **M‑5** · Enlace de recuperación en `AppController::enviarCambioPassword`: `http://localhost/GITHUB-PRIVATE/login-php/App/cambioPass&key=$key` — ruta hardcoded (ignora `BASE_URL`) y query string mal formada (`?App...&key=` vs `App/cambioPass&key=`).
- **M‑6** · `autoload.php` sólo escanea `controllers/`. Si se instancia `UserService`, `UserRepository`, etc., `spl_autoload_register` imprime `"error al cargar la clase"` y continúa — nunca lanza excepción.
- **M‑7** · `Controller::App` imprime `"error al abrir el metodo"` / `"error al intentar abrir el controlador"` directamente — debería devolver 404 + plantilla.
- **M‑8** · `Model::generateRandomString` usa `rand()` (no cripto‑seguro) y `rand(0, $len-3)` descarta los últimos 2 caracteres del alfabeto (Y, Z). Usar `random_bytes(32)` + `bin2hex`.
- **M‑9** · `SesionController` llama `session_start()` en el top-level, genera "headers already sent" si cualquier archivo tiene BOM o espacios antes de `<?php`.

### 3.4 Bajas / cosméticas

- Typos estructurales: `infrastrucure/` (debería `infrastructure`), `intefaces/` (debería `interfaces`), `cambiopassoword.php` (debería `cambiopassword.php`).
- Inconsistencia de nombres de capas duplicadas (`libs/view.php` == `application/services/ViewService.php` → contenido idéntico).
- `views/` y `presentation/views/` son copias exactas.
- `controllers/StoreController.php` está vacío excepto por la clase.
- Mezcla español/inglés: `signIn`/`signUp` / `acceso`, `GestorController`, `UserGestor`.
- Archivos `.js` hardcodean `const URL = "/GITHUB-PRIVATE/login-php"` (duplicado en 2 archivos); debería inyectarse desde el layout.
- `mydb.sql` tiene sintaxis inválida: `use DATABASE \`test\`` (correcto: `USE \`test\`;`) y `MODIFY \`id_rol\` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;` — la coma genera error en MySQL 8.
- Dump de `mydb.sql` contiene hashes bcrypt reales con correos personales — aunque estén comentados, son PII y material para ataques rainbow/offline.
- El `.htaccess` no fuerza HTTPS ni añade headers de seguridad (`X-Frame-Options`, `Content-Security-Policy`, `X-Content-Type-Options`).
- Múltiples CDN en `header.php` / `footer.php` sin `integrity` (SRI) — MITM puede inyectar JS.

---

## 4. Calidad de código

### 4.1 Patrones y duplicación

- **Duplicación completa `views/` ↔ `presentation/views/`** (todo el árbol). Mantener dos copias es una bomba de tiempo — cualquier fix sólo se aplicará en una.
- **Duplicación `libs/view.php` ↔ `application/services/ViewService.php`** (idénticos).
- **Duplicación `config/DataBase.php` ↔ `infrastrucure/persistence/Database.php`** (el nuevo es mejor: singleton estático).
- **Duplicación `config/Constants.php` ↔ `infrastrucure/config/constants.php`** (idénticos).
- **Dos interfaces con el mismo nombre** (`UserInterfaceRepository` en `domain/intefaces/AuthInterfaceRepository.php` y en `UserInterfaceRepository.php`). El primero define `signin/signup`, el segundo `save/getById/getAll/update/delete`. Declarar dos interfaces con el mismo nombre provoca un fatal error si ambas se cargan en la misma request.

### 4.2 Bugs funcionales

- `AppController::signIn` — comprueba `$response_login['status']` después de un posible `echo + exit`, pero escribe en sesión sin validar que `response_login['data']` existe cuando `status === false`.
- `UserGestor::getUser` — devuelve `array()` si rowCount == 0, pero el controlador ya intentó parsearlo en JS (no rompe, simplemente no rellena el modal).
- `UserController::dashboard` renderiza `'usuarios/editar'` que **no existe** en ninguna de las dos carpetas de views.
- `getId($key)` → `include_once 'views/app/cambiopassoword.php'` espera la variable `$user_id`, pero `cambioPass()` hace `$user_id = $user->getId($key)` y `getId` puede devolver un array `['error' => ...]`. Eso genera "Undefined index 'id_usuario'" sin mensaje amigable.
- `MailController::sendEmail` no siempre devuelve algo (si `$mail->send()` falla sin excepción, retorna `null` silenciosamente).
- `AppController::logout` llama `SesionController::redirect` como estático, pero el método es `protected` e instancia `header('Location: …')` sin `exit;` después.
- `Model::__construct` instancia `new Database()` cada vez — cada `new User()` abre una conexión PDO nueva. El singleton de `infrastrucure/persistence/Database.php` es la forma correcta.

### 4.3 Convenciones

- Mezcla `snake_case`/`camelCase` tanto en PHP (`id_usuario`, `UpdatePass`, `signIn`) como en BD (`recover`, `id_usuario`, `rol_id`).
- Clases con nombres inconsistentes: `SesionController` (falta `s`), `UserGestor` (híbrido).
- Comentarios muertos enormes en `sidebar.admin.php` (>300 líneas comentadas copiadas del template AdminLTE).
- Llamadas `error_log("ejecucion->" . $stmt->execute());` **ejecutan la query dos veces** (una para loguear, otra para el `if`), lo cual es funcional pero peligroso en INSERT/UPDATE.

### 4.4 Pruebas y CI

- No hay tests (`tests/`, `phpunit.xml`, ...).
- No hay `composer.json` (aunque se usa PHPMailer: debería cargarse con Composer y no con `require_once` manual).
- No hay pipeline de CI.

---

## 5. Base de datos

Esquema (tras arreglar la sintaxis del dump):

```
rol(id_rol PK, nombre)
usuario(id_usuario PK, username UNIQUE, email, password,
        rol_id FK→rol.id_rol, registro DATE, recover VARCHAR(200),
        estado TINYINT, imagen_url VARCHAR(…))
```

Notas:

- `estado` e `imagen_url` se usan en el código pero **no aparecen** en `mydb.sql`. Ejecutar el dump hoy deja la BD en un estado incompatible con el código.
- `email` no tiene `UNIQUE` (permitiría duplicados y colisiones en `recover`).
- `recover` debería separarse en tabla aparte o al menos tener expires_at.
- `password` es `VARCHAR(200)`, suficiente para bcrypt (60 chars) pero inconsistente; bcrypt2y necesita exactamente 60.

---

## 6. Plan de remediación sugerido (en orden)

1. **Inmediato (hoy):**
   - Revocar el App Password de Gmail y rotar las credenciales fuera del repo.
   - Purgar el historial de git (BFG / filter-repo).
   - Aplicar `htmlspecialchars` en toda impresión de `$_SESSION['*']`.
   - Parametrizar todas las queries de `UserGestor` y `User::registrar/recover`.
   - Eliminar `extract($_REQUEST)`.
2. **Corto plazo (1‑2 días):**
   - Implementar CSRF (token en sesión + hidden field + middleware).
   - Quitar `session_destroy()` global y arreglar `display_errors` según entorno.
   - Validar email/password server-side (longitud mínima, `FILTER_VALIDATE_EMAIL`).
   - Añadir autorización por rol (`isAdmin`) en `AdminController` y `GestorController`.
   - Añadir `.htaccess` en `assets/uploads` con `php_flag engine off` y `<FilesMatch "\.ph(p|tml)$">Deny from all</FilesMatch>`.
   - Cambiar `rand()` por `random_bytes` para la recovery key y añadir expiración + uso único.
3. **Medio plazo (1‑2 semanas):**
   - Decidir qué arquitectura queda: legado o DDD. Borrar la otra. No mantener dos.
   - Adoptar Composer + PSR‑4 + PHPMailer como dependencia (no copiado).
   - Renombrar carpetas/archivos con typos (`infrastrucure`, `intefaces`, `cambiopassoword`).
   - Extraer configuración a `.env` (credenciales DB, SMTP, BASE_URL).
   - Añadir PHPUnit y tests sobre `UserService` / login / recover.
   - Consolidar `const URL` en JS en una sola constante emitida por el layout (`<script>window.APP = { baseUrl: "<?= BASE_URL ?>" }</script>`).
4. **Largo plazo:**
   - Migrar a un microframework (Slim, Laravel, Symfony) para obtener routing, DI, middleware CSRF/CORS, migraciones, logs centralizados.
   - Implementar HTTPS + HSTS + CSP desde reverse proxy.

---

## 7. Lista de archivos revisados

Revisados manualmente en esta auditoría (bajo `/`): `README.md`, `index.php`, `autoload.php`, `.htaccess`, `.gitignore`, `mydb.sql`, `config/Constants.php`, `config/DataBase.php`, `helpers/helpers.php`, `helpers/SesionController.php`, `libs/view.php`, `controllers/Controller.php`, `controllers/AppController.php`, `controllers/UserController.php`, `controllers/AdminController.php`, `controllers/GestorController.php`, `controllers/MailController.php`, `controllers/StoreController.php`, `controllers/StoreViewController.php`, `model/Model.php`, `model/User.php`, `model/UserGestor.php`, `domain/entities/User.php`, `domain/entities/Product.php` (vacío), `domain/intefaces/AuthInterfaceRepository.php`, `domain/intefaces/UserInterfaceRepository.php`, `domain/valueobjects/Email.php`, `application/dto/UserSesionDto.php` (vacío), `application/services/SessionService.php` (vacío), `application/services/ViewService.php`, `application/services/users/UserService.php`, `infrastrucure/config/constants.php`, `infrastrucure/persistence/Database.php`, `infrastrucure/repositories/UserRepository.php`, `presentation/controllers/AuthController.php` (vacío), `presentation/controllers/BaseController.php`, `presentation/controllers/UserController.php`, `public/js/app.js`, `public/js/usuarios.js`, y las vistas clave `views/app/login.php`, `register.php`, `recover.php`, `cambiopassoword.php`, `views/layout/header.php`, `footer.php`, `views/usuarios/perfil.php`, `gestor.php`, `index.php`, `views/productos/gestor.php`, `views/dashboard/header.admin.php`, `sidebar.admin.php`.

No revisados en profundidad (librerías de terceros / recursos): `libs/PHPMailer-master/**`, `assets/admin/**`, `assets/toastr/**`.

---

*Fin del reporte.*
