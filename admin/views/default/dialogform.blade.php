{{--
    Non utilise par le noyau (seul un bloc commente de
    FormManager::renderForm() y fait reference). Conserve pour les projets
    qui l'incluraient.
--}}
<div class="main-card mb-3 card " style="width: {{$width}}px">
    <div class="card-header">
        <h3 class="card-title"> {!! $formtitle !!}</h3>
        <div class="card-toolbar">
            <button type="button" onclick="model._dismissmodal()"
                    class="btn btn-default "
                    aria-label="Close this dialog"
                    style="display: block;">×
            </button>
        </div>
    </div>
    <div class="card-body">
        {!! $formcontent !!}
    </div>
</div>
