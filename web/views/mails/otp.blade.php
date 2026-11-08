@extends("mails.email")
@section("content")

    <div style="color:#1f1f1f;line-height:1.25em;max-width:400px;margin:0 auto"
         align="center">
        <h3>
            {{ t_lang("Aidez nous à proteger votre compte", $lang ?? __default_lang) }}
        </h3>
        <p style="font-size:0.9em">
            {{ t_lang("Avant que vous vous connectiez, nous avons besoin de verifier votre identite. Entrer le code suivant sur la page de verification", $lang ?? __default_lang) }}
        </p>
        <div style="width:207px;height:53px;background-color:#f0f0f0;line-height:53px;font-weight:700;font-size:1.5em;color:#303030;margin:26px 0">
            {{$activation_code}}
        </div>
        <p style="font-size:0.75em">
            {{ t_lang("Si vous n'etes pas a l'origine de cette demande, ignorez ce message.", $lang ?? __default_lang) }}
            {{ t_lang("Votre code de verification expire dans 60 minutes", $lang ?? __default_lang) }}
        </p>
    </div>

@endsection
