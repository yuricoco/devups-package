<?php


trait CurrencyTrait
{
    /**
     * @Column(name="currency", type="string"  )
     * @var string
     **/
    protected $currency = 'XAF';
    /**
     * @Column(name="amount", type="float", nullable=true  )
     * @var float
     **/
    protected $amount = 0;
    
}