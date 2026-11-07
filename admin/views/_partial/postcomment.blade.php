<!--<img src="../web/images/avatar.png" class="img-responsive" alt="">-->
<div class="desc">

    <div class="post-author_head">
        @if(!$comment->getUsername())
            <a href="{{route('profile/?id='.$comment->user->getId())}}"
               rel="author"><?= $comment->user->getUsername(); ?></a>
        @else
            <a href="#" rel="author"><?= $comment->getUsername(); ?></a>
        @endif
        <br>
            <small><?= $comment->getCreatedAt('d M Y - H:i');  ?></small>
    </div>
    <div><?= $comment->getComment(); ?></div>

    @include('_partial._kola', ['nbkolas'=>Kola::where($comment)->count(),
'user'=>$user,'entity'=>"postcomment", 'entityid'=>$comment->getId()])
</div>
<div class="clearfix"></div>
