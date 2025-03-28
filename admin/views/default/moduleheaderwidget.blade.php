<div class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon">
                <i class="fas fa-fw fa-cog"></i>
            </div>
            <div>{{ $moduledata['name'] }}
                <div class="page-title-subheading">Some text</div>
            </div>
        </div>
        <div class="page-title-actions">

        </div>
    </div>
</div>
<ul class="nav nav-justified">
    <li class="nav-item">
        <a class="nav-link active"
           href="#">
            <i class="metismenu-icon"></i> <span>Dashboard</span>
        </a>
    </li>
    @php $entities = getadmin()->_dvups_role->getAttribute('entities');
 // dump($entities);
    @endphp
    @foreach ($moduledata['entities'] as $entity)
        @if(in_array(strtolower($entity['name']), $entities))
            <li class="nav-item">
                <a class="nav-link active"
                   href="/admin/<?=  $entity['path'] ?>{{$entity['name']}}/list">
                    <i class="metismenu-icon"></i> <span><?= $entity['name'] ?></span>
                </a>
            </li>
        @endif
    @endforeach
</ul>