/**
 * Created by Aurelien Atemkeng on 7/26/2018.
 */

var databinding = {
    bindmodal: function (data) {
        // console.log(model.entity);
        // model.modalbody.html(data);
        model.modalboxcontainer.html(data);
    },
    checkrenderform: function (response) {
        //console.log(response);
        if (response.form)
            this.bindmodal(response.form);
        else
            this.bindmodal(response);
    }
}

var model = {
    baseredirect: "index.php",
    baseurl: "services.php",

    spinner: '<span class="spinner-border spinner-border-sm mr-2" role="status"></span>',
    btnactive: null,
    separator: function (value, b) {

        if (!value)
            return 0;

        var a = '' + value + "".replace(/ /g, '');
        b = b || ' ';
        var c = '',
            d = 0;
        while (a.match(/^0[0-9]/)) {
            a = a.substr(1);
        }
        for (var i = a.length - 1; i >= 0; i--) {
            c = (d != 0 && d % 3 == 0) ? a[i] + b + c : a[i] + c;
            d++;
        }

        return c;

    },
    addLoader(btn) {
        this.btnactive = btn;
        this.btnactive.attr("disabled", true);
        this.btnactive.prepend(this.spinner);

    },
    removeLoader() {

        this.btnactive.attr("disabled", false);
        this.btnactive.find(".spinner-border").remove();

    },

    url: function (route, parameter) {
        var getAttr = "";
        if (parameter) {
            var keys = Object.keys(parameter);
            var values = Object.values(parameter);
            for (var i = 0; i < keys.length; i++) {
                getAttr += "&" + keys[i] + "=" + values[i];
            }
        }

        return route + getAttr;

    },
    clonerow: function (id, entity, el) {
        model.init(entity, el)
        model.addLoader($(el))
        var regex = /_/gi;
        Drequest.init(__env + "admin/api/clonerow?dclass=" + entity)
            .param({
                id: id
            })
            .get(function (response) {
                model.removeLoader()
                console.log(response)
                model.notify("Nouvelle ligne ajoutée avec succès!", "success");
                ddatatable.addrow(response.tablerow.row);
            })
            .fail(function (resultat, statut, erreur) {
                console.log(statut, erreur);
                databinding.bindmodal(resultat.responseText);
            });

    },
    routing: function (route, parameter) {
        return this.baseurl + "?path=" + this.url(route, parameter);
    },
    _showmodal: function (server = true) {
        //set content loader
        // model.modalbody.html('<div style="height: 150px; text-align: center; padding: 5%">Loading ...</div>');
        // model.modal.modal("show");
        if (server)
            model.modalboxcontainer.html(`
 
 <div  class="alert alert-warning">Loading ...</div>
 
`);
        model.modalbox.css('display', "inline-flex");
        model.modalbox.find(".swal2-modal").css('display', "inline-flex");
    },
    _dismissmodal: function (empty = true) {
        // model.modalbody.html("");
        // model.modal.modal("hide");
        if (empty)
            model.modalboxcontainer.html('');
        model.modalbox.css('display', "none");
        model.modalbox.find(".swal2-modal").css('display', "none");
        this.init();
    },
    notifyoptions : {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toastr-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    },
    notify(message, state, title = ""){
        $.notify(message, state)
        return 0;
        toastr.options = this.notifyoptions;
        if (state === 'success')
            toastr.success(message, title);
        else if (state === 'info')
            toastr.info(message, title);
        else if (state === 'warning')
            toastr.warning(message, title);
        else if (state === 'danger')
            toastr.danger(message, title);
        else
            toastr.success(message, title);
    },
    generatecache : function (el, entity) {
        model.addLoader($(el))
        Drequest.adminApi('generateConstantes?entity_name='+entity).get((response)=>{
            console.log(response)
            alert(response.detail)
            model.removeLoader()
        })
    },
    entity: null,
    _new: function (el, classname, param = '') {
        model.init(classname, el)

        //ddatatable.urlparam += param;
        this._showmodal();
        Drequest.adminApi(this.entity + "/form" + ddatatable.urlparam+param)
            .get(function (response) {
                console.log(response)
                databinding.checkrenderform(response);
            })
            .fail(function (resultat, statut, erreur) {
                console.log(statut, erreur);
                databinding.bindmodal(resultat.responseText);
            });

    },
    _edit: function (id, entity, el) {
        model.init(entity, el)
        var regex = /_/gi;
        //string..replace(regex, '-')

        this._showmodal();

        //model.request(this.entity + "._edit")
        Drequest.adminApi(this.entity + "/form?1" + ddatatable.urlparam)
            .param({
                id: id
            })
            .get(function (response) {
                console.log(response)
                databinding.checkrenderform(response);
            })
            .fail(function (resultat, statut, erreur) {
                console.log(statut, erreur);
                databinding.bindmodal(resultat.responseText);
            });

    },
    _uploadfile: function (id, entity, el, attr) {
        model.init(entity, el)
        var regex = /_/gi;
        //string..replace(regex, '-')

        var fd = new FormData();
        fd.append(this.entity +'_form['+$(el).attr('name')+']', el.files[0])
        //model.request(this.entity + "._edit")
        $(el).attr("disabled", true);
        $(el).replaceWith('<span class="spinner-border spinner-border-sm mr-2" role="status"></span>');
        Drequest.adminApi(this.entity + "/update?id=" + id)
            .data(fd)
            .post(function (response) {
                console.log(response)
                model.notify("Nouvelle ligne mise à jour avec succès!", "success");
                ddatatable.replacerow(id, response.tablerow.row);
                // ddatatable.addrow(response.tablerow.row);
                //$("#dv_table").find("#"+entityid).replaceWith(response.tablerow);
                model._dismissmodal();
                return;
            })
            .fail(function (resultat, statut, erreur) {
                console.log(statut, erreur);
                databinding.bindmodal(resultat.responseText);
            });

    },
    callbackdelete(id, response) {

    },
    _delete: function (id, entity, el, callback) {
        model.init(entity)
        this.addLoader($(el))
        var $tr = $(el).parents("tr");
        var $td = $(el).parents("td");

        if (!confirm('Voulez-vous Supprimer?')) return false;

        Drequest.adminApi(this.entity + "/delete?1"+ ddatatable.urlparam)
            .param({
                id: id
            })
            .get((response) => {
                this.removeLoader();
                console.log(response)
                if ($tr.length)
                    $tr.remove();
                if (callback)
                    callback(response);
                this.callbackdelete(id, response)
            })
            .fail(function (resultat, statut, erreur) {
                console.log(statut, erreur);
                databinding.bindmodal(resultat.responseText);
            });

    },
    _show: function (id, entity, callback) {
        model.init(entity)
        this._showmodal();

        Drequest.init(this.baseurl)
            .param({
                path: this.entity + "._show",
                id: id
            })
            .get((response) => {
                console.log(response);
                databinding.bindmodal(response);
            })
            .fail(function (resultat, statut, erreur) {
                console.log(statut, erreur);
                databinding.bindmodal(resultat.responseText);
            });

    },
    _formdatacustom: function (tbody) {

        var $rows = tbody.find("tr");
        var formentity = {};
        $.each($rows, function (i, row) {

            var $inputs = $(row).find('input');
            var $textareas = $(row).find('textarea');

            var value = {fr: $textareas.eq(1).val(), en: $textareas.eq(0).val()}

            formentity[$inputs.eq(0).val()] = value;
        })

        model.formentity = formentity;
        return formentity

    },
    _formdata: function (form, formdata) {
        var $inputs = form.find('input');
        var $textareas = form.find('textarea');
        var $selects = form.find('select');
        var formentity = {};

        if (!formdata)
            formdata = new FormData();

        $.each($inputs, function (i, input) {

            if ($(input).attr('type') === "file" && input.files[0]) {
                formdata.append($(input).attr('name'), input.files[0]);
                formentity[$(input).attr('name')] = $(input).val();
            } else if ($(input).attr('type') === "checkbox" && input.checked) {
                formdata.append($(input).attr('name'), $(input).val());
                formentity[$(input).attr('name')] = $(input).val();
            } else if ($(input).attr('type') === "radio" && input.checked) {
                formdata.append($(input).attr('name'), $(input).val());
                formentity[$(input).attr('name')] = $(input).val();
            } else if ($(input).attr('type') === "password") {
                formdata.append($(input).attr('name'), $(input).val());
                formentity[$(input).attr('name')] = $(input).val();
            } else if ($(input).attr('type') === "email") {
                formdata.append($(input).attr('name'), $(input).val());
                formentity[$(input).attr('name')] = $(input).val();
            } else if ($(input).attr('type') === "date") {
                formdata.append($(input).attr('name'), $(input).val());
                formentity[$(input).attr('name')] = $(input).val();
            } else if ($(input).attr('type') === "number") {
                formdata.append($(input).attr('name'), $(input).val());
                formentity[$(input).attr('name')] = $(input).val();
            } else if ($(input).attr('type') === "text") {
                formdata.append($(input).attr('name'), $(input).val());
                formentity[$(input).attr('name')] = $(input).val();
            }
        });
        $.each($textareas, function (i, textarea) {
            formdata.append($(textarea).attr('name'), $(textarea).val());
            formentity[$(textarea).attr('name')] = $(textarea).val();
        });
        $.each($selects, function (i, select) {
            formdata.append($(select).attr('name'), $(select).val());
            formentity[$(select).attr('name')] = {
                value: $(select).val(),
                option: $(select).find(":selected").text(),
            }
        });

        model.formentity = formentity;
        return formdata;
    },

    loading: false,
    dataset: [],
    autocompleteshow(el) {
        var component = $(el).parents(".dv-autocomplete");
        component.find("ul").show();
    },
    autocompletehide(el) {
        var component = $(el).parents(".dv-autocomplete");
        setTimeout(() => {
            component.find("ul").hide();
        }, 300)
    },
    bindresult(options, component, listEntity) {
        $.each(listEntity, function (i, item) {
            if (options.lang) {
                /*component.find("select").append($('<option>', {
                    value: item[options.value],
                    text: item[options.text][options.lang]
                }));*/
                component.find("ul").append(`<li onclick="model.selectvalue(this, ${i})" class="list-group-item" >${item[options.text][options.lang]}<span id="${options.entity}-${item[options.value]}" hidden >${JSON.stringify(item)}</span></li>`);
            } else {
                /*component.find("select").append($('<option>', {
                    value: item[options.value],
                    text: item[options.text]
                }));*/
                component.find("ul").append(`<li onclick="model.selectvalue(this, ${i})" class="list-group-item" >${item[options.text]}<span id="${options.entity}-${item[options.value]}" hidden >${JSON.stringify(item)}</span></li>`);
            }
        });
    },
    autocompletereset(el, value, text) {
        this.selectvalue(el, value, text)
    },
    selectvalue(el, i) {
        var component = $(el).parents(".dv-autocomplete");
        var tagoption = {
            value: model.dataset[i][model.options.value],
            text: model.dataset[i][model.options.text],
        }
        component.find("input").val(tagoption.text);
        if (model.options.lang) {
            component.find("select").html($('<option>', {
                value: tagoption.value,
                text: tagoption.text[model.options.lang],
            }));
            component.find("input").val(tagoption.text[model.options.lang]);
        }else
            component.find("select").html($('<option>', {
                value: tagoption.value,
                text: tagoption.text,
            }));

        if (model.options.itemselected) {
            var item = JSON.parse($(el).find("span").text());
            console.log(item, model.options.itemselected)
            //model.selectitemcallback(tagoption.value, tagoption.text, item);
            model[model.options.itemselected](tagoption.value, tagoption.text, item);
        }

    },
    autocomplete: function (el) {
        var component = $(el).parents(".dv-autocomplete"),
            options = component.data("options"),
            value = el.value,
            _param = {"dfilters": "on"};

        model.options = options
        if (value.length < 3) {
            model.dataset = [];
            this.loading = false;
            this.loaddata = false;
            return;
        }
        var data = [];
        if (this.loading) {
            if (model.dataset.length) {
                /*component.find("select").html($('<option>', {
                    value: "",
                    text: "... loading"
                }));*/
                component.find("ul").html(`<li class="list-group-item">... loading</li>`);
                data = model.filterrow(value, model.dataset, options.searchkey);
                model.bindresult(options, component, data)
            }
            if (data.length === 0) {
                this.loading = false;
            } else
                return;
        }

        /*component.find("select").html($('<option>', {
            value: "",
            text: "... loading"
        }));*/
        component.find("ul").show();
        component.find("ul").html(`<li class="list-group-item">... loading</li>`);
        this.loading = true;

        for (let v of options.search) {
            _param[v] = value
        }
        console.log(options, value);
        if (!options.base_url)
            options.base_url = __env+"api/lazyloading." + options.entity

        Drequest.init(options.base_url)
            .param(_param)
            .get((response) => {
                console.log(response)

                // component.find("select").html("");
                component.find("ul").html(``);
                model.dataset = response.listEntity

                if (options.callback) {
                    model[options.callback](response);
                }

                if (response.nb_element)
                    model.bindresult(options, component, response.listEntity)
                else {
                    component.find("select").html($('<option>', {
                        value: "",
                        text: "--- Aucun element trouve ---"
                    }));
                    component.find("ul").html(`<li class="list-group-item">--- Aucun element trouve ---</li>`);
                }
            })
    },

    filterrow(value, dataarray, key) {
        var filter, filtered = [], i, data;

        console.log(dataarray);
        filter = value.toUpperCase();

        for (i = 0; i < dataarray.length; i++) {
            data = dataarray[i];
            if (data[key].toUpperCase().indexOf(filter) > -1) {
                filtered.push(data);
            }
        }
        return filtered;
    },

    request: function (action) {
        // var formdata = this._formdata(form);
        // model.modalbody.append('<div id="loader" style="position: absolute;bottom:0; z-index: 3; height: 60px; text-align: center; padding: 5%">Loading ...</div>');
        console.log(this.baseurl)
        return Drequest.init(this.baseurl + "?path=" + action)
    },

    getformvalue: function (field) {
        return this.formentity[this.entity + "_form[" + field + "]"];
    },
    getform: function (fm, entity, attribs) {
        this._formdata(fm);
        var keys = Object.keys(this.formentity);
        var values = Object.values(this.formentity);
        var form = [];
        var fd = new FormData();
        attribs.forEach((attr) => {
            for (var i = 0; i < keys.length; i++) {

                if (keys[i] === entity + "_form[" + attr + "]") {
                    form[attr] = values[i];
                    console.log(typeof values[i]);
                    if (typeof values[i] === 'string')
                        fd.append(keys[i], values[i]);
                    else
                        fd.append(keys[i], values[i].value);

                    break;
                }

            }
        });

        //form['dvups_form['+entity+']'] = this.formentity['dvups_form['+entity+']'];
        form[entity] = this.formentity['dvups_form[' + entity + ']'];
        console.log(JSON.parse(form[entity]));
        fd.append('dvups_form[' + entity + ']', this.formentity['dvups_form[' + entity + ']']);
        form.fd = fd;
        return form;
    },
    entitytoformdata(entity, entityformmodel) {
        var fd = new FormData();
        this.formentity = {};
        var keys = Object.keys(entity);
        var values = Object.values(entity);

        for (var i = 0; i < keys.length; i++) {
            if (typeof values[i] === 'object' && values[i] !== null)
                fd.append(entityformmodel.name + `_form[${keys[i]}]`, values[i].id)
            else
                fd.append(entityformmodel.name + `_form[${keys[i]}]`, values[i])
        }
        fd.append('dvups_form[' + entityformmodel.name + ']', JSON.stringify(entityformmodel.field));

        return fd;
    },
    entitytoformentity(entity, persistance = []) {

        var formentity = {};
        var keys = Object.keys(entity);
        var values = Object.values(entity);

        for (var i = 0; i < keys.length; i++) {
            if (persistance.length) {
                if (persistance.includes(keys[i])) {
                    if (typeof values[i] === 'object' && values[i] !== null) {
                        formentity[keys[i] + ".id"] = values[i].id;
                        //formentity[entityformmodel.name+`_form[${keys[i]}]`] = values[i].id;
                    } else
                        formentity[keys[i]] = values[i]
                }
            } else {
                if (typeof values[i] === 'object' && values[i] !== null) {
                    formentity[keys[i] + ".id"] = values[i].id;
                    //formentity[entityformmodel.name+`_form[${keys[i]}]`] = values[i].id;
                } else
                    formentity[keys[i]] = values[i]
            }
            // formentity[entityformmodel.name+`_form[${keys[i]}]`] = values[i]
        }
        //this.formentity['dvups_form['+entityformmodel.name+']'] = entityformmodel.field;

        return formentity;
    },
    getformfield: function (field) {
        return $("input[name='" + this.entity + "_form[" + field + "]']");
    },
    modalbox : $("#dialog-container"),
    modalboxcontainer : $("#dialog-container").find(".box-container .card-body"),
    init: function (entity, el) {

        if (!entity)
            return;
        model.entity = entity;
        ddatatable.init(entity, el);
        model.baseurl = ddatatable.baseurl;
        // model.entity = dvdatatable.eq(0).data('entity');

        // model.modal = $("#" + model.entity + "modal");
        // model.modalbox = $("#" + model.entity + "box");
        // model.modalbody = $("#" + model.entity + "modal").find(".modal-body");
        // model.modalboxcontainer = $("#" + model.entity + "box");//.find(".box-container .card-body");

    }
};

//setTimeout(function () {
// model.init();
//}, 800)

