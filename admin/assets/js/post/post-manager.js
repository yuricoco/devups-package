

var postmanagervue = new Vue({
    el: "#postmanager",
    data: {
        db : Drequest.localstorage(),
        postcontent: "",
        embededcode: "",
        postcomponent: "",
        user: defaultuser,
        inprocess: false,
        userprofile: null,
        posttoshareid: null,
        post: postdata,
        postcomponentparam: {},
        videotoupload: {},
        filestoupload: [],
        postimages: [],
        urls: [],
        nbimageuploaded: 0,
    },
    mounted() {
        console.log(this.post)
        var post = this.db.post;
        if(post && this.post.id){
            this.post = post;
            this.postcontent = this.post.content;
            $("#postcontent").html(this.post.content);
        }
    },
    computed: {
        currentProperties: function () {
            console.log(this.postcomponent)
            return this.postcomponentparam;
        }
    },
    methods: {
        closecomponent: function () {
            this.postcomponent = "";
            $(this.$el).find("#postcontent").html("");
            //todo nullify all media variable
        },

        addimage: function () {
            if(!this.post.id){
                this.savesample(null, "post.create?publish=1");
            }
            $("input.images_form").trigger("click");
            // open dialog
        },
        addimages: function (ev) {
            if(!this.post.id){
                alert("Oops une erreur est survenue lors du chargement de vos images. Selectionnez de nouveau svp. si le problème persiste, actualiser la page, votre publication sera conservée.");
                return 0;
            }
            var files = ev.target.files;
            if (files[0]) {
                for (i = 0; i < files.length; i++) {
                    files[i].toupload = true;
                    this.filestoupload.push({file: files[i]});
                }
            }
            this.nbimages = this.filestoupload.length;
            $("#mapfile").val("");

        },
        uploaded: function () {
            this.nbimageuploaded++;
            console.log("uploaded se");
            if (this.nbimageuploaded === this.nbimages && this.product.id) {
                //this.callbackpersistence(this.serverresponse);
                console.log("all images hava well been uploaded thx")
            }
        },
        launchupload: function () {
            this.$root.$emit("productpersisted", this.product);
        },

        savesample(el, url) {
            if(el)
                model.addLoader($(el))

            console.log($(this.$el).find("#posted_at").val())
            this.db.post =  {
                "user.id": this.user.id,
                "publishedAt": $(this.$el).find("#posted_at").val(),
                //"tree_item\\category.id": $("#category-id").val(),
                "content": $("#postcontent").html()+"<br><br>"+this.embededcode,
            };
            Drequest.localsave(this.db);

            Drequest.api(url)
                .data(
                    {
                        post: this.db.post
                    })
                .raw((response) => {
                    if(el) {
                        model.removeLoader();
                        console.log(response);
                        $("#post-log").html(``)
                        if (response.success) {
                            this.post = {};
                            this.db.post = {};
                            Drequest.localsave(this.db)
                            $("#postcontent").html("")
                            this.filestoupload = [];
                            // if (edit)
                            //     window.location.reload();
                            // else
                            $("#activities").prepend(response.view);

                            return;
                        }
                        $("#post-log").html(`<div class="alert alert-warning">${response.detail}</div>`)
                    }else{

                        if (response.success) {
                            this.post.id = response.post.id;
                            this.db.post = this.post;
                            Drequest.localsave(this.db)
                        }

                    }
                })

        },
        loadImages: function (imagetoupload) {
            this.imagetoupload = imagetoupload;
        },
        setImagetoupload: function (imagetoupload) {
            this.imagetoupload = imagetoupload;
        },
        uploadfile: function (idpost, callback) {

            var nbuploaded = 0;
            var self = this;
            var fd = new FormData();
            fd.append("userid", this.user.id)

            if (this.imagetoupload.length === 1) {
                console.log(this.post.content)
                fd.append("content", this.post.content)
            }

            $.each(this.imagetoupload, function (index, file) {
                devups.upload(file, __env + `services.php?path=postimage.create&postid=${idpost}`,
                    (loaded, total) => {

                        // file.progress.value = loaded;
                        // file.progress.max = total;
                        file.value = loaded;
                        file.max = total;
                        self.$root.$emit("imageuploading", index, file);
                        // console.log(file.progress.max, file.progress.value);

                    },
                    function () {
                        nbuploaded++;
                        console.log(nbuploaded, self.imagetoupload.length);
                        if (nbuploaded === self.imagetoupload.length)
                            callback();
                    },
                    function (response) {
                        console.log(response);
                        $(file.progress).remove();
                        $(file.progress).remove();
                    }, "json", "image", fd
                )
            });

        },

        removeimage: function (file, index) {
            console.log(index);
            //if(!file.toupload){

            //}else{
            Drequest.api('postimage.delete?id=' + file.id).get((response) => {
                // model._apiget('product-image.delete?id=' + file.id, (response) => {
                console.log(response);
                this.filestoupload.splice(index, 1);
            });
            //}
        },
        postcontentlistner($event){
            var content = $($event.target);
            var contenttext = content.text();
            if(contenttext)
                content.parent().find("label.control-label").hide();
            else
                content.parent().find("label.control-label").show();

            //this.urls = devups.extracturl(content.text());
            //console.log(urls);
            console.log(content.html());
            this.postcontent = content.html();
        },

        publish: function ($event) {

            if (!this.postcontent) {
                alert("you must enter either a text, an image or a video");
                return 0;
            }

            if(this.post.id){
                this.savesample($event.target, "post.update?id="+this.post.id);
            }else
                this.savesample($event.target, "post.create?publish=1");

        }
    }
});
