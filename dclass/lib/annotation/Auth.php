<?php

//namespace dclass\lib\annotation;
//use Annotation;
use Firebase\JWT\JWT;

class Auth extends Annotation
{

    public $authorized;
    public $userId;
    public $user;
    public static $user_id;
    public static $systemic = false;
    public static $restrictions = [];

    /**
     * add entity that are restricted by authentification.
     *
     * @param $entity the class name  of the entity submitted at the auth restriction
     * @param $methodException the methods of the entityFrontConttroller excepted front the restriction
     * @return void
     */
    public static function addRestriction($entity, $methodException = [])
    {
        self::$restrictions[$entity] = $methodException;
    }

    public function authorize(&$router = null)
    {

        if (!isset($_SERVER['HTTP_AUTHORIZATION']) && !isset($_SERVER['HTTP_AUTH'])) {
            header('HTTP/1.0 400 Bad Request AUTHORIZATION not found');
            return ([
                'success' => false,
                'detail' => "Bad Request AUTHORIZATION or AUTH not found",
            ]);
        }
        return  $this->execute($router);

    }


    public function execute(&$router = null)
    {


        /*self::$user_id = Request::get('user_id');
        self::$group = Request::get('group');
        self::$group_id = Tree_item::getbyattribut('this.slug', self::$group)->id;

        $jwt = new stdClass;
        $jwt->userId = Request::get('user_id');
        if ($router)
            $router->jwt = $jwt;

        return $jwt;
        */

        if (!isset($_SERVER['HTTP_AUTHORIZATION']) && !isset($_SERVER['HTTP_AUTH'])) {
//            header('HTTP/1.0 400 Bad Request AUTHORIZATION not found');
            return ([
                'success' => false,
                'detail' => "Bad Request AUTHORIZATION or AUTH not found",
            ]);
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION']) && !preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            header('HTTP/1.0 400 Bad Request');
            return ([
                'success' => false,
                'detail' => "Token not found in request",
            ]);
            //exit;
        } else if (isset($_SERVER['HTTP_AUTH']) && !preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTH'], $matches)) {
            header('HTTP/1.0 400 Bad Request');
            return ([
                'success' => false,
                'detail' => "Token not found in request",
            ]);
            //exit;
        }
        $jwt = $matches[1];
        if (!$jwt) {
            // No token was able to be extracted from the authorization header
            header('HTTP/1.0 400 Bad Request');
            return ([
                'success' => false,
                'detail' => "No token was able to be extracted from the authorization header",
            ]);
        }
        $headers = new stdClass();
        try {
            $token = JWT::decode($jwt, new \Firebase\JWT\Key(jwt_secret_Key, 'HS512'), $headers);
        } catch (Exception $e) {
            header('HTTP/1.1 401 Unauthorized');
            //echo $e->getMessage();
            return ([
                'success' => false,
                'detail' => $e->getMessage(),
            ]);
        }

        $serverName = __server;

        if (jwt_expire_time) {
            $now = new DateTimeImmutable();
            if ($token->iss !== $serverName ||
                $token->nbf > $now->getTimestamp() ||
                $token->exp < $now->getTimestamp()) {
                header('HTTP/1.1 401 Unauthorized');
                return ([
                    'success' => false,
                    'detail' => "Token expired",
                ]);
            }
        }

        if ($router)
            $router->jwt = $token;

        self::$user_id = $token->userId;

        return $token;

    }

    public static function getJWT(\User $user){
        $domainName = __server;

        if (jwt_expire_time) {
            $date = new DateTimeImmutable();
            $expire_at = $date->modify(jwt_expire_time)->getTimestamp();      // Add 60 seconds
            // Retrieved from filtered POST data
            $request_data = [
                'iat' => $date->getTimestamp(),         // Issued at: time when the token was generated
                'iss' => $domainName,                       // Issuer
                'nbf' => $date->getTimestamp(),         // Not before
                'exp' => $expire_at,                           // Expire
                'userId' => $user->getId(),                     // User name
            ];
        } else                                    // Retrieved from filtered POST data
            $request_data = [
//                'iat' => null,         // Issued at: time when the token was generated
                'iss' => $domainName,                       // Issuer
//                'nbf' => null,         // Not before
//                'exp' => null,                           // Expire
                'userId' => $user->getId(),                     // User name
            ];

        return JWT::encode(
            $request_data,
            jwt_secret_Key,
            'HS512'
        );

    }

}