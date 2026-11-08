<?php
/**
 * Created by PhpStorm.
 * User: Aurelien Atemkeng
 * Date: 20/04/2019
 * Time: 11:56 AM
 */

namespace DClass\lib;

use DOMDocument;
use ReflectionFunction;
use Request;

class Util
{

    const dateformat = 'Y-m-d H:i:s';

    public static function handleSessionLost($redirect = "admin/")
    {

        if (!isset($_SESSION[ADMIN]) and $_GET['path'] != 'connexion') {
            header("location: " . __env . $redirect);
        }
    }

    public static function nicecomponent($component)
    {
        return str_replace("-", "_", $component);
    }

    public static function initLocation($reload = false)
    {

        if (!isset($_SESSION[USERLOCATION]) || $reload) {
            $location = ip_info("154.72.171.182");
            $_SESSION[USERLOCATION] = serialize($location);

            return;
        }

//        $location = self::getLocation();
//        if(!isset($location["city"]))
//            self::initLocation(true);

    }

    public static function getLocation()
    {
        return unserialize($_SESSION[USERLOCATION]);
    }

    public static function dateiso($creationdate)
    {
        return date("Y-m-d H:i:s", strtotime($creationdate));
    }

    public static function money($amount)
    {
        return number_format($amount, 0, '', ' ');
    }

    public static function quantity($quantity)
    {
        return number_format($quantity, 2, ',', ' ');
    }

    public static function local()
    {
        if (Request::get('lang')) {
            return Request::get('lang');
        } elseif (isset($_SESSION[LANG]))
            return $_SESSION[LANG];

        return __lang;
    }

    /**
     * this method write file with path setted on ROOT folder of the project by default
     * @param $content
     * @param string $file
     * @param string $root
     * @param string $mode
     */
    static function writein($content, $file = "log", $root = "", $mode = "a+")
    {

        if (!file_exists(ROOT . $root))
            mkdir(ROOT . $root, 0777, true);

        $moddepend = fopen(ROOT . $root . '/' . $file, $mode);
        fputs($moddepend, $content . "\n");
        fclose($moddepend);
    }

    public static function log($content, $file = "log", $root = ROOT, $mode = "a+")
    {

        if (!$content)
            return;

        $moddepend = fopen($root . '/' . $file, $mode);
        fputs($moddepend, "  " . $content . "\n");
        fclose($moddepend);
    }

    public static function nfacture($nbcaract, $emptycarat, $value)
    {
        $acte = "";
        $remaincarat = $nbcaract - strlen($value);
        for ($i = 0; $i < $remaincarat; $i++)
            $acte .= $emptycarat;
        //$acte = "0000";
        return $acte . $value;
    }

    /**
     * @param mixed
     */
    public static function randomcode($length = 8, $list = "0123456789abcdefghijklmnopqrstvwxyzABCDEFGHIJKLMNOPQRSTVWXYZ+-@%*$")
    {

        mt_srand((double)microtime() * 1000000);
        $password = "";
        while (strlen($password) < $length) {
            $password .= $list[mt_rand(0, strlen($list) - 1)];
        }
        return $password;

    }

    public static function validation($value, $type = "telephone")
    {
        if ($type == "telephone") {
            if (!is_numeric($value))
                return t("Entrer une valeur numérique");
            if (strlen($value . "") != 9)
                return t("le numéro doit etre constitué de 9 caractères");
        }
        return null;
    }

    public static function urlsanitize($str, $charset = 'utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
        $str = str_replace(' ', '-', $str); // supprime les autres caractères
        $str = str_replace(',', '', $str); // supprime les autres caractères
        $str = str_replace('\'', '', $str); // supprime les autres caractères

        return strtolower($str);
    }

