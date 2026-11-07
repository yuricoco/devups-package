<div class="">

    <div class="">
        <div class="form relative ">

            <label><?= t("laissez un commentaire") ?></label>
            <label class=""></label>
            <?= Form::textarea('comment', "", ['rows' => '3', 'data-emojiable' => 'true', 'id' => $post->getId() . '-comment-form', 'class' => 'form-control comment']); ?>
        </div>
        @if(!$user->getId())
            <div class="form relative ">
                <label class="">@tt("Votre nom ou pseudo")</label>
                <?= Form::input('username', "", ['id' => $post->getId() . '-username-form', 'class' => 'form-control']); ?>
            </div>
        @endif
        <div class="form relative ">
            <button class="btn btn-primary float-right"
                    onclick="app.newcomment(this, {{$post->getId()}})"><?= t("Commenter") ?></button>
        </div>
        <div class="form relative ">
            <p class="error"></p>
        </div>
    </div>
</div>