/**
            * push_emailCtrl
            * Generated by devups
            * on 2022/03/06
            */

model.sendmail = function (el, idnotif) {
    model.addLoader($(el))
    Drequest.init(__env+"admin/api/push-email.sendnotification?id="+idnotif)
        .get((response)=>{
            model.removeLoader()
            alert("Relance envoyer avec succes")
            console.log(response)
        })
}