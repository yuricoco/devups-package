<?php
        // user \dclass\devups\model\Model;
    /**
     * @Entity @Table(name="dvups_privilege")
     * */
    class Dvups_privilege extends Model implements JsonSerializable{

        /**
         * @Id @GeneratedValue @Column(type="integer")
         * @var int
         * */
        protected $id; 
        
        /**
         * @ManyToOne(targetEntity="\Dvups_role")
         * @JoinColumn(onDelete="cascade")
         * @var \Dvups_role
         */
        public $dvups_role;

        /**
         * @ManyToOne(targetEntity="\Dvups_action")
         * @JoinColumn(onDelete="cascade")
         * @var \Dvups_action
         */
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
