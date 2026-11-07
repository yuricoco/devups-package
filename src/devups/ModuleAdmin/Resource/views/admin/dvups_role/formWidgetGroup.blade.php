
<?= Form::open($dvups_role, ["action" => $action, "method" => "post"]) ?>

<div class='text-left'>
    <div class='form-group'>
        <label for='name'>{{t('Role name')}} </label>
        <?= Form::input('name', $dvups_role->getName(), ['class' => 'form-control']); ?>
    </div>
    <div class='form-group'>
        <label for='alias'>{{t('Alias for commun')}}</label>
        <?= Form::input('alias', $dvups_role->alias, ['class' => 'form-control']); ?>
    </div>
    <div class='form-group'>
        <label>{{t('Droits (entite / action)')}}</label>
        <table class='table'>
            @foreach($actionsByEntity as $entity => $entityActions)
                <tr>
                    <th>{{$entity}}</th>
                    <td>
                        @foreach($entityActions as $dvups_action)
                            <label class='checkbox-inline' style='margin-right: 12px;'>
                                <input type='checkbox' name='privileges[]' value='{{$dvups_action->getId()}}'
                                    @if(in_array($dvups_action->getId(), $grantedActionIds)) checked @endif >
                                {{$dvups_action->action}}
                            </label>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <label for='dvups_module'>Dvups_module</label>
    <table class='table'>
        <tr>
            <td>Component</td>
            <td></td>
        </tr>
        @foreach($configs as $component)
            <tr class='form-group'>
                <th class='form-group '>
                    <?= Form::input('components', [$component->name, $components], ['class' => ''], "checkbox"); ?>
                    {{$component->name}}
                </th>
                <td>
                    <table class='table'>
                        @foreach($component->listmodule as $module)
                            <tr class='form-group'>
                                <th class='form-group '>
                                    <?= Form::input('modules', [$module->name, $modules], ['class' => ''], "checkbox"); ?>
                                    {{$module->name}}
                                </th>
                                <td>

                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class='row pl-6'>
                                        @foreach($module->listentity as $entity)
                                            <div class="col-lg-3">
                                                <label class='form-group '>
                                                    <?= Form::input('entities', [$entity->name, $entities], ['class' => ''], "checkbox"); ?>
                                                    {{$entity->name}}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        @endforeach

    </table>

</div>

<?= Form::submitbtn("save", ['class' => 'btn btn-success']) ?>

<?= Form::close() ?>

<?= Form::addDformjs() ?>
<?= Form::addjs(Dvups_role::classpath('Resource/js/dvups_roleForm')) ?>
    