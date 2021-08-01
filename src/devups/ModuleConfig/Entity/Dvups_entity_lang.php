<?php 
        // user \dclass\devups\model\Model;
    /**
     * @Entity @Table(name="dvups_entity_lang")
     * */
    class Dvups_entity_lang extends Dv_langCore {

        /**
         * @Id @GeneratedValue @Column(type="integer")
         * @var int
         * */
        protected $id;
            /**
             * @Column(name="label", type="string" , length=100 )
             * @var string
             **/
            protected $label;
            /**
             * @Column(name="dvups_entity_id", type="integer" )
             * @var integer
             **/
            protected $dvups_entity_id; 
        

        
        public function __construct($id = null){
            
                if( $id ) { $this->id = $id; }   
        }
      
        public function getId() {
            return $this->id;
        }
        public function getLabel() {
            return $this->label;
        }

        public function setLabel($label) {
            $this->label = $label;
        }
          
}