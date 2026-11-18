<?php

namespace devups\ModuleLog\Entity;

use Doctrine\ORM\Mapping as ORM;

        // user \dclass\devups\model\Model;
#[ORM\Entity]
    #[ORM\Table(name: "dvups_log")]
    class Dvups_log extends \Model implements \JsonSerializable{

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
        #[ORM\Column(name: "fingerprint", type: "string", length: 64)]
        protected $fingerprint;
        /**
         * @var string
         */
        #[ORM\Column(name: "level", type: "string", length: 20)]
        protected $level;
        /**
         * @var string
         */
        #[ORM\Column(name: "entity_class", type: "string", length: 255, nullable: true)]
        protected $entity_class;
        /**
         * @var string
         */
        #[ORM\Column(name: "file", type: "string", length: 500, nullable: true)]
        protected $file;
        /**
         * @var integer
         */
        #[ORM\Column(name: "line", type: "integer", nullable: true)]
        protected $line;
        /**
         * @var string
         */
        #[ORM\Column(name: "message", type: "string", length: 1000, nullable: true)]
        protected $message;
        /**
         * @var integer
         */
        #[ORM\Column(name: "occurrences", type: "integer")]
        protected $occurrences;
        /**
         * @var datetime
         */
        #[ORM\Column(name: "first_at", type: "datetime", nullable: true)]
        protected $first_at;
        /**
         * @var datetime
         */
        #[ORM\Column(name: "last_at", type: "datetime", nullable: true)]
        protected $last_at;
        /**
         * @var text
         */
        #[ORM\Column(name: "context", type: "text", nullable: true)]
        protected $context;
        /**
         * @var string
         */
        #[ORM\Column(name: "log_file", type: "string", length: 500, nullable: true)]
        protected $log_file; 
        

        
        public function __construct($id = null){
            
            if( $id ) { $this->id = $id; }   
            
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
class_alias(Dvups_log::class, 'Dvups_log');
