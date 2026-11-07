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
        
        // getter and setter
        
        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
                return [
                    ... $this->dvserialised,
                ];
        }
        
}
