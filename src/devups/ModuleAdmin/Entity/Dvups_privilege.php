<?php

namespace devups\ModuleAdmin\Entity;

use Doctrine\ORM\Mapping as ORM;

        // user \dclass\devups\model\Model;
#[ORM\Entity]
    #[ORM\Table(name: "dvups_privilege")]
    class Dvups_privilege extends \Model implements \JsonSerializable{

        /**
         * @var int
         */
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column(type: "integer")]
        protected $id; 
        
        /**
         * @var \Dvups_role
         */
        #[ORM\ManyToOne(targetEntity: "\Dvups_role")]
        #[ORM\JoinColumn(onDelete: "cascade")]
        public $dvups_role;

        /**
         * @var \Dvups_action
         */
        #[ORM\ManyToOne(targetEntity: "\Dvups_action")]
        #[ORM\JoinColumn(onDelete: "cascade")]
        public $dvups_action;


        
        public function __construct($id = null){
            
            if( $id ) { $this->id = $id; }   
            
	$this->dvups_role = new Dvups_role();
	$this->dvups_action = new Dvups_action();
}

        public function getId() {
            return $this->id;
        }
        
        // getter and setter
        
        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
                return [
                    ... $this->dvserialised,
                ];
        }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Dvups_privilege::class, 'Dvups_privilege');
