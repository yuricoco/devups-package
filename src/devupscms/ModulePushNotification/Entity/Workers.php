<?php 
        // user \dclass\devups\model\Model;
    /**
     * @Entity @Table(name="workers")
     * */
    class Workers extends Model implements JsonSerializable{

        /**
         * @Id @GeneratedValue @Column(type="integer")
         * @var int
         * */
        protected $id;
        /**
         * @Column(name="queue", type="string" , length=25 )
         * @var string
         **/
        public $queue;
        /**
         * @Column(name="payload", type="text"  )
         * @var integer
         **/
        protected $payload;
        /**
         * @Column(name="type", type="string" , length=55 )
         * @var string
         **/
        protected $type;
        /**
         * @Column(name="callback", type="string" , length=255, nullable=true )
         * @var string
         **/
        protected $callback;
        /**
         * @Column(name="log", type="string" , length=255, nullable=true )
         * @var string
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
                    'queue' => $this->queue,
                    'payload' => $this->payload,
                    'type' => $this->type,
                    'callback' => $this->callback,
                    'log' => $this->log,
                ];
        }
        
}
