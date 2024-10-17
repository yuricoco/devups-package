<?php


use dclass\devups\Datatable\Datatable as Datatable;

class UserTable extends Datatable
{


    public function __construct($user = null, $datatablemodel = [])
    {
        parent::__construct($user, $datatablemodel);
    }

    public static function init(\User $user = null)
    {

        $dt = new UserTable($user);
        $dt->entity = $user;

        return $dt;
    }

    public function buildindextable()
    {

        $this->base_url = __env . "admin/";
        $this->datatablemodel = [
            'id' => ['header' => t('#'),],
            'src_profile' => ['header' => t('Profile'),'value'=>'src:profile'],
            'profile' => ['header' => t('Profile'),],
            'firstname' => ['header' => t('Firstname'),],
            'lastname' => ['header' => t('Lastname'),],
            'email' => ['header' => t('Email'),],
            'phonenumber' => ['header' => t('Phonenumber'),],
            //'lang' => ['header' => t('Lang'),],
            'username' => ['header' => t('Username'),],
        ];

        $this->addcustomaction(function ($item){
           return \dclass\devups\model\Dbutton::link('Detail',
               User::classview('user/detail?id='.$item->id), 'btn btn-info')
               ->render();
        });

        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('firstname'), 'value' => 'firstname'],
            ['label' => t('lastname'), 'value' => 'lastname'],
            ['label' => t('email'), 'value' => 'email'],
            ['label' => t('sexe'), 'value' => 'sexe'],
            ['label' => t('phonenumber'), 'value' => 'phonenumber'],
            ['label' => t('password'), 'value' => 'password'],
            ['label' => t('resettingpassword'), 'value' => 'resettingpassword'],
            ['label' => t('is_activated'), 'value' => 'is_activated'],
            ['label' => t('activationcode'), 'value' => 'activationcode'],
            ['label' => t('birthdate'), 'value' => 'birthdate'],
            ['label' => t('creationdate'), 'value' => 'creationdate'],
            ['label' => t('lang'), 'value' => 'lang'],
            ['label' => t('username'), 'value' => 'username'],
            ['label' => t('has_shop'), 'value' => 'has_shop']
        ];
        // TODO: overwrite datatable attribute for this view
        return $this;
    }

    public function router()
    {
        $tablemodel = Request::get("tablemodel", null);
        if ($tablemodel && method_exists($this, "build" . $tablemodel . "table") && $result = call_user_func(array($this, "build" . $tablemodel . "table"))) {
            return $result;
        } else
            switch ($tablemodel) {
                // case "": return this->
                default:
                    return $this->buildindextable();
            }

    }

}
