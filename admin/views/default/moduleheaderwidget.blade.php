<div class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon">
                <i class="{{ $moduledata['icon'] ?? 'fas fa-fw fa-cog' }}"></i>
            </div>
            <div>{{ $moduledata['label'] ?? $moduledata['name'] }}
                {{-- fil d'Ariane : classes .breadcrumb/.breadcrumb-item stylees par le theme (admin/main.css) --}}
                <ul class="breadcrumb p-0 m-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="{{ __env }}admin">{{ t('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ $moduledata['label'] ?? $moduledata['name'] }}</li>
                </ul>
                {{-- "Some text" retire, pas remplace par un attribut
                     "description" que personne n'aurait rempli (addendum §3). --}}
            </div>
        </div>
        <div class="page-title-actions">

        </div>
    </div>
</div>
<ul class="nav nav-justified">
    <li class="nav-item">
        <a class="nav-link active" href="#">
            <i class="metismenu-icon"></i> <span>Dashboard</span>
        </a>
    </li>
    @php
        $entities = getadmin()->_dvups_role->getAttribute('entities');
        // dump($entities);
    @endphp
    @foreach ($moduledata['entities'] as $entity)
        @if (in_array(strtolower($entity['name']), $entities))
            <li class="nav-item">
                <a class="nav-link active"
                    href="{{ __env }}admin/<?= $entity['path'] ?>{{ $entity['name'] }}/list">
                    <i class="metismenu-icon"></i> <span><?= $entity['label'] ?? $entity['name'] ?></span>
                </a>
            </li>
        @endif
    @endforeach
</ul>
