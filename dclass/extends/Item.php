<?php


class Item extends Model implements JsonSerializable
{

    // use ModelTrait;

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="quantity", type="integer"  )
     * @var integer
     **/
    protected $quantity = 1;
    /**
     * @Column(name="unit", type="integer", nullable=true  )
     * @var integer
     **/
    protected $unit = 1;
    /**
     * for product billing by weight
     *
     * @Column(name="weight", type="integer", nullable=true  )
     * @var integer
     **/
    protected $weight;
    /**
     * @Column(name="price", type="integer"  )
     * @var integer
     **/
    protected $price;
    /**
     * @Column(name="totalprice", type="float", nullable=true  )
     * @var integer
     **/
    protected $totalprice;

    /**
     * Accompagnement pour les produits qui sont dans une categorie qui necessite un accompagnement.
     *
     * @Column(name="support", type="text" , nullable=true )
     * @var string
     **/
    protected $support;
    /**
     * Accompagnement pour les produits qui sont dans une categorie qui necessite un accompagnement.
     *
     * @Column(name="description", type="string", length=125 , nullable=true )
     * @var string
     **/
    protected $description;
    /**
     * @Column(name="comment", type="text", nullable=true )
     * @var string
     **/
    protected $comment;

    /**
     * @Column(name="status", type="string", length=25, nullable=true  )
     * @var string
     **/
    protected $status;

    /**
     * @Column(name="priceofcustomer", type="integer"  )
     * @var integer
     **/
    protected $priceofcustomer;
    /**
     * @Column(name="discountprice", type="integer", nullable=true  )
     * @var integer
     **/
    protected $discountprice;
    /**
     * define if the product will be visible by the service for order
     * @Column(name="quantityrule", type="float" , nullable=true )
     * @var string
     **/
    protected $quantityrule = 1;

    /**
     * @ManyToOne(targetEntity="\Product")
     * @var \Product
     */
    public $product;

    public function getPriceofcustomer()
    {
        return $this->priceofcustomer;
    }

    public function setPriceofcustomer($priceofcustomer)
    {
        $this->priceofcustomer = $priceofcustomer;
    }

    public function getDiscountprice()
    {
        return $this->discountprice;
    }

    public function setDiscountprice($discountprice)
    {
        $this->discountprice = $discountprice;
    }

    /**
     * @return int
     */
    public function getTotalprice()
    {
        return $this->totalprice;
    }

    /**
     * @param int $totalprice
     */
    public function setTotalprice($totalprice)
    {
        $this->totalprice = $totalprice;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
    /**
     * @return string
     */
    public function period()
    {
        return json_decode($this->comment);
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return int
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param int $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    function getProductname()
    {
        return $this->product->getName();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description ?? $this->getProductname();
    }

    /**
     * @param string $description
     */
    public function setDescription( $description)
    {
        $this->description = $description;
    }

    public function setPrice($price){
        $this->price = $price;
    }

    public function srcImage($size = "50_"){
        if($this->product_image->getId())
            return $this->product_image->srcImage($size);

        return $this->product->getCoverimage($size);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getPricefront()
    {
        return $this->price;
        // return m($this->price, $this->procurrency);
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * we convert the current cart currenty to the one passed in parameter
     * @param $currency
     */
    public function convert(\Currency $currency, $cart)
    {
        //var_dump($this->amount, $cart->currency);
        // $currency = getcurrency();
        $amount = $currency->convert($this->amount, $cart->currency);
        // $amount = $currency->convert($this->priceofcustomer, $this->cart->currency);
        $this->__update([
                "this.amount"=>$amount,
                "this.price"=> $currency->convert($this->price, $cart->currency),
                "this.priceofcustomer"=> $currency->convert($this->priceofcustomer, $cart->currency),
                ])->exec();

        //var_dump($amount, $currency->getName());

    }
    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'quantity' => (float) $this->quantity,
            'quantityrule' => (float) $this->quantityrule,
            'price' => (float) $this->price,
            'weight' => $this->weight,
            'unit' => (int) $this->unit,
            'status' => $this->status,
        ];
    }
}