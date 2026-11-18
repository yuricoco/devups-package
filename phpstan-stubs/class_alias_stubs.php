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
