Vue.component("localcontentForm", {
    data() {
        return {
            inprocess: false,
            baseurl: "",
            content: "",
            local_contentparent: {},
            local_contenttree: [],
        }
    },
    props: ["local_content", "lang"],
    mounted() {
        console.log(this.local_content)
        this.baseurl = $("#lang_component").data("baseurl");
        this.content = this.local_content.content[this.lang.iso_code];
    },
    methods: {
        exportlang() {
            Drequest.init(__env + "admin/api/local-content.exportlang")
                .get((response) => {
                    console.log(response)
                })
        },
        update() {
            this.inprocess = true;
            console.log(this.baseurl);
            var fd = new FormData();
            fd.append("local_content", this.content);
            fd.append("idlang", this.lang.id);
            Drequest.init(this.baseurl + 'local-content.update&id=' + this.local_content.id).
                /*toFormdata({
                    "local_content": this.content
                })*/
                data(fd)
                .post(response => {
                    console.log(response);
                    this.inprocess = false;
                    //("Categorié mise à jour avec succès!", "success");

                });
        },

    },
    template: `
        
        <div class="panel">
            <div class="card-body">
                <form id="frmEdit" class="form-horizontal">
                    <div class="form-group">
                        <label for="text">content {{lang.iso_code}}</label>
                        <textarea  v-model="content"  class="form-control " 
                               placeholder="Text"></textarea>

                    </div>
                </form>
            </div>
            <div class="card-footer">
                <button :disabled="inprocess" @click="update()" type="button" class="btn btn-primary">
                    <i v-if="inprocess" class="material-icons mi-autorenew">autorenew</i>
                    <i class="fas fa-sync-alt"></i> Update
                </button>
            </div>
        </div>
    `
});

Vue.component("local_content", {
    data() {
        return {
            local_contenttree: [],
            local_contents: [],
            local_content_keys: [],
            local_contentstring: "",
            search: "",
            resultdatas: [],
            local_content: {},
            langs: langs,
            ll: {},
            componentkey: 1,
        }
    },
    props: ["tree"],
    mounted() {
        console.log(this.$parent.baseurl)
        Drequest.init(__env+"admin/api/local-content.detaillang?id=" + this.tree.id)
        //Drequest.init(this.$parent.baseurl + "local-content.getlang&id=" + this.tree.id)
            .get((response) => {
                //model._apiget("local-content.getlang&id=" + this.tree.id, (response) => {
                console.log(response);
                this.local_content = response.local_content;
                this.local_content_keys = response.local_content_keys;
            })

    },
    methods: {},
    template: `

        <div class="panel">
            <div v-if="local_content.id" class="row">
                <div class="col-12"> 
                    <div class=" "> 
                            <h3 class="">{{local_content.reference}} | </h3>
                    </div>  
                </div>
                <div class="col-md-7">
                    <div class="card-body">
                        
                        <ul id="sortable" class="sortableLists list-group">
                                    
                            <li is="localcontentForm" v-for="(lang, $index) in langs" 
                                v-bind:key="lang.iso_code"
                                :local_content="local_content" 
                                :content="local_content.content[lang.iso_code]" 
                                :lang="lang" 
                                :index="$index" 
                                class="list-group-item"></li>
                            
                        </ul>
                    </div>
                </div>
                <div class="col-md-5">
                    <b>Occurence dans les vues</b>
                    <div class="card-body">
                        
                        <ul class="list-group">
                                    
                            <li v-for="(lck, $index) in local_content_keys"  
                                class="list-group-item">
                                <b>{{lck.path}}/</b>
                                {{lck.default_content}}
</li>
                            
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    `
})

var local_contentview = new Vue({
    el: '#lang_component',
    data: {
        datas: [],
        trees: [],
        langfilter: "",
        baseurl: "",
        next: 1,
        tree: {},
        ll: {},
        treeedit: {},
        loading: false,
    },
    mounted() {
        this.baseurl = $(this.$el).data("baseurl");
        console.log(this.baseurl)
        this.loaddata();
    },
    methods: {
        regeneratecache(event) {
            model.addLoader($(event.target));
            Drequest.init(this.baseurl + "local_content.regeneratecache").get((response) => {
                console.log(response);
                model.removeLoader();
                alert(response.message)
            })

        },
        loaddata(next = 1) {
            this.loading = true;
            this.trees = [];
            var parap = "";
            if (this.langfilter)
                parap = "&dfilters=on&reference:opt=" + this.langfilter;

            Drequest.init(this.baseurl + "local-content.get&next=" + next + "&per_page=30" + parap)
                .get((response) => {
                    // model._apiget("local-content.get", (response) => {
                    console.log(response);
                    this.loading = false;
                    this.trees = response.listEntity;
                    this.datas = response.listEntity;
                    //this.next = response.next;
                    this.ll = response;
                })
        },
        nextpage() {
            //this.next += 1;
            if (this.ll.next <= this.ll.pagination)
                this.loaddata(this.ll.next);
        },
        previous() {
            //this.next -= 1;
            if (this.ll.previous > 0)
                this.loaddata(this.ll.previous);
        },
        searchdata() {

        },
        filter() {
            if (this.langfilter)
                this.trees = this.filterrow(this.langfilter, this.datas)
            else
                this.trees = this.datas;

            console.log("local_content.getdata client");
        },

        filterrow(value, dataarray) {
            var filter, filtered = [], i, data;

            console.log(dataarray);
            filter = value.toUpperCase();

            for (i = 0; i < dataarray.length; i++) {
                data = dataarray[i];
                if (data.reference.toUpperCase().indexOf(filter) > -1) {
                    filtered.push(data);
                }
            }
            return filtered;
        },

        init(tree) {
            console.log(tree)
            this.tree = tree;
            console.log("local_content.getdata client");
        },
        buildcache() {
            this.loading = true;
            model.init("local_content")
            Drequest.init(this.baseurl + "local_content.regeneratecache")
                .get((response) => {
                    this.loading = false;
                    console.log(response);
                    alert(response.message)
                })

        },
        _delete(id, index) {

            if (!confirm("confirmer la suppression?"))
                return;

            Drequest.init(this.baseurl + "local_content_key._delete&id=" + id)
                .get((response) => {
                    //model._apiget("local-content.delete&id=" + id, (response) => {
                    console.log(response);
                    this.trees.slice(index, 1)
                });

        },

    }
});

