<?php

namespace devups\ModuleAdmin\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "dvups_role")]
class Dvups_role extends \Model implements \JsonSerializable
{

    static $SELLER = "seller";

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected $id;
    /**
     * @var string
     */
    #[ORM\Column(name: "name", type: "string", length: 255)]
    protected $name;
    /**
     * @var string
     */
    #[ORM\Column(name: "alias", type: "string", length: 255)]
    protected $alias;
    /**
     * @var string
     */
    #[ORM\Column(name: "configs", type: "json", nullable: true)]
    public $configs;
    /**
     * @var string
     */
    #[ORM\Column(name: "components", type: "text", nullable: true)]
    public $components;
    /**
     * @var string
     */
    #[ORM\Column(name: "modules", type: "text", nullable: true)]
    public $modules;
    /**
     * @var string
     */
    #[ORM\Column(name: "entities", type: "text", nullable: true)]
    public $entities;

    public function __construct($id = null)
    {

        if ($id)
            $this->id = $id;

    }

    public function getId()
    {
        return $this->id;
    }
    public function getName()
    {
        return $this->name;
    }

    /**
     * ETAPE P.2 : manquait - $alias est protected sans accesseur, alors
     * que Dvups_roleTable::builddetailtable() en a besoin (appel
     * 'get' . ucfirst('alias') construit dynamiquement par
     * Datatable::getTableEntityBody()).
     */
    public function getAlias()
    {
        return $this->alias;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    function setModules($dvups_module)
    {
        $this->modules = implode(",", $dvups_module);;
    }

    function setComponents($dvups_module)
    {
        $this->components = implode(",", $dvups_module);
    }

    function setEntities($dvups_entity)
    {
        $this->entities = implode(",", $dvups_entity);
    }

    function getAttribute($attrib)
    {
        return explode(",", $this->{$attrib});
    }

    function updateConfigs()
    {
        $comps = $this->getAttribute('components');
        $mods = $this->getAttribute('modules');
        $ents = $this->getAttribute('entities');
        $globalconfig = require ROOT.'config/dvups_configurations.php';
//        dv_dump($globalconfig);
        $global_navigation = \Core::buildOriginCore(function ($core, $type) use ($globalconfig, $comps, $mods, $ents){
            if ($type == 'component'){
                return in_array($core, $comps);
            }elseif ($type == 'module'){
                return in_array($core, $mods);
            }elseif ($type == 'entity'){
//                dv_dump($core, $ents, in_array($core, $ents));
                if(in_array(strtolower($core), $ents))
                    return $globalconfig[''.ucfirst($core)];
            }else
                return false;
        });

//        dv_dump($global_navigation);
        $this->configs = json_encode($global_navigation);
        return $global_navigation;

    }

    function getConfigs(){
        return json_decode($this->configs, true) ?? [];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'alias' => $this->alias,
        ];
    }

    public function is($role)
    {
        if ($this->name == $role)
            return true;

        return false;
    }

    public function isIn($roles)
    {
        if (in_array($this->name, $roles))
            return true;

        return false;
    }

    public static function updateprivilegeAction()
    {

        $admin = getadmin();
        if ($admin->dvups_role->hydrate()->is("admin"))
            return '<button onclick="model.updateprivilege(this)"  class=\'btn btn-info\'> ' . t("Update Privilege") . ' </button>';

    }

    /**
     * Droits d'un role, compiles une seule fois en cache/rights/<id>.php :
     * ['EntiteExacte' => ['action' => true, ...], ...] (entity et action
     * tels que declares dans dvups_action, par Dvups_action::sync()).
     * clearRightsCache() invalide ce fichier, a appeler apres toute
     * modification des privileges du role (voir Dvups_privilegeController).
     */
    public static function compiledRights(int $roleId): array
    {
        $path = self::rightsCachePath($roleId);
        if (file_exists($path))
            return require $path;

        $rights = self::buildRights($roleId);

        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0777, true);
        file_put_contents($path, "<?php\nreturn " . var_export($rights, true) . ";\n");

        return $rights;
    }

    public static function clearRightsCache(int $roleId = null): void
    {
        if ($roleId !== null) {
            @unlink(self::rightsCachePath($roleId));
            return;
        }
        foreach (glob(ROOT . 'cache/rights/*.php') ?: [] as $file)
            @unlink($file);
    }

    private static function rightsCachePath(int $roleId): string
    {
        return ROOT . 'cache/rights/' . $roleId . '.php';
    }

    private static function buildRights(int $roleId): array
    {
        $rows = \Model::pdo()->prepare(
            'SELECT a.entity, a.action FROM dvups_privilege p
             INNER JOIN dvups_action a ON a.id = p.dvups_action_id
             WHERE p.dvups_role_id = :role_id'
        );
        $rows->execute(['role_id' => $roleId]);

        $rights = [];
        foreach ($rows->fetchAll(\PDO::FETCH_ASSOC) as $row)
            $rights[$row['entity']][$row['action']] = true;

        return $rights;
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Dvups_role::class, 'Dvups_role');
