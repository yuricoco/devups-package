<?php
/**
 * Created by PhpStorm.
 * User: Aurelien Atemkeng
 * Date: 20/04/2019
 * Time: 11:56 AM
 */

namespace DClass\lib;

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
    public static function randomcode($length = 8)
    {
        $list = "0123456789abcdefghijklmnopqrstvwxyzABCDEFGHIJKLMNOPQRSTVWXYZ+-@%*$";
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

    public static function setcookie($key, $value)
    {
        setcookie($key, $value, time() + 365 * 24 * 3600, null, null, false, true); // On écrit un cookie
    }

    public static function clearcookie($key)
    {
        setcookie($key, null, -1, null, null, false, true);
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

    public static function arrayToPhpFile($array, $filename, $folder = 'cache/'){
        $phpContent = "<?php\n\nreturn " . var_export($array, true) . ";\n";
//        file_put_contents(ROOT . "cache/routes.json", json_encode(self::$routes));
//        if (!file_exists(ROOT . "cache/routes.php"))
        file_put_contents(ROOT . $folder."$filename.php", $phpContent);
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

    public static function getTimezone()
    {

        $adresseIP = $_SERVER['REMOTE_ADDR']; // Remplacez par l'adresse IP de l'utilisateur '104.26.3.192'; //
        $apiKey = "8f67b514f5b0437882f3dff272373e2d"; // Remplacez par votre clé d'API réelle
        // 8f67b514f5b0437882f3dff272373e2d

        $url = "https://api.ipgeolocation.io/timezone?apiKey=$apiKey&ip=$adresseIP";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        return $data['timezone'] ?? 'UTC';

    }
}
