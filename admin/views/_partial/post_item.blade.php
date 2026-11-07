@php
    $images = $post->__hasmany(Postimage::class);
$nbimage = count($images);
@endphp
<div class="post-info col-lg-12">
    <p title="<?= $post->user->getUsername();  ?>">
        <a href="{{route("activities?post=".$post->getId())}}">
            <?= $post->user->getUsername();  ?>
        </a><br>
        @<em><?= $post->getCreatedAt('d M Y - H:i');  ?></em>
    </p>
    <div style="max-height: 100px; overflow: hidden">{!! $post->getContent() !!}</div>
    <hr>
    <div class="">
        @if($nbimage)
            @if($nbimage == 1)
                <div style="max-height: 200px; overflow: hidden; margin: 3px" class="card">
                    <img src="<?= $images[0]->srcImage("270_"); ?>"
                         title="post image 3ag edition {{$images[0]->getId()}}"
                         alt="post image 3ag edition {{$images[0]->getId()}}"/>
                </div>
            @elseif($nbimage == 2)
                @foreach($images as $item)
                    <div style="width: 45%; display: inline-block; margin: 3px" class="card">
                        <img src="<?= $item->srcImage("270_"); ?>"
                             title="post image 3ag edition {{$item->getId()}}" style="max-width: 100%"
                             alt="post image 3ag edition {{$item->getId()}}"/>
                    </div>
                @endforeach
            @elseif( $nbimage >= 3 )
                @foreach($images as $item)
                    @break($loop->index == 4)
                    <div style="width: 31%; display: inline-block; margin: 3px" class="card">
                        <img src="<?= $item->srcImage("150_"); ?>"  style="max-width: 100%"
                             title="post image 3ag edition {{$item->getId()}}"
                             alt="post image 3ag edition {{$item->getId()}}"/>
                    </div>
                @endforeach
            @endif
        @endif
    </div>
    <p>
        {{ Postcomment::where($post)->count() }} @tt('Commentaire')

        @include('_partial._kola', ['nbkolas'=>Kola::where($post)->count(), 'entity'=>"post", 'entityid'=>$post->getId()])

        {{--<iframe src="https://www.facebook.com/plugins/share_button.php?href= &layout=button_count&size=small&mobile_iframe=true&width=83&height=20&appId"
                width="83" height="20" style="border:none;overflow:hidden" scrolling="no" frameborder="0"
                allowTransparency="true"></iframe>--}}

        {{--<a href="https://twitter.com/share?ref_src= "
           class="twitter-share-button" data-show-count="false">Tweet</a>
        <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>--}}
    </p>

</div>

