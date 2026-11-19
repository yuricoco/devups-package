<?php

/**
 * ETAPE 5.2 (v8.0) : stubs PHPStan uniquement - jamais executes, jamais
 * autoloades (hors du classmap composer.json), seulement scannes par
 * phpstan (`scanFiles` dans phpstan.neon.dist) pour qu'il resolve les noms
 * courts d'entites que class_alias() rend utilisables a l'execution (voir
 * TODO.md "LOT 5.2" - class_alias() n'est pas visible par l'analyse
 * statique, qui sinon rapporte "Instantiated class X not found" / "Call to
 * static method on an unknown class X" pour chaque site qui reference
 * encore l'entite par son nom court). Une ligne par entite namespacee ;
 * ajouter la ligne correspondante ici au moment ou l'entite est migree.
 */

class Dvups_lang extends \devups\ModuleLang\Entity\Dvups_lang
{
}

class Dvups_action extends \devups\ModuleAdmin\Entity\Dvups_action
{
}

class Dvups_admin extends \devups\ModuleAdmin\Entity\Dvups_admin
{
}

class Dvups_privilege extends \devups\ModuleAdmin\Entity\Dvups_privilege
{
}

class Dvups_role extends \devups\ModuleAdmin\Entity\Dvups_role
{
}

class Configuration extends \devups\ModuleConfig\Entity\Configuration
{
}

class Dvups_entity extends \devups\ModuleConfig\Entity\Dvups_entity
{
}

class Dvups_entity_lang extends \devups\ModuleConfig\Entity\Dvups_entity_lang
{
}

class Dvups_log extends \devups\ModuleLog\Entity\Dvups_log
{
}

class Cmscomment extends \devupscms\ModuleCms\Entity\Cmscomment
{
}

class Cmscontent extends \devupscms\ModuleCms\Entity\Cmscontent
{
}

class Cmscontent_lang extends \devupscms\ModuleCms\Entity\Cmscontent_lang
{
}

class Cmsresource extends \devupscms\ModuleCms\Entity\Cmsresource
{
}

class Interval extends \devupscms\ModuleData\Entity\Interval
{
}

class Interval_group extends \devupscms\ModuleData\Entity\Interval_group
{
}

class Status extends \devupscms\ModuleData\Entity\Status
{
}

class Status_lang extends \devupscms\ModuleData\Entity\Status_lang
{
}

class Country extends \devupscms\ModuleLocation\Entity\Country
{
}

class Country_lang extends \devupscms\ModuleLocation\Entity\Country_lang
{
}

class Notification extends \devupscms\ModuleNotification\Entity\Notification
{
}

class Notificationtype extends \devupscms\ModuleNotification\Entity\Notificationtype
{
}

class Notificationtype_lang extends \devupscms\ModuleNotification\Entity\Notificationtype_lang
{
}

class Workers extends \devupscms\ModulePushNotification\Entity\Workers
{
}

class Push_subscription extends \devupscms\ModulePushNotification\Entity\Push_subscription
{
}

class Emaillog extends \devupscms\ModuleReporting\Entity\Emaillog
{
}

class Push_email extends \devupscms\ModuleReporting\Entity\Push_email
{
}

class Reportingmodel extends \devupscms\ModuleReporting\Entity\Reportingmodel
{
}

class Reportingmodel_lang extends \devupscms\ModuleReporting\Entity\Reportingmodel_lang
{
}

class Tree extends \devupscms\ModuleTree\Entity\Tree
{
}

class Tree_item extends \devupscms\ModuleTree\Entity\Tree_item
{
}

class Tree_item_image extends \devupscms\ModuleTree\Entity\Tree_item_image
{
}

class Tree_item_lang extends \devupscms\ModuleTree\Entity\Tree_item_lang
{
}

class Tree_lang extends \devupscms\ModuleTree\Entity\Tree_lang
{
}

class Oauth_client extends \devupsfront\ModuleUser\Entity\Oauth_client
{
}

class User extends \devupsfront\ModuleUser\Entity\User
{
}

/*
 * ETAPE 5.2 (v8.0) - point (b) : stubs pour les classes Controller/Datatable/
 * Form de chaque module (meme mecanisme que les stubs d'Entity ci-dessus).
 */

