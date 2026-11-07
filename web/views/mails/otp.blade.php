@extends("mails.email")
@section("content")

    <div style="color:#1f1f1f;line-height:1.25em;max-width:400px;margin:0 auto"
         align="center">
        <h3>
            {{langt("Aidez nous à proteger votre compte", "Help us protect your account", $lang)}}
        </h3>
        <p style="font-size:0.9em">
            {{langt("Avant que vous vous connectiez, nous avons besoin de verifier votre identite. Entrer le code suivant sur la page de verification",
"Before you sign in, we need to verify your identity. Enter the following
            code on the sign-in page.", $lang)}}

        </p>
        <div style="width:207px;height:53px;background-color:#f0f0f0;line-height:53px;font-weight:700;font-size:1.5em;color:#303030;margin:26px 0">
            {{$activation_code}}
        </div>
        <p style="font-size:0.75em">
            {{langt("Si vous n'etes pas a l'origine de cette demande, ignorez ce message.",
"If you did not request this code, you can ignore this email.", $lang)}}
            {{langt("Votre code de verification expire dans 60 minutes",
"Your verification code expires after 60 minutes.", $lang)}}

        </p>
    </div>

@endsection