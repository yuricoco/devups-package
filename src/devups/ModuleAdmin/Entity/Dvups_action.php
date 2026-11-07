<?php
        // user \dclass\devups\model\Model;
    /**
     * @Entity @Table(name="dvups_action")
     * */
    class Dvups_action extends Model implements JsonSerializable{

        /**
         * @Id @GeneratedValue @Column(type="integer")
         * @var int
         * */
        protected $id;
        /**
         * @Column(name="entity", type="string" , length=100 )
         * @var string
         **/
        protected $entity;
        /**
         * @Column(name="action", type="string" , length=50 )
         * @var string
         **/
        protected $action;
        /**
         * @Column(name="label", type="string" , length=150 )
         * @var string
         **/
        protected $label; 
        

        
        public function __construct($id = null){
            
            if( $id ) { $this->id = $id; }   
            
}

        public function getId() {
            return $this->id;
        }

        /**
         * Actions implicites de toute entite dotee d'un CRUD genere
         * (ControllerCore/CrudTrait). Une entite peut en declarer d'autres,
         * propres a son metier, dans son datacore (Core/<Entite>Core.json,
         * cle "actions" : liste de codes, ou de {"code":..,"label":..}).
         */
        const DEFAULT_ACTIONS = ['create', 'read', 'update', 'delete', 'export', 'import'];

        /**
         * Parcourt config/dvups_configurations.php (catalogue des entites
         * du projet) et le datacore de chacune pour completer dvups_action
         * des actions manquantes : les 6 actions implicites, plus celles
         * declarees par l'entite elle-meme. N'efface ni ne modifie jamais
         * une ligne existante (une action retiree du datacore reste en
         * base, avec ses eventuels privileges attribues : a l'administrateur
         * de la retirer explicitement si elle n'a plus lieu d'etre).
         *
         * @return array{created:int,total:int} nombre de lignes creees et
         *         nombre total d'entites/actions declarees.
         */
        public static function sync(): array
        {
            $configurations = require ROOT . 'config/dvups_configurations.php';
            $created = 0;
            $total = 0;

            foreach ($configurations as $key => $entityConfig) {
                // le catalogue repete chaque entite sous sa cle courte et
                // sous sa cle namespacee complete (Core::updateDvupsTable) :
                // ne la traiter qu'une fois, via la cle courte.
                if (strpos($key, '\\') !== false || !is_array($entityConfig) || empty($entityConfig['component']))
                    continue;

                $entityName = $entityConfig['name'];
                $corePath = ROOT . 'src/' . $entityConfig['path'] . 'Core/' . $entityName . 'Core.json';
                $declared = [];
                if (file_exists($corePath)) {
                    $core = json_decode(file_get_contents($corePath), true);
                    $declared = is_array($core['actions'] ?? null) ? $core['actions'] : [];
                }

                foreach (array_merge(self::DEFAULT_ACTIONS, $declared) as $action) {
                    $code = is_array($action) ? ($action['code'] ?? null) : $action;
                    if (!$code)
                        continue;
                    $label = is_array($action) ? ($action['label'] ?? ucfirst($code)) : ucfirst($code);
                    $total++;

                    $existing = self::where('entity', $entityName)->andwhere('action', $code)->firstOrNull();
                    if ($existing && $existing->getId())
                        continue;

                    self::create(['entity' => $entityName, 'action' => $code, 'label' => $label]);
                    $created++;
                }
            }

            return ['created' => $created, 'total' => $total];
        }

        // getter and setter

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
                return [
                    ... $this->dvserialised,
                ];
        }
        
}
