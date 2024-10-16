/**
 * reportingmodelForm
 * Generated by devups
 * on 2021/04/05
 */


var reportingmodel = {
    delay: null,
    lang: "fr",
    template: $("#template").html(),
    editorlangs: {},
    editor: {},
    previewFrame: {},
    preview: {},
    init() {

        $(".dv-editable").hide()
        $("#container-" + this.lang).show()

        let previewFrame = document.getElementById('preview');
        this.preview = previewFrame.contentDocument || previewFrame.contentWindow.document;

        for (let lang of langs) {
            tinymce.init({
                selector: '#code-' + lang.iso_code,
                setup: function (editor) {
                    editor.on('init', function (e) {
                        console.log('The Editor has initialized.');
                    });
                    editor.on('keyup', function (e) {
                        //editor.triggerSave();
                        reportingmodel.updatePreview(editor.getContent())
                        // clearTimeout(reportingmodel.delay);
                        // reportingmodel.delay = setTimeout(() => reportingmodel.updatePreview(editor.getContent()), 300);
                    });
                },
                height: 550,
                fontSize: 10,
                relative_urls: false,
                remove_script_host: false,
                convert_urls: true,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image preview anchor ',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic | image link | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help code',
                content_css: [
                    '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
                    // '//www.tiny.cloud/css/codepen.min.css'
                ]
            });
            this.editorlangs[lang.iso_code] = $('#code-' + lang.iso_code)

        }
        this.editor = this.editorlangs[this.lang];
        this.updatePreview(this.editor.val())

    },
    updatePreview(val) {
        this.editor.val(val)
        this.preview.open();
        this.preview.write(
            this.template.replace(/{yield}/g, val.replace(/{__env}/g, __env))
        );
        this.preview.close();
        $(this.preview.getElementsByTagName("body")).attr("contenteditable", true)
        // added this line
        //tinymce.activeEditor.setContent(this.editor.val())

    },
    updateEditor() {
        this.editor.val($(this.preview.getElementById("yield")).html())
        //tinymce.activeEditor.setContent(this.editor.val())
    },
    loadFromFile(el, lang) {
        model.addLoader($(el))
        Drequest.init(__env + "admin/api/reportingmodel.load-content?lang=" + lang + "&name=" + report.name)
            .get((response) => {
                model.removeLoader()
                if (response.success) {
                    this.editor.val(response.content)
                    tinymce.activeEditor.setContent(this.editor.val())
                } else {
                    alert(response.detail)
                }
            })
    },
    submit(el) {
        //tinymce.triggerSave();
        // var form = $("#cmstext-form");
        var fd = new FormData();
        for (let lang of langs) {
            fd.append("reportingmodel_form[content][" + lang.iso_code + "]", this.editorlangs[lang.iso_code].val())
            fd.append("reportingmodel_form[contenttext][" + lang.iso_code + "]", $("#contenttext-" + lang.iso_code).val())
        }
        model.addLoader($(el))

        Drequest.init(__env + "api/update/reportingmodel?id=" + report.id)
            .data(fd)
            .post((response) => {
                console.log(response);
                model.removeLoader()
                $.notify("Modification enregistrées", "info")
            })
    },
    changelang(el) {
        $(".dv-editable").hide()
        $("#container-" + el.value).show()
        // $(".dv-title").hide()
        // $("#dv-title-" + el.value).show()

        this.lang = el.value;

        this.editor = $("#code-" + this.lang);
        this.updatePreview(this.editor.val())
        console.log(this.editor)
        // let previewFrame = document.getElementById('preview');
        // this.preview = previewFrame.contentDocument || previewFrame.contentWindow.document;

    },

}
reportingmodel.init();