    public static function setcookie($key, $value, $expireseconds = null)
    {
        // ETAPE K.3 : Secure si la requete est en HTTPS (jamais force a vrai
        // en clair, pour ne pas casser le cookie en dev/http local),
        // SameSite=Lax - cf. l'option "samesite" du tableau d'options
        // (seule forme qui le permette ; l'ancienne signature positionnelle
        // de setcookie() n'a pas de parametre SameSite).
        setcookie($key, $value, [
            'expires' => time() + ($expireseconds ?? 365 * 24 * 3600),
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function clearcookie($key)
    {
        setcookie($key, null, [
            'expires' => -1,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            return true;
        return ($_SERVER['SERVER_PORT'] ?? null) == 443
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https';
    }

    public static function dateadd($duration, $period, $from)
    {
        return date("Y-m-d H:i:s", strtotime($duration . " $period", strtotime($from)));
    }

    public static function sanitizePhonenumber($phonenumber, $phone_code)
    {
        $telephone = str_replace(" ", "", $phonenumber);
        $telephone = str_replace("(", "", $telephone);
        $telephone = str_replace(")", "", $telephone);
        $telephone = str_replace("+" . $phone_code, "", "+" . $telephone);
        return str_replace("+", "", $telephone);
    }

    /**
     * @throws \ReflectionException
     */
    public static function get_func_argNames($funcName)
    {
        $f = new ReflectionFunction($funcName);
        $result = array();
        foreach ($f->getParameters() as $param) {
            $result[] = $param->name;
        }
        return $result;
    }


    /**
     * @param mixed $login
     */
    public static function generateLogin($base)
    {//on envoi une liste de login
        $list = "1234567890";
        mt_srand((double)microtime() * 10000);
        $generate = "";
        while (strlen($generate) < 4) {
            $generate .= $list[mt_rand(0, strlen($list) - 1)];
        }

        if (strlen($base) > 6)
            $alias = substr($base, 0, -(strlen($base) - 6));
        else
            $alias = $base;

        $login = self::wd_remove_accents($alias) . $generate;
        return strtolower($login);

    }

    protected static function wd_remove_accents($str, $charset = 'utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
        return str_replace(' ', '_', $str); // supprime les autres caractères
    }

    public static function arrayToPhpFile($array, $filename, $folder = 'cache/')
    {
        // $filename est interpole
        // dans un chemin disque. Generique (appele aussi par Core.php avec
        // des litteraux fixes, et par Tree::rebuildCacheFiles() avec
        // "<name>.<iso>" / "<name>_references.<iso>"), donc on refuse ici
        // tout nom hors d'un alphabet sans danger - jamais un nettoyage
        // silencieux. Le point est autorise comme separateur de SEGMENT
        // (ex. "gender.fr") mais deux points consecutifs (".." - traversee
        // de chemin) sont rejetes, de meme qu'un point en tete/en queue.
        if (!preg_match('/^[A-Za-z0-9_\-]+(\.[A-Za-z0-9_\-]+)*$/', $filename)) {
            throw new \InvalidArgumentException(
                "Util::arrayToPhpFile() : nom de fichier refuse (\"" . $filename . "\") - " .
                "seuls lettres, chiffres, '_', '-' et '.' (comme separateur de segment, jamais '..') sont autorises."
            );
        }
        $phpContent = "<?php\n\nreturn " . var_export($array, true) . ";\n";
//        file_put_contents(ROOT . "cache/routes.json", json_encode(self::$routes));
//        if (!file_exists(ROOT . "cache/routes.php"))

        // Ecriture atomique : un require() concurrent sur
        // le fichier final ne doit jamais tomber sur un contenu tronque.
        // On ecrit dans un fichier temporaire du MEME dossier (tmpfile()
        // dans un autre dossier risquerait un rename() inter-volume, qui
        // n'est pas atomique), puis rename() - atomique sur un meme
        // systeme de fichiers, aussi bien POSIX que Windows/NTFS.
        $targetPath = ROOT . $folder . "$filename.php";
        $tmpPath = $targetPath . '.' . uniqid('tmp_', true);
        $written = file_put_contents($tmpPath, $phpContent);
        if ($written === false) {
            throw new \RuntimeException("Util::arrayToPhpFile() : ecriture du fichier temporaire impossible (\"$tmpPath\").");
        }
        if (!rename($tmpPath, $targetPath)) {
            @unlink($tmpPath);
            throw new \RuntimeException("Util::arrayToPhpFile() : rename() a echoue (\"$tmpPath\" -> \"$targetPath\").");
        }
    }

    // camalcasing
    public static function toPascalCase($string)
    {
        // Supprime les caractères non alphabétiques et remplace les séparateurs par des espaces
        $string = preg_replace('/[^a-zA-Z0-9]+/', ' ', $string);

        // Met chaque mot en majuscule et supprime les espaces
        return lcfirst(str_replace(' ', '', ucwords(strtolower($string))));
    }

    public static function getIdentifier($iso = "")
    {
        $fuseauxHorairesParPays = [
            'AF' => ['Asia/Kabul'],
            'AL' => ['Europe/Tirane'],
            'DZ' => ['Africa/Algiers'],
            'AS' => ['Pacific/Pago_Pago'],
            'AD' => ['Europe/Andorra'],
            'AO' => ['Africa/Luanda'],
            'AR' => [
                'America/Argentina/Buenos_Aires',
                'America/Argentina/Cordoba',
                'America/Argentina/Salta',
                'America/Argentina/Jujuy',
                'America/Argentina/Tucuman',
                'America/Argentina/Catamarca',
                'America/Argentina/La_Rioja',
                'America/Argentina/San_Juan',
                'America/Argentina/Mendoza',
                'America/Argentina/San_Luis',
                'America/Argentina/Rio_Gallegos',
                'America/Argentina/Ushuaia'
            ],
            'AM' => ['Asia/Yerevan'],
            'AU' => [
                'Australia/Sydney',
                'Australia/Melbourne',
                'Australia/Brisbane',
                'Australia/Perth',
                'Australia/Adelaide',
                'Australia/Hobart',
                'Australia/Darwin',
                'Australia/Broken_Hill',
                'Australia/Lord_Howe'
            ],
            'AT' => ['Europe/Vienna'],
            'AZ' => ['Asia/Baku'],
            'BS' => ['America/Nassau'],
            'BH' => ['Asia/Bahrain'],
            'BD' => ['Asia/Dhaka'],
            'BY' => ['Europe/Minsk'],
            'BE' => ['Europe/Brussels'],
            'BZ' => ['America/Belize'],
            'BJ' => ['Africa/Porto-Novo'],
            'BM' => ['Atlantic/Bermuda'],
            'BT' => ['Asia/Thimphu'],
            'BO' => ['America/La_Paz'],
            'BA' => ['Europe/Sarajevo'],
            'BW' => ['Africa/Gaborone'],
            'BR' => [
                'America/Sao_Paulo',
                'America/Rio_Branco',
                'America/Manaus',
                'America/Boa_Vista',
                'America/Campo_Grande',
                'America/Cuiaba',
                'America/Porto_Velho',
                'America/Recife',
                'America/Fortaleza',
                'America/Bahia',
                'America/Noronha'
            ],
            'BN' => ['Asia/Brunei'],
            'BG' => ['Europe/Sofia'],
            'BF' => ['Africa/Ouagadougou'],
            'BI' => ['Africa/Bujumbura'],
            'KH' => ['Asia/Phnom_Penh'],
            'CM' => ['Africa/Douala'],
            'CA' => [
                'America/Toronto',
                'America/Vancouver',
                'America/Montreal',
                'America/Edmonton',
                'America/Winnipeg',
                'America/Halifax',
                'America/St_Johns',
                'America/Whitehorse',
                'America/Yellowknife',
                'America/Iqaluit'
            ],
            'CV' => ['Atlantic/Cape_Verde'],
            'CF' => ['Africa/Bangui'],
            'TD' => ['Africa/Ndjamena'],
            'CL' => [
                'America/Santiago',
                'Pacific/Easter'
            ],
            'CN' => ['Asia/Shanghai', 'Asia/Urumqi'],
            'CO' => ['America/Bogota'],
            'KM' => ['Indian/Comoro'],
            'CG' => ['Africa/Brazzaville'],
            'CI' => ['Africa/Abidjan'],
            'CD' => [
                'Africa/Kinshasa',
                'Africa/Lubumbashi'
            ],
            'CR' => ['America/Costa_Rica'],
            'HR' => ['Europe/Zagreb'],
            'CU' => ['America/Havana'],
            'CY' => ['Asia/Nicosia'],
            'CZ' => ['Europe/Prague'],
            'DK' => ['Europe/Copenhagen'],
            'DJ' => ['Africa/Djibouti'],
            'DM' => ['America/Dominica'],
            'DO' => ['America/Santo_Domingo'],
            'EC' => ['America/Guayaquil', 'Pacific/Galapagos'],
            'EG' => ['Africa/Cairo'],
            'SV' => ['America/El_Salvador'],
            'GQ' => ['Africa/Malabo'],
            'ER' => ['Africa/Asmara'],
            'EE' => ['Europe/Tallinn'],
            'ET' => ['Africa/Addis_Ababa'],
            'FI' => ['Europe/Helsinki'],
            'FR' => ['Europe/Paris'],
            'GA' => ['Africa/Libreville'],
            'GM' => ['Africa/Banjul'],
            'GE' => ['Asia/Tbilisi'],
            'DE' => ['Europe/Berlin', 'Europe/Busingen'],
            'GH' => ['Africa/Accra'],
            'GR' => ['Europe/Athens'],
            'GT' => ['America/Guatemala'],
            'GN' => ['Africa/Conakry'],
            'GW' => ['Africa/Bissau'],
            'GY' => ['America/Guyana'],
            'HT' => ['America/Port-au-Prince'],
            'HN' => ['America/Tegucigalpa'],
            'HU' => ['Europe/Budapest'],
            'IS' => ['Atlantic/Reykjavik'],
            'IN' => ['Asia/Kolkata'],
            'ID' => [
                'Asia/Jakarta',
                'Asia/Pontianak',
                'Asia/Makassar',
                'Asia/Jayapura'
            ],
            'IR' => ['Asia/Tehran'],
            'IQ' => ['Asia/Baghdad'],
            'IE' => ['Europe/Dublin'],
            'IL' => ['Asia/Jerusalem'],
            'IT' => ['Europe/Rome'],
            'JM' => ['America/Jamaica'],
            'JP' => ['Asia/Tokyo'],
            'KE' => ['Africa/Nairobi'],
            'KR' => ['Asia/Seoul'],
            'MX' => [
                'America/Mexico_City',
                'America/Cancun',
                'America/Monterrey',
                'America/Mazatlan',
                'America/Tijuana'
            ],
            'US' => [
                'America/New_York',
                'America/Chicago',
                'America/Denver',
                'America/Los_Angeles',
                'America/Anchorage',
                'Pacific/Honolulu'
            ],
            'ZA' => ['Africa/Johannesburg']
        ];

//        print_r($fuseauxHorairesParPays);

        if ($iso)
            if (array_key_exists($iso, $fuseauxHorairesParPays))
                return $fuseauxHorairesParPays[$iso];
            else
                return [];

        return $fuseauxHorairesParPays;

    }

    /**
     * ETAPE P.3 : seule fonction a interroger pour l'IP du client (utilisee
     * par RateLimiter et Country). Les en-tetes transmis par le client
     * (X-Forwarded-For et assimiles) ne sont dignes de confiance QUE si la
     * requete vient elle-meme d'un proxy de confiance (REMOTE_ADDR dans
     * TRUSTED_PROXIES, .env, vide par defaut) - sinon n'importe qui peut
     * forger ces en-tetes pour usurper une IP et contourner le
     * RateLimiter (ou se faire passer pour un autre pays).
     */
    public static function get_IP_address()
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($remoteAddr !== null && self::isTrustedProxy($remoteAddr)) {
            foreach (array(
                         'HTTP_CLIENT_IP',
                         'HTTP_X_FORWARDED_FOR',
                         'HTTP_X_FORWARDED',
                         'HTTP_X_CLUSTER_CLIENT_IP',
                         'HTTP_FORWARDED_FOR',
                         'HTTP_FORWARDED',
                     ) as $key) {
                if (array_key_exists($key, $_SERVER) && ($ip = self::firstValidPublicIp($_SERVER[$key])) !== null)
                    return $ip;
            }
        }

        return $remoteAddr !== null ? self::firstValidPublicIp($remoteAddr) : null;
    }

    /**
     * Premiere IP publique valide d'une liste separee par des virgules
     * (format de X-Forwarded-For), ou null si aucune.
     */
    private static function firstValidPublicIp($headerValue)
    {
        foreach (explode(',', (string)$headerValue) as $candidate) {
            $candidate = trim($candidate); // Just to be safe

            if (filter_var($candidate,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                !== false) {

                return $candidate;
            }
        }
        return null;
    }

    /**
     * true si $ip figure dans TRUSTED_PROXIES (liste separee par des
     * virgules, .env - vide par defaut : aucun proxy de confiance, donc
     * aucun en-tete X-Forwarded-For pris en compte tant que ce n'est pas
     * configure explicitement).
     */
    private static function isTrustedProxy($ip): bool
    {
        if ($ip === null || $ip === '')
            return false;

        $trusted = array_filter(array_map('trim', explode(',', TRUSTED_PROXIES)));
        return in_array($ip, $trusted, true);
    }
    public static function getTimezone()
    {
        if (!IPGEOLOCATION_API_KEY)
            return 'UTC';

        $adresseIP = $_SERVER['REMOTE_ADDR'];
        $url = "https://api.ipgeolocation.io/timezone?apiKey=" . IPGEOLOCATION_API_KEY . "&ip=$adresseIP";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        return $data['timezone'] ?? 'UTC';

    }

    public static function getLitteralDAte($date, $lang = 'fr')
    {
        if (!$date)
            return "";

        if ($lang == 'en') {
            return date("l", strtotime($date)) . ' ' . date(' M, Y', strtotime($date));

        } else {
            $jours = ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"];
            $mois = ["", "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre"];

//            $jour = $jours[date('w', strtotime($date))];
            $numJour = date("d", strtotime($date));
            $moisTxt = $mois[date("n", strtotime($date))];
            $annee = date("Y", strtotime($date));
            return " $numJour $moisTxt $annee";

        }
    }

    public static function parseTranslate($text, $l = null) {
        $result = [];
        if (!$text)
            return null;

        // Regex : capture "etranslate_xx" puis le texte jusqu'au prochain "etranslate_yy"
        preg_match_all('/etranslate_(\w+)\s+([\s\S]*?)(?=etranslate_\w+|$)/', $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $lang = trim($match[1]);      // ex: "fr"
            $content = trim($match[2]);   // le texte associé
            $result[$lang] = $content;
            if ($l)
                return $content;
        }

        if (count($result))
            return $result;
        return null;
    }

    public static function getTranslation($html, $lang){
        // Expression régulière pour extraire le bloc désiré
        $pattern = '/\[etranslate_' . preg_quote($lang, '/') . '\](.*?)(?=\[etranslate_|$)/s';

        if (preg_match($pattern, $html, $matches)) {
            $content = trim($matches[1]);
        } else {
            // Si la langue demandée n'existe pas, on tente de prendre le premier bloc trouvé
            if (preg_match('/\[etranslate_\w+\](.*?)(?=\[etranslate_|$)/s', $html, $fallback)) {
                $content = trim($fallback[1]);
            } else {
                // Aucun bloc trouvé
                return $html;
            }
        }
        return $content;
    }

    /**
     * Extrait le contenu HTML d'une langue spécifique à partir d'un texte multi-langues
     * structuré avec des blocs :
     * [etranslate_fr] ... [etranslate_en] ...
     *
     * @param string $html  Le contenu complet (contenant tous les blocs de langue)
     * @param string | array $lang  Langue désirée (ex: "fr" ou "en")
     * @return string | array       Le contenu HTML du bloc correspondant
     */
    public static function parseTranslateHtml($html, $lang = ['fr', 'en'])
    {
        if (!$html)
            return null;
        // Nettoyage d'encodage
        $html = trim($html);

        if (is_string($lang))
            return self::getTranslation($html, $lang);

        $content = [];
        foreach ($lang as $l) {
            $content[$l] = self::getTranslation($html, $l);
        }
        return $content;

    }

    public static  function convertCase(string $string, string $to = 'camel'): string {
        switch ($to) {
            case 'camel':
                $str = str_replace(['-', '_'], ' ', strtolower($string));
                $str = lcfirst(str_replace(' ', '', ucwords($str)));
                return $str;

            case 'pascal':
                $str = str_replace(['-', '_'], ' ', strtolower($string));
                $str = str_replace(' ', '', ucwords($str));
                return $str;

            case 'snake':
                $str = preg_replace('/([a-z])([A-Z])/', '$1_$2', $string);
                return strtolower(str_replace('-', '_', $str));

            case 'kebab':
                $str = preg_replace('/([a-z])([A-Z])/', '$1-$2', $string);
                return strtolower(str_replace('_', '-', $str));

            default:
                return $string;
        }
    }

    public static function fileToBase64(string $file): string
    {
        if (!file_exists($file))
            return '';
        return 'data:image/png;base64,' . base64_encode(file_get_contents($file));
    }

    public static function toHours($duration, $unit)
    {
        $unit = strtolower($unit);
        switch ($unit) {
            case 'minutes':
            case 'min':
                return round($duration / 60, 1);
            case 'week':
            case 'weeks':
            case 'semaines':
                return $duration * 7 * 24;
            case 'day':
            case 'days':
            case 'jours':
                return $duration * 24;
            case 'month':
            case 'months':
            case 'mois':
                return $duration * 30 * 24;
            default:
                return $duration;
        }
    }

}
