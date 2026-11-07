@php
    $images = $post->__hasmany(Postimage::class);
    //$comments = $post->__hasmany(Postcomment::class, false)->__getAll(true, ["postcomment.user"]);
$ll = new \dclass\devups\Datatable\Lazyloading();
    $ll->setPerPage(3)->lazyloading(new Postcomment(), Postcomment::where($post));
$nbimage = count($images);
@endphp

<div class="post-info  ">
    <div class="text-right">
        <button onclick="app.togglecommentbox(false)" class="btn btn-primary">
            @tt("Fermer")
        </button>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="">
                <div style="width: 58%; display: inline-block" class="col-lg-8">
                    {{ Postcomment::where($post)->count() }} @tt('Commentaire')
                </div>
                <div style="width: 38%; display: inline-block" class="col-lg-4">
                    @include('_partial._kola', ['nbkolas'=>Kola::where($post)->count(),
    'user'=>$user,'entity'=>"post", 'entityid'=>$post->getId()])
                </div>
            </div>

        </div>

        <div style="max-height: 500px; overflow: auto;" class="card-footer">

            <ul id="{{$post->getId()}}-comments" class="comment-list">
                <?php foreach ($ll->listentity as $comment) { ?>
                <li>
                    @include("_partial.postcomment")
                </li>
                <?php } ?>
                @if($ll->next > 1 && $ll->nb_element)
                    <li id="{{$post->getId()}}-next-comment">
                        <hr>
                        <button data-next="{{$ll->next}}" onclick="app.nextcomment(this, {{$post->getId()}})" class="btn btn-block">@tt("voir les autres commentaires")</button>
                    </li>
                @endif
            </ul>
            <!--<a href="single.html" class="read-more"><span></span>read more</a>-->
            <div style="position: sticky; bottom: 30px;" class="content-form">
                @include("_partial.commentview")
            </div>
        </div>
    </div>
</div>

