@php
    $images = $post->__hasmany(Postimage::class);
    //$comments = $post->__hasmany(Postcomment::class, false)->__getAll(true, ["postcomment.user"]);
$ll = new \dclass\devups\Datatable\Lazyloading();
    $ll->setPerPage(3)->lazyloading(new Postcomment(), Postcomment::where($post));
$nbimage = count($images);
@endphp

<div class="row">
<div class="post-info col-lg-12">
    <div class="card">
        <div class="card-header">
            <p title="<?= $post->user->getUsername();  ?>">
                <a href="{{route("activities?post=".$post->getId())}}">
                    <?= $post->user->getUsername();  ?>
                </a>
                @if($user->getId() == $post->user->getId())
                    <a class=" btn float-right" href="{{route("account/post?idpost=".$post->getId())}}">
                        <i class="fa fa-edit"></i> @tt("Modifier ma publication")
                    </a>
                @endif
            </p>
            <p>@<em><?= $post->getCreatedAt('d M Y - H:i');  ?></em></p>
        </div>
        <div class="card-body">
            {!! $post->getContent() !!}
            <hr>
            <ul id="thumbs" class="portfolio row">
                @if($nbimage)
                    @if($nbimage == 1)
                        <li class="item-thumbs design" data-id="id-{{$images[0]->_image->getId()}}" data-type="web">
                            <!-- Fancybox - Gallery Enabled - Title - Full Image -->
                            <a class="hover-wrap fancybox" data-fancybox-group="{{$post->getId()}}-gallery"
                               title="post image 3ag edition {{$images[0]->getId()}}"
                               href="{{$images[0]->_image->srcImage()}}">
                                <span class="overlay-img"></span>
                                <span class="overlay-img-thumb font-icon-plus"></span>
                            </a>
                            <!-- Thumb Image and Description -->
                            <img src="{{$images[0]->_image->srcImage("700_")}}"
                                 title="post image 3ag edition {{$images[0]->getId()}}"
                                 alt="post image 3ag edition {{$images[0]->getId()}}">
                        </li>
                    @elseif($nbimage == 2)
                        @foreach($images as $item)
                            <li class="item-thumbs col-lg-6 design" data-id="id-{{$item->_image->getId()}}"
                                data-type="web">
                                <!-- Fancybox - Gallery Enabled - Title - Full Image -->
                                <a class="hover-wrap fancybox" data-fancybox-group="{{$post->getId()}}-gallery"
                                   title="post image 3ag edition {{$images[0]->getId()}}"
                                   href="{{$item->_image->srcImage()}}">
                                    <span class="overlay-img"></span>
                                    <span class="overlay-img-thumb font-icon-plus"></span>
                                </a>
                                <!-- Thumb Image and Description -->
                                <img src="{{$item->_image->srcImage("270_")}}"
                                     title="post image 3ag edition {{$images[0]->getId()}}"
                                     alt="post image 3ag edition {{$images[0]->getId()}}">
                            </li>
                        @endforeach
                    @elseif($nbimage == 3 || $nbimage == 6)
                        @foreach($images as $item)
                            <li class="item-thumbs col-lg-4 design" data-id="id-{{$item->getImage()->getId()}}"
                                data-type="web">
                                <!-- Fancybox - Gallery Enabled - Title - Full Image -->
                                <a class="hover-wrap fancybox" data-fancybox-group="{{$post->getId()}}-gallery"
                                   title="post image 3ag edition {{$images[0]->getId()}}"
                                   href="{{$item->getImage()->srcImage()}}">
                                    <span class="overlay-img"></span>
                                    <span class="overlay-img-thumb font-icon-plus"></span>
                                </a>
                                <!-- Thumb Image and Description -->
                                <img src="{{$item->getImage()->srcImage("270_")}}"
                                     title="post image 3ag edition {{$images[0]->getId()}}"
                                     alt="post image 3ag edition {{$images[0]->getId()}}">
                            </li>
                        @endforeach
                    @else
                        @foreach($images as $item)
                            <li class="item-thumbs col-lg-3 design" data-id="id-{{$item->getImage()->getId()}}"
                                data-type="web">
                                <!-- Fancybox - Gallery Enabled - Title - Full Image -->
                                <a class="hover-wrap fancybox" data-fancybox-group="{{$post->getId()}}-gallery"
                                   title="post image 3ag edition {{$images[0]->getId()}}"
                                   href="{{$item->getImage()->srcImage()}}">
                                    <span class="overlay-img"></span>
                                    <span class="overlay-img-thumb font-icon-plus"></span>
                                </a>
                                <!-- Thumb Image and Description -->
                                <img src="{{$item->getImage()->srcImage("150_")}}"
                                     title="post image 3ag edition {{$images[0]->getId()}}"
                                     alt="post image 3ag edition {{$images[0]->getId()}}">
                            </li>
                        @endforeach
                    @endif
                @endif
            </ul>
            <div class="row">
                <div class="col-lg-8">
                    {{ Postcomment::where($post)->count() }} @tt('Commentaire')
                </div>
                <div class="col-lg-4">
                    @include('_partial._kola', ['nbkolas'=>Kola::where($post)->count(),
    'user'=>$user,'entity'=>"post", 'entityid'=>$post->getId()])
                </div>
            </div>

        </div>

        <div class="card-footer">

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
            <div class="content-form">
                @include("_partial.commentview")
            </div>
        </div>
    </div>
</div>
</div>

