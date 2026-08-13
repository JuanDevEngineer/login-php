<?php

declare(strict_types=1);

namespace App\Presentation\View;

/**
 * Renderizador de plantillas PHP con soporte de layouts.
 *
 * Una página declara su layout con $this->layout('dashboard') y su contenido
 * queda disponible dentro del layout como $content. Así ninguna vista tiene que
 * abrir un <div> en un include y cerrarlo en otro, que era el problema del
 * header/container/footer originales.
 */
final class ViewRenderer
{
    private string $viewPath;
    /** @var array<string, mixed> variables compartidas por todas las vistas */
    private array $shared = [];

    private ?string $layout = null;
    /** @var array<string, string> */
    private array $sections = [];

    public function __construct(string $viewPath)
    {
        $this->viewPath = rtrim($viewPath, '/');
    }

    /** @param mixed $value */
    public function share(string $key, $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $this->layout   = null;
        $this->sections = [];

        $content = $this->renderFile($this->resolve($view), $data);

        if ($this->layout === null) {
            return $content;
        }

        $layout       = $this->layout;
        $this->layout = null;

        return $this->renderFile(
            $this->resolve('layouts/' . $layout),
            array_merge($data, ['content' => $content, 'sections' => $this->sections])
        );
    }

    /** Renderiza un fragmento reutilizable (partial o componente). */
    public function partial(string $view, array $data = []): string
    {
        return $this->renderFile($this->resolve($view), $data);
    }

    /** Llamado desde dentro de una vista para elegir su layout. */
    public function layout(string $name): void
    {
        $this->layout = $name;
    }

    /** Guarda un bloque nombrado (por ejemplo scripts extra) para el layout. */
    public function section(string $name, string $html): void
    {
        $this->sections[$name] = ($this->sections[$name] ?? '') . $html;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $file, array $data): string
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Vista no encontrada: ' . $file);
        }

        // $view queda disponible dentro de la plantilla para llamar a
        // $view->partial(), $view->layout(), etc.
        $view = $this;

        extract(array_merge($this->shared, $data), EXTR_SKIP);

        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    private function resolve(string $view): string
    {
        $relative = str_replace(['..', '\\'], '', $view);

        return $this->viewPath . '/' . ltrim($relative, '/') . '.php';
    }
}
