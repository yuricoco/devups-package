/**
            * reportingmodelCtrl
            * Generated by devups
            * on 2021/04/05
            */

model.sendmail = function (el, id) {
    model.addLoader($(el))
    var email = $("#email-" + id).val();
    model.init("reportingmodel");
    model.request("reportingmodel.testmail&id=" + id + '&email=' + email).get((response) => {
        console.log(response);
        model.removeLoader();
    })
}