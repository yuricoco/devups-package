<h3>{{$entity}}</h3>

<hr>
<div class="form-group">
    <div class="row">
        <div class="col-lg-6">
            <label>Langue source</label>
            <select class="form-control" name="idlang">
                @foreach($langs as $lang)
                    <option value="{{$lang->id}}">{{$lang->iso_code}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-6">
            <label>Separateur de CSV</label>
            <input id="separator" name="separator" value=";" class="form-control" />
        </div>
    </div>
</div>
<div class="form-group">
    <label>Coller un contenu csv</label>
    <textarea id="contentcsv" class="form-control"></textarea>
</div>
<div class="form-group">
    <label>Importer un  fichier csv/xslx/array php/json</label>
    <input id="csvfile" class="form-control" type="file" accept=".csv,.xslx,.php,.json" />
</div>
<button onclick="dventity.importlang(this)" type="button" class="btn btn-info">Importer les données!</button>
<hr>
<div id="result"></div>
<div id="log-import"></div>
<script>

    var dventity = {
        exportdata (el){
            model.addLoader($(el))
            var fd = new FormData();
            if ($("#csvfile")[0].files[0])
                fd.append( "fixture", $("#csvfile")[0].files[0])
            else
                fd.append( "contentcsv", $("#contentcsv").val())
            Drequest.init(__env + "admin/services.php?path=import&classname={{$entity}}")
                .data(fd)
                .post((function (response) {
                    model.removeLoader();
                    console.log(response)
                    $("#result").html(`<a class="btn btn-primary btn-block" target="_blank" href="${response.result.download}">download the exported file</a>`)
                }))

        },
        lang:'',
        filename:'',
        importlang(el, loadlang = false) {
            var form = $(el).parents("form")
            var fd = new FormData();
            var url = __env + "admin/services.php?path=dvups_entity.importData&classname={{$entity}}&split="+$("#separator").val();

            this.lang = form.find("select[name=idlang]").val();
            /*if (!contentlang.langs.length) {
                alert("vous devez selectionnez au moins une langue pour le traitement")
                return;
            }*/
            var file = $("#csvfile")[0].files[0]
            /*if (!file || loadlang) {
                this.loadlang(url, 1, 1000)
                return;
            }*/
            if(file)
                fd.append("fixture", file)
            else
                fd.append( "contentcsv", $("#contentcsv").val())

            $("#log-import").html(`<div class="alert alert-info text-center">... Chargement en cours</div>`)

            Drequest.init(url)
                .data(fd)
                .post((response) => {
                    console.log(response);
                    if (!response.success) {

                        $("#log-import").html('<div class="alert alert-danger text-center">' + response.detail + "</div>");

                        return;

                    }
                    if(!file) {
                        $("#log-import").html('<div class="alert alert-success text-center">' + response.detail + "</div>");
                        return;
                    }
                    this.filename = response.filename
                    $("#log-import").html('<div class="alert alert-success text-center">' + response.detail + " enregistrement des langues en cours ...</div>");
                    this.loadlang(url, 1, 1000)

                })

        },
        loadlang(url, next, iteration) {
            Drequest.init(url)
                .param({
                    lang: this.lang,
                    //langs: contentlang.langs.join(),
                    next: next,
                    iteration: iteration,
                    filename: this.filename,
                }).get((response) => {
                console.log(response)

                if (!response.success) {

                    $("#log-import").html('<div class="alert alert-danger text-center">' + response.detail + "</div>");
                    return;
                }

                if (response.remain >= 0) {
                    $("#log-import").html('<div class="alert alert-info text-center">' + response.i + " traductions traitées ...</div>");
                    this.loadlang(url, next + iteration, iteration)
                } else {
                    $("#log-import").html('<div class="alert alert-success text-center"> Traitement de ' + response.i + " traductions terminés!</div>");
                    alert(" Traitement de " + response.i + " traductions terminés!")
                    //window.location.reload()
                }
            })
        },

    }

</script>
