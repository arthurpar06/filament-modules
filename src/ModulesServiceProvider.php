<?php

namespace Coolsam\FilamentModules;

use Coolsam\FilamentModules\Commands\ModuleMakePanelCommand;
use Coolsam\FilamentModules\Extensions\LaravelModulesServiceProvider;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\HtmlString;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModulesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('modules')
            ->hasConfigFile('modules')
            ->hasViews()
            ->hasCommands([
                ModuleMakePanelCommand::class,
            ]);
    }

    public function register()
    {
        $this->app->register(LaravelModulesServiceProvider::class);
        $this->app->singleton('coolsam-modules', Modules::class);
        $this->app->afterResolving('filament', function () {
            foreach (Filament::getPanels() as $panel) {
                $id = \Str::of($panel->getId());
                if ($id->contains('::')) {
                    $title = $id->replace(['::', '-'], [' ', ' '])->title()->toString();
                    $title = str_replace('Admin', '', $title);

                    $panel->renderHook(
                        'panels::sidebar.nav.start',
                        fn () => new HtmlString("<h2 class='m-2 p-2 font-black text-xl'>$title Module</h2>"),
                    );

                    $panel->navigationItems([
                        NavigationItem::make()->label('Main Panel')->icon('heroicon-o-arrow-uturn-left')->url(url(Filament::getDefaultPanel()->getPath()))->sort(99),
                    ]);
                }
            }
        });

        $this->app->afterResolving('auth', function () {
            $items = [];
            foreach (Filament::getPanels() as $panel) {
                $id = \Str::of($panel->getId());
                if ($id->contains('::')) {
                    $title = $id->replace(['::', '-'], [' ', ' '])->title()->toString();
                    if (str_contains($title, 'Admin')) {
                        $title = str_replace('Admin', '', $title);
                        if (\Auth::user()?->can('view_module')) {
                            $items[] = NavigationItem::make($title)
                                ->icon('heroicon-o-puzzle-piece')
                                ->url(url($panel->getPath()))
                                ->group('Modules');
                        }
                    }
                }
            }

            Filament::getDefaultPanel()->navigationItems($items);
        });

        return parent::register();
    }
}
