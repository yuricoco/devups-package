<?php 
        // user \dclass\devups\model\Model;
    /**
     * @Entity @Table(name="dvups_log")
     * */
    class Dvups_log extends Model implements JsonSerializable{

        /**
         * @Id @GeneratedValue @Column(type="integer")
         * @var int
         * */
        protected $id;
        /**
         * @Column(name="object", type="string" , length=255 )
         * @var string
         **/
        protected $object;
        /**
         * @Column(name="log", type="text"  , nullable=true)
         * @var text
         **/
        protected $log; 
        

        
        public function __construct($id = null){
            
            if( $id ) { $this->id = $id; }   
            
}

        public function getId() {
            return $this->id;
        }
        
        // getter and setter
        
        public function jsonSerialize() {
                return [
                    'id' => $this->id,
                    'object' => $this->object,
                    'log' => $this->log,
                ];
        }
        
}
