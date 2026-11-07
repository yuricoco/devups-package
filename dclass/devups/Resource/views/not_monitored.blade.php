@extends('layout.layout_module')
@section('content_module')
    <div class="row">
        <div class="col-md-12" style="padding: 60px 20px; text-align: center;">
            <i class="pe-7s-attention" style="font-size: 48px; color: #d3d3d3;"></i>
            <h4 style="margin-top: 16px;">{{ t('contenu non monitoré, veuillez contacter le développeur') }}</h4>
            @if ($entityName || $moduleName)
                <p style="font-size: 0.85em; color: #888; margin-top: 8px;">
                    {{ trim($entityName . ' / ' . $moduleName, ' /') }}
                </p>
            @endif
        </div>
    </div>
@endsection
