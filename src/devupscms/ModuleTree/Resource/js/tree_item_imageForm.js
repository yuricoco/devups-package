/**
 * tree_item_imageForm
 * Generated by devups
 * on 2021/03/06
 */

Vue.component("tree_item_image", {
    data() {
        return {
            images: [],
            treeitem: {},
            contenturl: "",
            tree_itemparent: {},
            tree_itemtree: [],
        }
    },
    props: ["tree_item"],
    mounted() {

        var droppedFiles = false;
        var $form = $(this.$el);
        $form.addClass('has-advanced-upload');
        $form.on('drag dragstart dragend dragover dragenter dragleave drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
        })
            .on('dragover dragenter', function () {
                $form.addClass('is-dragover');
            })
            .on('dragleave dragend drop', function () {
                $form.removeClass('is-dragover');
            })
            .on('drop',  (e) => {
                droppedFiles = e.originalEvent.dataTransfer.files;

                model.init("dv_image");
                //ddatatable.init("dv_image");

                for (var i = 0; i < droppedFiles.length; i++) {
                    var file = droppedFiles[i];
                    console.log(file)
                    var fd = new FormData();
                    fd.append("image", file);
                    fd.append("name", file.name);

                    $.notify("Uploading " + file.name, "info")
                    // Drequest.init($("#dv_table").data("route") + "services.php?path=chapter.scanpages" + param)
                    Drequest.api("tree-item-image.upload?tree_item_id=" + this.tree_item.id)
                        .data(fd)
                        .post((response) => {
                            console.log(response);
                            if (response.success) {
                                this.images.push(response.image)
                                $.notify("Upload complete of " + file.name, "success");
                                return;
                            }
                            // todo handle error message of upload image
                        });

                }
            });

        Drequest.api("tree-item.images?id=" + this.tree_item.id)
            .get((response) => {
                console.log(response);
                this.images = response.items;
            })

    },
    methods: {
        _delete(id, index){
            console.log(id, index)
            Drequest.api("tree-item-image.delete?id=" + id)
                .get((response) => {
                    console.log(response);
                    this.images.splice(index, 1)
                })

        }
    },
    template: `
        <div id="galleryContainer" class="box card-body row">
            <div v-for="(image, $index) in images" :id="'image-'+image.id" class="col-md-4 col-xl-6 image-item ">
                <div class="card mb-3 widget-content">
                    <div class="widget-content-outer">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left">
                                <div class="widget-heading">
                                    <button type="button" @click="_delete(image.id, $index)"
                                            class="btn btn-danger">delete
                                    </button>
                                </div>
                                <div class="">
                                    <img :src="image.srcImage_100" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
});
Vue.component("image_item", {
    data() {
        return {
            chain: [],
            treeitem: {},
            contenturl: "",
            tree_itemparent: {},
            tree_itemtree: [],
        }
    },
    props: ["tree_item"],
    methods: {},
    template: `
        
    `
});