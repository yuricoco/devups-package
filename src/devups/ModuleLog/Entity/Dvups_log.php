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
         * @Column(name="fingerprint", type="string" , length=64 )
         * @var string
         **/
        protected $fingerprint;
        /**
         * @Column(name="level", type="string" , length=20 )
         * @var string
         **/
        protected $level;
        /**
         * @Column(name="entity_class", type="string" , length=255 , nullable=true)
         * @var string
         **/
        protected $entity_class;
        /**
         * @Column(name="file", type="string" , length=500 , nullable=true)
         * @var string
         **/
        protected $file;
        /**
         * @Column(name="line", type="integer"  , nullable=true)
         * @var integer
         **/
        protected $line;
        /**
         * @Column(name="message", type="string" , length=1000 , nullable=true)
         * @var string
         **/
        protected $message;
        /**
         * @Column(name="occurrences", type="integer"  )
         * @var integer
         **/
        protected $occurrences;
        /**
         * @Column(name="first_at", type="datetime"  , nullable=true)
         * @var datetime
         **/
        protected $first_at;
        /**
         * @Column(name="last_at", type="datetime"  , nullable=true)
         * @var datetime
         **/
        protected $last_at;
        /**
         * @Column(name="context", type="text"  , nullable=true)
         * @var text
         **/
        protected $context;
        /**
         * @Column(name="log_file", type="string" , length=500 , nullable=true)
         * @var string
         **/
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
