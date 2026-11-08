<?php

namespace route;
use AdminTemplateGenerator;
use Dvups_entityController;
use Genesis;
use Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   title="Devups API",
 *   version="1.0.0",
 *   description="Documentation des WebServices de Devups."
 * )
 *
 * @OA\Server(
 *   url="https://monsite.com/api",
 *   description="Serveur principal"
 * )
 */
class Admin extends \dclass\devups\Controller\ModuleController
{

    public function __construct($route)
    {
        parent::__construct($route);
    }

    public function dashboardView()
    {
        Genesis::render("dashboard", AdminTemplateGenerator::dashboardView());
    }
    public function configView()
    {
        Genesis::render("config", []);
    }

    /**
     * @MethodView(GET="hello", Auth=1)
     */
    public function helloService()
    {
        Genesis::json_encode(["success" => true, "message" => "hello devups you made your first apicall"]);
    }

    public function ExportView()
    {
        $classname = ucfirst(Request::get("classname"));
        $entity = new $classname;
        $result = $entity->exportCsv($classname); //,
        $message = $classname . ": CSV generated with success";
//            Genesis::json_encode([
//                "message"=>$message
//            ]);
        Genesis::json_encode(compact("message", "entity", "result"));
    }

    public function importService()
    {
        $classname = ucfirst(Request::get("classname"));
        $entity = new $classname;
        $response = $entity->importCsv($classname); //,
        $message = $classname . ": Core generated with success";
        Genesis::json_encode(compact("response", "message"));
    }

    public function export()
    {
        (new Dvups_entityController())->exportCsv();
    }

    /**
     * @MethodView(path="dvups-admin/complete-registration")
     */
    public function completeRegistrationView($id)
    {
        \devups\ModuleAdmin\ModuleAdmin::renderView('admin.dvups_admin.complete_registration', (new \Dvups_adminController())->completeRegistration($id));
    }

    public function connexionView($id)
    {
        \devups\ModuleAdmin\ModuleAdmin::renderView('admin.dvups_admin.complete_registration', (new \Dvups_adminController())->completeRegistration($id));
    }

    /**
     * ETAPE N.3 : Swagger UI back-office, protege par can() (meme garde
     * que l'ecran Dvups_log, lecture seule) - inclut les routes front
     * (JWT) ET admin (session), contrairement a documentation/api.php
     * (routes front uniquement, servi sans authentification). Aucune
     * action "Documentation" n'existe dans dvups_action : seul le role
     * "admin" (bypass maitre de can()) y a acces tant qu'un droit dedie
     * n'est pas cree via `php devups rights:sync` + Dvups_privilege.
     */
    public function apidocsView()
    {
        if (!can('read', 'Documentation')) {
            http_response_code(403);
            echo t('Action non autorisee');
            die;
        }

        require_once ROOT . 'dclass/generator/OpenApiGenerator.php';
        $spec = dv_build_openapi_spec(ROOT, true);
        $specJson = json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . PROJECT_NAME . ' – API (back-office)</title>'
            . '<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">'
            . '<style>.swagger-ui .topbar{display:none}body{margin:0}</style>'
            . '</head><body><div id="swagger-ui"></div>'
            . '<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>'
            . '<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>'
            . '<script>window.onload=()=>{SwaggerUIBundle({spec:' . $specJson . ',dom_id:"#swagger-ui",'
            . 'deepLinking:true,displayRequestDuration:true,defaultModelsExpandDepth:-1,docExpansion:"none",'
            . 'filter:true,presets:[SwaggerUIBundle.presets.apis,SwaggerUIStandalonePreset],'
            . 'plugins:[SwaggerUIBundle.plugins.DownloadUrl],layout:"StandaloneLayout"});};</script>'
            . '</body></html>';
    }

    public function web($path = "")
    {
        // TODO: Implement web() method.
    }

    public function services($path = "")
    {
        // TODO: Implement services() method.
    }

    public function webservices($path = "")
    {
        // TODO: Implement webservices() method.
    }
}