<em>

    @if(!$user->getId() || !$entityid)
        <span class="kola">
            <i class="kolagiven"></i> <p class="error" style="color: red"><?= $nbkolas ?> kola(s)</p>
            @elseif($id = Kola::on($user, " $entity.id = $entityid "))
                <button onclick="app.removekola(this, {{$id}}, '{{$entity}}', {{$entityid}})"
                        class="btn btn-primary">
                                        <i class="mensionkola kolagiven"></i> @tt("+1 kola")</button>
            @else
                <button onclick="app.givekola(this, {'user.id':<?= $user->getId() ?>, '{{$entity}}.id':{{$entityid}}})"
                        class="btn kola"><i class="mensionkola kolatook"></i> @tt("Donner une kola")</button>
    @endif
</em>
@if($user->getId())
    <em id="nbkolas" class="tvalue pull-right" style="color: #eb1c24;"><?= $nbkolas ?> kola(s)</em>
@endif