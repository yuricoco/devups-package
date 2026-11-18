<?php

namespace Devups;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

/**
 * ETAPE 2 (v8.0) : bootstrap autonome du moteur de vues Illuminate (le
 * meme illuminate/view qu'utilisait `philo/laravel-blade`, mais tenu a
 * jour directement ici - philo/laravel-blade est abandonne depuis des
 * annees et epinglait illuminate/view ~5, source de l'exception
 * composer audit PKSA-h74q-2n9z-j6wq (CVE-2021-43808) retiree par ce
 * lot). Reprend exactement l'assemblage Container/Filesystem/Dispatcher
 * /EngineResolver/FileViewFinder/Factory que faisait `Philo\Blade\Blade`,
 * en remplacant l'ancienne dependance par les classes illuminate/view ^11
 * directement.
 *
 * Point d'entree UNIQUE de toute instanciation du moteur de vues dans le
 * framework (anciennement `BladeD`, voir Genesis.php/Controller.php/
 * Request.php) : `new Devups\View($viewPaths, $cachePath)->view()->make($view, $data)->render()`.
 */
class View
{
    /** @var array */
    public $viewPaths;

    /** @var string */
    public $cachePath;

    /** @var Container */
    protected $container;

    /** @var Factory */
    protected $instance;

    public function __construct($viewPaths, string $cachePath, ?Dispatcher $events = null)
    {
        // ETAPE F.3 (v7.1) : cache/ est entierement ignore par git
        // (.gitignore) - un clone neuf n'a donc aucun de ses sous-dossiers,
        // y compris cache/views. Cree systematiquement ici, au point
        // d'entree unique, jamais besoin d'une etape d'installation
        // prealable.
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        $this->container = new Container();
        $this->viewPaths = (array)$viewPaths;
        $this->cachePath = $cachePath;

        $this->registerFilesystem();
        $this->registerEvents($events ?: new Dispatcher());
        $this->registerEngineResolver();
        $this->registerViewFinder();
        $this->instance = $this->registerFactory();
    }

    public function view(): Factory
    {
        return $this->instance;
    }

    protected function registerFilesystem(): void
    {
        $this->container->singleton('files', function () {
            return new Filesystem();
        });
    }

    protected function registerEvents(Dispatcher $events): void
    {
        $this->container->singleton('events', function () use ($events) {
            return $events;
        });
    }

    protected function registerEngineResolver(): void
    {
        $me = $this;

        $this->container->singleton('view.engine.resolver', function ($app) use ($me) {
            $resolver = new EngineResolver();

            foreach (['php', 'blade'] as $engine) {
                $me->{'register' . ucfirst($engine) . 'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    public function registerPhpEngine($resolver): void
    {
        $app = $this->container;
        $resolver->register('php', function () use ($app) {
            return new PhpEngine($app['files']);
        });
    }

    public function registerBladeEngine($resolver): void
    {
        $me = $this;
        $app = $this->container;

        // ETAPE D.? (v7.1) : directive @tt(...) -> traduction via tt().
        $this->container->singleton('blade.compiler', function ($app) use ($me) {
            $bc = new BladeCompiler($app['files'], $me->cachePath);
            $bc->directive('tt', function ($ref, $default = "", $local = null) {
                if ($default) {
                    return "<?php echo tt($ref, $default); ?>";
                }

                return "<?php echo tt($ref); ?>";
            });

            return $bc;
        });

        $resolver->register('blade', function () use ($app) {
            return new CompilerEngine($app['blade.compiler'], $app['files']);
        });
    }

    protected function registerViewFinder(): void
    {
        $me = $this;
        $this->container->singleton('view.finder', function ($app) use ($me) {
            return new FileViewFinder($app['files'], $me->viewPaths);
        });
    }

    protected function registerFactory(): Factory
    {
        $resolver = $this->container['view.engine.resolver'];
        $finder = $this->container['view.finder'];

        $env = new Factory($resolver, $finder, $this->container['events']);
        $env->setContainer($this->container);

        return $env;
    }

    public function getCompiler(): BladeCompiler
    {
        return $this->container['blade.compiler'];
    }
}
