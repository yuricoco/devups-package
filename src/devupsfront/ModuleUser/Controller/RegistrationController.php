<?php

/**
 * Codes d'activation (inscription, renvoi du code, changement de telephone).
 *
 * Le code en clair n'est jamais stocke : User::setActivationcode() le hache.
 * Il n'est jamais renvoye au client HTTP non plus : resendactivationcode()
 * le transmet a l'appelant PHP (pour l'envoi par mail/push) sous la cle
 * 'activationcode', que le contrôleur retire avant de repondre.
 */
class RegistrationController
{

    /**
     * Genere un code numerique (6 chiffres par defaut), avec un generateur
     * cryptographiquement sur.
     */
    public static function generatecode(int $length = 6): string
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= (string)random_int(0, 9);
        }
        return $code;
    }

    /**
     * Regenere et envoie par mail un nouveau code d'activation a
     * l'utilisateur authentifie (Auth::$user_id) ou a $user.
     *
     * @return array ['success' => bool, 'detail' => string, 'activationcode' => string]
     */
    public static function resendactivationcode(\User $user = null): array
    {
        if (!$user)
            $user = User::find(Auth::$user_id);

        if (!$user || !$user->getId())
            return ['success' => false, 'detail' => t("Utilisateur introuvable")];

        if ($user->isActivated())
            return ['success' => false, 'detail' => t("Ce compte est deja active")];

        $activationcode = self::generatecode();
        $user->setActivationcode($activationcode);
        $user->__update();

        if ($user->email) {
            DMail::init("mails.otp", $user->lang ?: __default_lang)
                ->addReceiver($user->email, $user->username)
                ->setTitle(t("Verification du compte"))
                ->setObject(t("Code d'activation"))
                ->sendMail([
                    "activation_code" => $activationcode,
                    "username" => $user->username,
                ]);
        }

        return [
            'success' => true,
            'detail' => t("Un nouveau code d'activation vous a ete envoye"),
            'activationcode' => $activationcode,
        ];
    }

}
