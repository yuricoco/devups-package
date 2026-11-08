<?= Form::open($cmscontent, ["action" => "$action", "method" => "post"], true) ?>
<div class="row">

    <div class="col-lg-12">
        @if($tree_item_id = Request::get("tree_item"))
            <div class='form-group'>
                <label for='content'>{{$tree_item->getName()}}</label>
                <?= Form::select('tree_item', FormManager::Options_Helper("name", [$tree_item]), $tree_item_id, ['class' => 'form-control']); ?>
            </div>
        @else
            <div class='form-group'>
                <label for='content'>Rubrique</label>
                <?= Form::select('tree_item', FormManager::Options_Helper("name", Tree_item::mainmenu()->get()), $tree_item_id, ['class' => 'form-control']); ?>
            </div>
        @endif

        <div class='form-group'>
            <label for='lang'>Active</label>
            <?= Form::radio('active', Cmscontent::$ACTIVES, $cmscontent->getActive(), ['class' => 'form-control']); ?>
        </div>
        <div class='form-group'>
            <label for='lang'>Reference</label>
            <?= Form::input('reference', $cmscontent->getReference(), ['class' => 'form-control']); ?>
        </div>

        <div class='form-group'>
            <label for='titre'>Titre</label>
            <?= Form::input('title', $cmscontent->getTitle(), ['class' => 'form-control']); ?>
        </div>

        @foreach(Dvups_lang::all() as $lang)
            <div class='form-group'>
                <label for='titre'>Titre {{$lang->getIso_code()}}</label>
                <?= Form::input('title_' . $lang->getIso_code(), $cmscontent->__gettranslate("title", $lang->getIso_code(), $cmscontent->getTitle()), ['class' => 'form-control'], "text", ["persist" => false]); ?>
            </div>
        @endforeach

        <div class='form-group'>
            <label for='content'>Content</label>
            <?= Form::textarea('content', $cmscontent->getContent(), ['class' => 'form-control', 'id' => 'editor']); ?>
        </div>

        <div class='form-group'>
            <label for='content'>Sommary</label>
            <?= Form::textarea('sommary', $cmscontent->getSommary(), ['class' => 'form-control']); ?>
        </div>
    </div>
    <div class='col-lg-6'>
        <?= Form::submitbtn("Enregistrer et rester sur la page", ['onclick' => 'cmscontent.submit(this)', 'type' => 'button', 'class' => 'btn btn-info btn-block']) ?>
    </div>
    <div class='col-lg-6'>
        <?= Form::submitbtn("Enregistrer et allez a la liste", ['class' => 'btn btn-success btn-block']) ?>
    </div>
</div>
<?= Form::close() ?>