class Dvups_langController extends \devups\ModuleLang\Controller\Dvups_langController
{
}

class Dvups_langTable extends \devups\ModuleLang\Datatable\Dvups_langTable
{
}

class Dvups_langForm extends \devups\ModuleLang\Form\Dvups_langForm
{
}

class ConfigurationController extends \devups\ModuleConfig\Controller\ConfigurationController
{
}

class ConfigurationTable extends \devups\ModuleConfig\Datatable\ConfigurationTable
{
}

class ConfigurationForm extends \devups\ModuleConfig\Form\ConfigurationForm
{
}

class Dvups_logController extends \devups\ModuleLog\Controller\Dvups_logController
{
}

class Dvups_logTable extends \devups\ModuleLog\Datatable\Dvups_logTable
{
}

class Dvups_logForm extends \devups\ModuleLog\Form\Dvups_logForm
{
}

class Dvups_actionController extends \devups\ModuleAdmin\Controller\Dvups_actionController
{
}

class Dvups_adminController extends \devups\ModuleAdmin\Controller\Dvups_adminController
{
}

class Dvups_adminFrontController extends \devups\ModuleAdmin\Controller\Dvups_adminFrontController
{
}

class Dvups_privilegeController extends \devups\ModuleAdmin\Controller\Dvups_privilegeController
{
}

class Dvups_roleController extends \devups\ModuleAdmin\Controller\Dvups_roleController
{
}

class Dvups_actionTable extends \devups\ModuleAdmin\Datatable\Dvups_actionTable
{
}

class Dvups_adminTable extends \devups\ModuleAdmin\Datatable\Dvups_adminTable
{
}

class Dvups_privilegeTable extends \devups\ModuleAdmin\Datatable\Dvups_privilegeTable
{
}

class Dvups_roleTable extends \devups\ModuleAdmin\Datatable\Dvups_roleTable
{
}

class Dvups_actionForm extends \devups\ModuleAdmin\Form\Dvups_actionForm
{
}

class Dvups_adminForm extends \devups\ModuleAdmin\Form\Dvups_adminForm
{
}

class Dvups_privilegeForm extends \devups\ModuleAdmin\Form\Dvups_privilegeForm
{
}

class Dvups_roleForm extends \devups\ModuleAdmin\Form\Dvups_roleForm
{
}

class CmscommentController extends \devupscms\ModuleCms\Controller\CmscommentController
{
}

class CmscontentController extends \devupscms\ModuleCms\Controller\CmscontentController
{
}

class CmscontentFrontController extends \devupscms\ModuleCms\Controller\CmscontentFrontController
{
}

class CmsresourceController extends \devupscms\ModuleCms\Controller\CmsresourceController
{
}

class CmsresourceFrontController extends \devupscms\ModuleCms\Controller\CmsresourceFrontController
{
}

class CmscommentTable extends \devupscms\ModuleCms\Datatable\CmscommentTable
{
}

class CmscontentTable extends \devupscms\ModuleCms\Datatable\CmscontentTable
{
}

class CmsresourceTable extends \devupscms\ModuleCms\Datatable\CmsresourceTable
{
}

class CmscommentForm extends \devupscms\ModuleCms\Form\CmscommentForm
{
}

class CmscontentForm extends \devupscms\ModuleCms\Form\CmscontentForm
{
}

class CmsresourceForm extends \devupscms\ModuleCms\Form\CmsresourceForm
{
}

class IntervalController extends \devupscms\ModuleData\Controller\IntervalController
{
}

class StatusController extends \devupscms\ModuleData\Controller\StatusController
{
}

class IntervalTable extends \devupscms\ModuleData\Datatable\IntervalTable
{
}

class StatusTable extends \devupscms\ModuleData\Datatable\StatusTable
{
}

class IntervalForm extends \devupscms\ModuleData\Form\IntervalForm
{
}

class StatusForm extends \devupscms\ModuleData\Form\StatusForm
{
}

class CountryController extends \devupscms\ModuleLocation\Controller\CountryController
{
}

class CountryFrontController extends \devupscms\ModuleLocation\Controller\CountryFrontController
{
}

class CountryTable extends \devupscms\ModuleLocation\Datatable\CountryTable
{
}

class CountryForm extends \devupscms\ModuleLocation\Form\CountryForm
{
}
