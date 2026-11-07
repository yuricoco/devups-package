
@php
    $dvups_navigation = $admin->_dvups_role->getConfigs();
    // mm-active etait fige sur le lien
    // "Dashboards" global, quelle que soit la page courante. Mesure avant
    // d'ecrire (grep du bundle main.js, classe metisMenu reelle, pas
    // devinee) : au chargement, metisMenu CHERCHE les elements qui
    // portent deja mm-active (.find(parentTrigger+"."+ACTIVE)) pour
    // deplier leurs ancetres - elle ne l'efface jamais, elle s'en sert.
    // Poser mm-active cote serveur sur le bon element est donc sans
    // danger : metisMenu respecte ce qui est deja la au chargement, elle
    // n'ajoute mm-collapse (replie) qu'aux elements qui n'ont PAS
    // mm-active. $dv_is_current reutilise exactement les memes
    // fragments d'URL que les href generes plus bas, pour rester
    // coherent avec eux.
    $dv_current_path = strtolower(trim(parse_url(\Router::$uri ?? ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/'));
    $dv_is_current = function ($path) use ($dv_current_path) {
        $path = strtolower(trim($path, '/'));
        return $path !== '' && ($dv_current_path === $path || strpos($dv_current_path, $path . '/') === 0);
    };
@endphp
<div class="app-sidebar sidebar-shadow">
    <div class="app-header__logo">
        <div class="logo-src"></div>
        <div class="header__pane ml-auto">
            <div>
                <button type="button" class="hamburger close-sidebar-btn hamburger--elastic"
                        data-class="closed-sidebar">
                                    <span class="hamburger-box">
                                        <span class="hamburger-inner"></span>
                                    </span>
                </button>
            </div>
        </div>
    </div>
    <div class="app-header__mobile-menu">
        <div>
            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                                <span class="hamburger-box">
                                    <span class="hamburger-inner"></span>
                                </span>
            </button>
        </div>
    </div>
    <div class="app-header__menu">
        <span>
            <button type="button"
                    class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                <span class="btn-icon-wrapper">
                    <i class="fa fa-ellipsis-v"></i>
                </span>
            </button>
        </span>
    </div>
    <div class="scrollbar-sidebar">
        <div class="app-sidebar__inner">
            <ul class="vertical-nav-menu">
                <li class="app-sidebar__heading">{{t("Dashboards")}}</li>
                <li>
                    <a href="<?= __env ?>admin/dashboard" class="{{ $dv_is_current('admin/dashboard') ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-rocket"></i>
                        {{t("Dashboards")}}
                    </a>
                </li>
                @foreach ($dvups_navigation as $key => $component)
                    <li class="app-sidebar__heading">{{$component['label'] ?? $component['name'] }}</li>

                    @foreach ($component["listmodule"] as $key => $module)
                        <li class="{{ $dv_is_current('admin/' . $component['name'] . '/' . $module['name']) ? 'mm-active' : '' }}">
                            <a aria-expanded="true" href="#">
                                <i class="metismenu-icon {{ $module['icon'] ?? 'fas fa-fw fa-cog' }}"></i>

                                <span class="menu-title">{{$module['label'] ?? $module['name'] }}</span>
                                <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                            </a>
                            <ul class="mm-collapse">
                                <li>
                                    <a href="<?= __env ?>admin/{{$component['name'] }}/{{ $module['name'] }}/dashboard" class="{{ $dv_is_current('admin/' . $component['name'] . '/' . $module['name'] . '/dashboard') ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon"></i> {{t("Dashboard")}}
                                    </a>
                                </li>
                                @foreach ($module["listentity"] as $entity)
                                    <li>
                                        <a href="<?= __env ?>admin/{{ $entity['path'] }}{{strtolower($entity['name']) }}/list" class="{{ $dv_is_current('admin/' . $entity['path'] . strtolower($entity['name'])) ? 'mm-active' : '' }}">
                                            <i class="metismenu-icon"></i> {{$entity['label'] ?? $entity['name'] }}
                                           {{-- @ if($nb = $entity->alert())
                                              (  {$nb}} )
                                            @ endif--}}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                @endforeach
                <li class="app-sidebar__heading">{{t("Développement")}}</li>
                <li>
                    <a href="<?= __env ?>documentation/" target="_blank">
                        <i class="metismenu-icon pe-7s-plugin"></i>
                        {{t("Documentation API")}}
                    </a>
                </li>
                <li>
                    <a href="<?= __env ?>documentation/guide.php" target="_blank">
                        <i class="metismenu-icon pe-7s-note2"></i>
                        {{t("Guide Développeur")}}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
