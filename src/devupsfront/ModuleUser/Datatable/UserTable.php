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
        $this->order_by = " this.id desc ";
        $this->qbcustom->with('country', ['name']);
        $this->datatablemodel = [
            'id' => ['header' => t('#'),],
            'src_profile' => ['header' => t('Profile'), 'value' => 'src:profile'],
            //            'firstname' => ['header' => t('Firstname'),],
            'is_activated' => ['header' => t('Activated'),],
            'username' => ['header' => t('Username'),],
            'email' => ['header' => t('Email'),],
            'country.name' => ['header' => t('Pays'),],
            'phonenumber' => ['header' => t('Phonenumber'),],
            //'lang' => ['header' => t('Lang'),],
            'created_at' => ['header' => t('Cree le'),],
            'updated_at' => ['header' => t('Last Update'), 'order' => true],
        ];

        $this->addcustomaction(function ($item) {
            return \dclass\devups\model\Dbutton::link(
                'Detail',
                User::classview('user/detail?id=' . $item->id),
                'btn btn-info'
            )
                ->render();
        });

        return $this;
    }
    public function builddashboardtable()
    {

        $this->enabletopaction = false;
        $this->groupaction = false;
        $this->enablefilter(false);
        $this->base_url = __env . "admin/";
        $this->order_by = " this.id desc ";
        $this->datatablemodel = [
            'id' => ['header' => t('#'),],
            'username' => ['header' => t('Username'),],
            'email' => ['header' => t('Email'),],
            'created_at' => ['header' => t('Cree le'), 'order' => true,],
        ];

        $this->disableDefaultaction();
        $this->actionDropdown = false;
        $this->addcustomaction(function ($item) {
            return \dclass\devups\model\Dbutton::link(
                'Detail',
                User::classview('user/detail?id=' . $item->id),
                'btn btn-info'
            )
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
            ['label' => t('resettingpassword'), 'value' => 'resettingpassword'],
            ['label' => t('is_activated'), 'value' => 'is_activated'],
            ['label' => t('birthdate'), 'value' => 'birthdate'],
            ['label' => t('lang'), 'value' => 'lang'],
            ['label' => t('username'), 'value' => 'username'],
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
