
@php
    $dvups_navigation = $admin->_dvups_role->getConfigs();
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
                    <i class="fa fa-ellipsis-v fa-w-6"></i>
                </span>
            </button>
        </span>
    </div>
    <div class="scrollbar-sidebar">
        <div class="app-sidebar__inner">
            <ul class="vertical-nav-menu">
                <li class="app-sidebar__heading">{{t("Dashboards")}}</li>
                <li>
                    <a href="<?= __env ?>admin/" class="mm-active">
                        <i class="metismenu-icon pe-7s-rocket"></i>
                        {{t("Dashboards")}}
                    </a>
                </li>
                @foreach ($dvups_navigation as $key => $component)
                    <li class="app-sidebar__heading">{{$component['name'] }}</li>

                    @foreach ($component["listmodule"] as $key => $module)
                        <li>
                            <a aria-expanded="true" href="#">
                                <i class="metismenu-icon ">
                                    <i class="fas fa-fw fa-cog"></i>
                                </i>

{{--<i class="{{$module["module"]->getFavicon()}}"></i>--}}
                                <span class="menu-title">{{$module['name'] }}</span>
                                <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                            </a>
                            <ul class="mm-collapse">
                                <li>
                                    <a href="/admin/{{$component['name'] }}/{{ $module['name'] }}/">
                                        <i class="metismenu-icon"></i> {{t("Dashboard")}}
                                    </a>
                                </li>
                                @foreach ($module["listentity"] as $entity)
                                    <li>
                                        <a href="/admin/{{ $entity['path'] }}{{strtolower($entity['name']) }}/list">
                                            <i class="metismenu-icon"></i> {{$entity['name'] }}
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
            </ul>
        </div>
    </div>
</div>
