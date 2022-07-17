/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var imagetoupload = [];

Vue.component("post-image-manager", {
    data() {
        return {
            oburl: "",
            cover: "",
            thisfile: {},
            progressionmax: 0,
            progressionval: 0,
        }
    },
    props: ['image', 'post', 'index'],
    beforeMount() {
        if(this.image.id)
            this.thisfile = this.image;
        else
            this.thisfile = this.image.file;
    },
    mounted() {
        // if (!idprod) {
        //     this.upload();
        // }
        this.cover = this.post.cover;
        this.addimages();
        this.$root.$on('productpersisted', (data)=>{
            console.log(data)
            if (!this.image.id)
                this.upload();
        })
    },
    methods: {
        addimages: function () {
            if (this.image.id){
                this.oburl = this.image.show50;
                return;
            }
            this.oburl = window.URL.createObjectURL(this.image.file);
            this.upload();
        },
        oncomplete(response){

            console.log(response);
            this.thisfile = response.postimage;
            if ( ! this.post.id)
                postmanagervue.uploaded();

        },
        upload(){
            var el = this.$el;
            devups.upload(this.image.file, __env+`api/postimage.upload?idpost=${this.post.id}`,
                 function(loaded, total) {
                console.log(loaded, total)
                     $(el).find(".progress-bar").css({
                         "width": ((loaded / total) * 100) + "%",
                         "transition": '.3s',
                     });
                     el.progressionval = loaded;
                     el.progressionmax = total;
                    //$(this.$el).find(".progress").eq(index)[0].max = total;
                },
                function () {}, this.oncomplete, "json", "image"
            )
        },
        remove(){
            if(!confirm("Confirmez la supprission de l'image"))
                return;

            postmanagervue.removeimage(this.thisfile, this.index);
        }
    }, // thisfile.type
    template: `<tr >
        <td>
        <embed v-if="!thisfile.id" v-bind:type="thisfile.type" width="50px" v-bind:src="oburl" />
        <img v-if="thisfile.id" width="50px" v-bind:src="oburl" />
        </td>
        <td>
            <div class="progress">
                <div class="progress-bar bg-primary" role="progressbar"
                     style="width: 0%" aria-valuenow="25"
                     aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
            {{thisfile.id}} / {{thisfile.name}}  
            <progress v-if="thisfile.toupload" v-bind:value="progressionval" v-bind:max="progressionmax" class="progress"></progress>
        </td>
        <td>
            <button v-if="thisfile.id" @click="remove()" class="btn btn-danger btn-xs" type="button">
                <i class="fa fa-minus "></i> Supprimer
            </button>
        </td>
    </tr>`
});

