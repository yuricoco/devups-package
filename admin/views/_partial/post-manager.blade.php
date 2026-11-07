
<div style="text-align: initial !important;" id="postmanager" class="row">

    <div class="col-lg-12">
        <div class="form-group with-icon label-floating is-empty">
            <label class="control-label">Publier une act...</label>
            <!--textarea v-model="post.content" class="form-control" placeholder=""></textarea-->
            <div style="height: inherit; min-height: 100px" contenteditable="true" id="postcontent" @keyup="postcontentlistner($event)" class="form-control" ></div>
        </div>
        <div class="form-group with-icon label-floating is-empty">
            <label class="control-label">Embended code insert after the text</label>
            <!--textarea v-model="post.content" class="form-control" placeholder=""></textarea-->
            <textarea v-model="embededcode" style="height: inherit; min-height: 100px" class="form-control" ></textarea>
        </div>
        <div class="form-group with-icon label-floating is-empty">
            <label class="control-label">Date de publication : </label>

            <input id="posted_at" value="{{date("d/m/Y")}}" type="date" class="form-control">
        </div>
        <div id="post-log" ></div>
    </div>

    <div v-if="filestoupload.length" id="sortable" class="form-group table-responsive">

        <table class="table ">
            <thead>
            <tr>
                <th>Image</th>
                <th>@tt("label")</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr is="post-image-manager" v-for="(image, $index) in filestoupload"
                v-bind:key="image.id"
                v-bind:image="image"
                v-bind:post="post"
                v-bind:index="$index">
            </tr>
            </tbody>
        </table>
    </div>
    <div v-if="postimages.length" class="form-group table-responsive">

        <table class="table ">
            <thead>
            <tr>
                <th>Image</th>
                <th>@tt("label")</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr is="post-image-manager" v-for="(image, $index) in filestoupload"
                v-bind:key="image.id"
                v-bind:image="image"
                v-bind:post="post"
                v-bind:index="$index">
            </tr>
            </tbody>
        </table>
    </div>
    <div class='col-lg-6'>
        <input hidden @change="addimages($event)" type="file" multiple class="images_form">
        <button @click="addimage" class="btn btn-info btn-block">@tt("Ajouter des images")</button>
    </div>
    <div class='col-lg-6'>
        <button @click="publish" class="btn btn-info btn-block">@tt("Publier")</button>
    </div>
</div>
<script >
    var postdata = @json($post);
    var defaultuser = @json(User::getdefault());
</script>
<?php foreach (dclass\devups\Controller\Controller::$jsfiles as $jsfile){ ?>
<script src="<?= $jsfile ?>"></script>
<?php } ?>
