<?php

declare(strict_types=1);

namespace Tests\Application\UseCase\Dashboard;

use App\Application\Dto\NewUserData;
use App\Application\UseCase\Dashboard\GetDashboardMetrics;
use App\Application\UseCase\User\CreateUser;
use App\Application\UseCase\User\ToggleUserStatus;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Security\BcryptPasswordHasher;
use PHPUnit\Framework\TestCase;
use Tests\Double\FixedClock;
use Tests\Double\InMemoryLoginLog;
use Tests\Double\InMemoryRoleRepository;
use Tests\Double\InMemoryUserRepository;
use Tests\Double\SpyImageStorage;

final class GetDashboardMetricsTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryLoginLog $logins;
    private FixedClock $clock;
    private GetDashboardMetrics $useCase;

    protected function setUp(): void
    {
        $this->users  = new InMemoryUserRepository();
        $this->logins = new InMemoryLoginLog();
        // 15 de marzo de 2026: mes con 31 días, precedido por febrero de 28.
        $this->clock  = new FixedClock('2026-03-15 10:30:00');

        $this->useCase = new GetDashboardMetrics($this->users, $this->logins, $this->clock);
    }

    private function crearUsuario(string $username): int
    {
        $useCase = new CreateUser(
            $this->users,
            new InMemoryRoleRepository(),
            new BcryptPasswordHasher(),
            new SpyImageStorage(),
            $this->clock
        );

        $data = $useCase->execute(new NewUserData(
            $username,
            $username . '@example.com',
            'contrasena-larga',
            'contrasena-larga',
            '2'
        ));

        return $data['id_usuario'];
    }

    private function desactivar(int $id): void
    {
        (new ToggleUserStatus($this->users))->execute((string) $id);
    }

    private function registrarAcceso(int $userId, string $cuando): void
    {
        $this->logins->record(UserId::fromInt($userId), new \DateTimeImmutable($cuando));
    }

    // ----------------------------------------------------------- conteos

    public function testSinUsuariosDevuelveCeros(): void
    {
        $m = $this->useCase->execute();

        self::assertSame(0, $m->totalUsers);
        self::assertSame(0, $m->inactiveUsers);
        self::assertSame(0, $m->loginsThisMonth);
        self::assertSame(0, $m->activeUsers());
    }

    public function testCuentaTotalesEInactivos(): void
    {
        $a = $this->crearUsuario('ana');
        $this->crearUsuario('beto');
        $c = $this->crearUsuario('caro');

        $this->desactivar($a);
        $this->desactivar($c);

        $m = $this->useCase->execute();

        self::assertSame(3, $m->totalUsers);
        self::assertSame(2, $m->inactiveUsers);
        self::assertSame(1, $m->activeUsers());
    }

    public function testElPorcentajeDeInactivosNoDivideEntreCero(): void
    {
        self::assertSame(0.0, $this->useCase->execute()->inactiveRatio());
    }

    public function testCalculaElPorcentajeDeInactivos(): void
    {
        $a = $this->crearUsuario('ana');
        $this->crearUsuario('beto');
        $this->crearUsuario('caro');
        $this->crearUsuario('dani');

        $this->desactivar($a);

        self::assertSame(25.0, $this->useCase->execute()->inactiveRatio());
    }

    // ------------------------------------------------- rango del mes en curso

    public function testCuentaSoloLosAccesosDelMesEnCurso(): void
    {
        $id = $this->crearUsuario('ana');

        $this->registrarAcceso($id, '2026-03-01 00:00:00'); // primer instante
        $this->registrarAcceso($id, '2026-03-15 12:00:00'); // en medio
        $this->registrarAcceso($id, '2026-03-31 23:59:59'); // último instante

        $this->registrarAcceso($id, '2026-02-28 23:59:59'); // mes anterior
        $this->registrarAcceso($id, '2026-04-01 00:00:00'); // mes siguiente

        self::assertSame(3, $this->useCase->execute()->loginsThisMonth);
    }

    /**
     * El extremo superior es exclusivo: el primer instante del mes siguiente
     * NO cuenta. Es el caso que se rompe si alguien usa BETWEEN con el último
     * día a las 00:00.
     */
    public function testElPrimerInstanteDelMesSiguienteQuedaFuera(): void
    {
        $id = $this->crearUsuario('ana');
        $this->registrarAcceso($id, '2026-04-01 00:00:00');

        self::assertSame(0, $this->useCase->execute()->loginsThisMonth);
    }

    public function testElPrimerInstanteDelMesActualSiCuenta(): void
    {
        $id = $this->crearUsuario('ana');
        $this->registrarAcceso($id, '2026-03-01 00:00:00');

        self::assertSame(1, $this->useCase->execute()->loginsThisMonth);
    }

    /**
     * Diciembre + 1 mes debe dar enero del año siguiente, no diciembre otra vez.
     */
    public function testElRangoCruzaBienElCambioDeAno(): void
    {
        $clock = new FixedClock('2026-12-20 08:00:00');
        $useCase = new GetDashboardMetrics($this->users, $this->logins, $clock);

        $id = $this->crearUsuario('ana');
        $this->registrarAcceso($id, '2026-12-31 23:00:00');
        $this->registrarAcceso($id, '2027-01-01 00:30:00');

        self::assertSame(1, $useCase->execute()->loginsThisMonth);
    }

    /**
     * Enero tiene 31 días y febrero 28: si el rango se calculara como
     * "hoy + 1 mes", el 31 de enero desbordaría a marzo. Se calcula desde el
     * día 1, así que no puede pasar.
     */
    public function testElRangoNoDesbordaEnMesesDeDistintaLongitud(): void
    {
        $clock = new FixedClock('2026-01-31 23:00:00');
        $useCase = new GetDashboardMetrics($this->users, $this->logins, $clock);

        $id = $this->crearUsuario('ana');
        $this->registrarAcceso($id, '2026-01-31 23:30:00');
        $this->registrarAcceso($id, '2026-02-05 10:00:00'); // febrero: fuera

        self::assertSame(1, $useCase->execute()->loginsThisMonth);
    }

    // ------------------------------------------------------------- etiqueta

    public function testLaEtiquetaDelMesEstaEnEspanol(): void
    {
        self::assertSame('marzo 2026', $this->useCase->execute()->monthLabel());
    }
}
