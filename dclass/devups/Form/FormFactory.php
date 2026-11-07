<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FormFactory
 *
 * @author azankang
 */
class FormFactory
{

    //put your code here
    protected static $fieldname;
    protected static $fieldid;
    protected static $class;
    public static $langs;

    public static function __inputradio($entitycore, $field, $directive = "")
    {

        $radio = '';
        $key = $field['option'];
        //foreach ($field['options'] as $key => $value) {
        if ('' . $field['value'] == '' . $key)
            $radio .= '<input class="" name="' . FormFactory::$fieldname . '" type="radio" value="' . $key . '" checked />';
        else
            $radio .= '<input class="" name="' . FormFactory::$fieldname . '" type="radio" value="' . $key . '" />';
        //}

        return $radio;
    }

    public static function __radio($entitycore, $field, $directive = "")
    {

        $radio = '';

        foreach ($field['options'] as $key => $value) {
            if ('' . $field['value'] == '' . $key)
                $radio .= '<label  ' . $directive . ' ><input class="" name="' . FormFactory::$fieldname . '" type="radio" value="' . $key . '" checked />' . $value . '</label>';
            else
                $radio .= '<label ' . $directive . ' ><input class="" name="' . FormFactory::$fieldname . '" type="radio" value="' . $key . '" />' . $value . '</label>';
        }

        return $radio;
    }

    public static function __checkboxinput($field, $directive = "", $callback = null)
    {

//        $checkbox['checkecd'] = [];
//        $checkbox['uncheckecd'] = [];

//        if ($field['values']) {
//            foreach ($field['values'] as $key => $value) {
//                $input = '<input class="" name="' . FormFactory::$fieldname . '[]" type="checkbox" value="' . $value->getId() . '" checked />';
//
//                $checkbox['checkecd'][] = ["checkbox" => $input, "entity" => $value];
//            }
//        }

        if ($field['option']) {
            //foreach ($field['options'] as $key => $value) {

            $ckecked = (in_array($field['option'], $field['values'])) ? "checked" : "";
            return '<input name="' . FormFactory::$fieldname . '[]" type="checkbox" value="' . $field['option'] . '"  ' . $ckecked . ' ' . $directive . ' />';

//                $checkbox['uncheckecd'][] = ["checkbox" => $input, "entity" => $value];
            //}
        }

//        call_user_func($callback, $checkbox);
    }

    public static function __checkbox($entitycore, $field, $directive = "")
    {

        $checkbox = '';

        if (isset($field['collectionforminjection'])) {

            $diff = $field['options']['list'];
            $fieldname = FormFactory::$fieldname;
            $fieldid = FormFactory::$fieldid;
            if (isset($field['values']['forcollectionform'])) {
//                die(var_dump($field['options']["forcollectionform"]));
                foreach ($field['values']['forcollectionform'] as $key => $value) {
//                    $collectionform = $field['collectionforminjection']::__renderForm($field['values']['forcollectionform'][$key]);
//                    $bc = new Blockcontentmodel();
//                    $bc->contentmodel = $value;
                    $collectionform = BlockcontentmodelForm::__renderForm($value);
                    $checkbox .= '<div class="checkbox dv-checkbox" >'
                        . '<label><input class="" name="' . $fieldname . '[]" type="checkbox" value="' . $key . '" checked />'
                        . '<span>' . $value->contentmodel->getLabel() . '</span>'
                        . '</label>'
                        . '' . $collectionform
                        . '</div>';
                }
            }
            $checkbox .= '<hr>';
            if ($field['options']['forcollectionform']) {
//                die(var_dump($field['options']["forcollectionform"]));
                foreach ($field['options']['forcollectionform'] as $key => $value) {
//                    $collectionform = $field['collectionforminjection']::__renderForm($field['values']['forcollectionform'][$key]);
                    $bc = new Blockcontentmodel();
                    $bc->contentmodel = $value;
                    $collectionform = BlockcontentmodelForm::__renderForm($bc);
                    $checkbox .= '<div class="checkbox dv-checkbox" >'
                        . '<label><input class="" name="' . $fieldname . '[]" type="checkbox" value="' . $key . '" />'
                        . '<span>' . $value->getLabel() . '</span>'
                        . '</label>'
                        . '' . $collectionform
                        . '</div>';
                }
            }

//            $checkbox .= '<hr>';
//            if ($diff) {
//                foreach ($diff as $key => $value) {
//                    $collectionform = $field['collectionforminjection']::__renderForm($field['options']['forcollectionform'][$key]);
//                    $checkbox .= '<div class="checkbox dv-checkbox" >'
//                            . '<label><input class="" name="' . $fieldname . '[]" type="checkbox" value="' . $key . '" />'
//                            . '<span>' . $value . '</span>'
//                            . '</label>'
//                            . '' . $collectionform
//                            . '</div>';
//                }
//            };
        } elseif (isset($field['checker'])) {
            if (is_callable($field['checker'])) {
                foreach ($field['options'] as $id => $value) {
                    $ckecked = $field['checker']($id) ? "checked" : "";
                    $checkbox .= '<div class="checkbox dv-checkbox" ><label><input class="" name="' . FormFactory::$fieldname . '[]" type="checkbox" value="' . $id . '" ' . $ckecked . ' /><span>' . $value . '</span></label></div>';
                }
            } elseif (is_array($field['checker'])) {
                foreach ($field['options'] as $id => $value) {
                    $ckecked = (in_array($id, $field['checker'])) ? "checked" : "";
                    $checkbox .= '<div class="checkbox dv-checkbox" ><label><input class="" name="' . FormFactory::$fieldname . '[]" type="checkbox" value="' . $id . '" ' . $ckecked . ' /><span>' . $value . '</span></label></div>';
                }
            }
        } else {

//            if ($field['values']) {
//                foreach ($field['values'] as $key => $value) {
//                    $checkbox .= '<div class="checkbox dv-checkbox" ><label><input class="" name="' . FormFactory::$fieldname . '[]" type="checkbox" value="' . $key . '" checked /><span>' . $value . '</span></label></div>';
//                }
//            }
//            $checkbox .= '<hr>';
            if ($field['options']) {
                foreach ($field['options'] as $id => $value) {
                    $ckecked = (in_array($id, $field['values'])) ? "checked" : "";
                    $checkbox .= '<div class="checkbox dv-checkbox" ><label><input class="" name="' . FormFactory::$fieldname . '[]" type="checkbox" value="' . $id . '" ' . $ckecked . ' /><span>' . $value . '</span></label></div>';
                }
            }
        }

        return $checkbox;
    }

    public static function __textarea($entitycore, $field, $directive = "", $lang = false)
    {
        if ($lang) {
            $inp = [];
            if (is_array($field['value'])) {
                foreach ($field['value'] as $iso => $val) {
                    $inp[$iso] = "<textarea " . $directive . " name='" . FormFactory::$fieldname . "[$iso]' >" . $val . "</textarea>";
                }
                $input = $inp;
            } else {
                foreach (self::$langs as $lang) {
                    $inp[$lang->iso_code] = "<textarea " . $directive . " name='" . FormFactory::$fieldname . "[{$lang->iso_code}]' ></textarea>";
                }
                $input = $inp;
            }

            return $input;
        }
        $textarea = "<textarea " . $directive . " name='" . FormFactory::$fieldname . "' ";

        return $textarea . '>' . $field['value'] . '</textarea>';
    }

    public static function __select($entitycore, $field, $directive = "", $callback = null)
    {

        $select = '<select ' . $directive . ' name="' . FormFactory::$fieldname . '" >';

        if (isset($field['placeholder'])) {
            $select .= '<option value="" >' . $field['placeholder'] . '</option>';
        }

        foreach ($field['options'] as $key => $value) {
            if (is_callable($callback)) {
                $select .= $callback($value, $field['value']);
                continue;
            }
            if ('' . $field['value'] == '' . $key)
                $select .= '<option value="' . $key . '" selected >' . $value . '</option>';
            else
                $select .= '<option value="' . $key . '" >' . $value . '</option>';
        }

        return $select . '</select>';
    }

    public static function __autocomplete($entitycore, $field, $directive = "", $callback = null)
    {

        $text = "";
        $value = "";
        $option = "";
        if (is_object($field['value'])) {
            $entity = $field['value'];
            $text = $entity->{$field['options']["text"]};
            $value = $entity->{$field['options']["value"]};
            $option = '<option value="' . $value . '" >' . $text . '</option>';
        }

        $select = '
        <div class="input-group">
            <input onblur="model.autocompletehide(this)" onfocus="model.autocompleteshow(this)" onkeyup="model.autocomplete(this)" autocomplete="off" ' . $directive . ' >
            <div class="input-group-append">
                <button onclick="model.autocompletereset(this, \'' . $value . '\', \'' . $text . '\')"
                        class="reset btn btn-outline-primary" type="button"><i class="fa fa-reply"></i>
                </button>
            </div>
        </div>
        ';

        $select .= '<div class="search-results"><ul class="list-group"></ul></div>
<select style="display: none" ' . $directive . ' name="' . FormFactory::$fieldname . '" >';

        $options = json_encode($field['options']);
        return "<div data-options='$options' class='dv-autocomplete'>" . $select . $option . '</select></div>';
    }

    public static function __selectGroup($entitycore, $field, $callback, $directive = "")
    {

        //dv_dump($field);
        $select = '<select ' . $directive . ' name="' . FormFactory::$fieldname . '" >';

        if (isset($field['placeholder'])) {
            $select .= '<option value="" >' . $field['placeholder'] . '</option>';
        }

        foreach ($field['options'] as $keygroup => $value) {
            $selectchild = '';
            $children = $callback($keygroup);
            if ($children) {
                foreach ($children as $key => $child) {
                    if ('' . $field['value'] == '' . $key)
                        $selectchild .= '<option value="' . $key . '" selected >' . $child . '</option>';
                    else
                        $selectchild .= '<option value="' . $key . '" >' . $child . '</option>';
                }
                $select .= '<optgroup label="' . $value . '" >' . $selectchild . '</optgroup>';
            } else {
                if ('' . $field['value'] == '' . $keygroup)
                    $select .= '<option value="' . $keygroup . '" selected >' . $value . '</option>';
                else
                    $select .= '<option value="' . $keygroup . '" >' . $value . '</option>';
            }
        }

        return $select . '</select>';
    }

    public static function __file($entitycore, $field, $require)
    {

        $input = FormFactory::__input($entitycore, $field, $require);
        $file = "";

        if (isset($field['src']))
            $file = self::__filepreview($field);

        return $file . $input;
    }

    public static function __filepreview($field)
    {
        $file = "";
        if ($field['value']) {
            $file = $field['src'];
//            switch ($field[FH_FILETYPE]) {
//
//                case FILETYPE_DOCUMENT:
//                    $file = "<a class='dv-doc' href='" . $field['src'] . "' download='" . $field['value'] . "'> download the document</a>";
//                    break;
//                case FILETYPE_VIDEO:
//                    $file = "<video class='dv-video' src='" . $field['src'] . "'> </video>";
//                    break;
//                case FILETYPE_AUDIO:
//                    $file = "<audio class='dv-audio' src='" . $field['src'] . "'> </audio>";
//                    break;
//                case FILETYPE_IMAGE:
//                    $file = $field['src'];
//                    break;
//            }
        }

        return $file;
    }

    public static function __input($entitycore, $field, $directive = null, $lang = null)
    {
        // le vocabulaire du cadre reste FORMTYPE_DATETIME
        // ('datetime') - la constante est publique, potentiellement utilisee
        // ailleurs, pas renommee. Seul l'attribut HTML emis est traduit :
        // type="datetime" est deprecie en HTML5 et degrade en champ texte
        // libre (aucun selecteur, aucune validation) ; type="datetime-local"
        // est le type valide.
        $htmltype = ($field['type'] === FORMTYPE_DATETIME) ? 'datetime-local' : $field['type'];

        if (!$lang)
            return "<input " . $directive . " type='" . $htmltype . "' name='" . FormFactory::$fieldname . "' value='" . $field['value'] . "'  />";
        else {
            $inp = [];
            if (is_array($field['value'])) {
                foreach ($field['value'] as $iso => $val) {
                    $inp[$iso] = "<input " . $directive . " type='" . $htmltype . "' name='" . FormFactory::$fieldname . "[$iso]' value='" . $val . "'  />";
                }
                $input = $inp;
            } else {
                foreach (self::$langs as $lang) {
                    $inp[$lang->iso_code] = "<input " . $directive . " type='" . $htmltype . "' name='" . FormFactory::$fieldname . "[{$lang->iso_code}]' value=''  />";
                }
                $input = $inp;
            }

            return $input;
        }

    }

    public static function serialysedirective($directives)
    {
        $formdirective = [];
        if (!isset($directives["id"]))
            $directives["id"] = FormFactory::$fieldid;

        foreach ($directives as $key => $value) {
            $formdirective[] = $key . "='" . $value . "'";
        }
        return implode(" ", $formdirective);
    }

    public static function getLabel($field, $etoil, $lang = "")
    {

        $label = "";
        if (isset($field['label'])) {
            $label = "<label class='dv_label ' >" . $field['label'] . " $lang " . $etoil . "</label>";
        }
        return $label;
    }

    private static $entityattrib = "";

    public static function __renderForm($entitycore)
    {

        $form = "";
        $formrow = "";
        //$_SESSION["dvups_form"][$entitycore->name] = $entitycore->field;
        self::$langs = Dvups_lang::all();
        foreach ($entitycore->field as $key => $field) {

            // reinitialise a chaque iteration : sans ca,
            // un type non gere plus bas (sans 'else' avant ce correctif)
            // laissait $formfield porter la valeur HTML du champ precedent,
            // avec son name= d'origine - une corruption de donnees silencieuse.
            $formfield = '';

            $directive = [];
            $etoil = '';
            $class = "";

            if (!isset($field['directive'])) {
                $directive['class'] = "form-control";
            } else {
                $directive = $field['directive'];

                if (!isset($directive['class'])) {
                    $directive['class'] = "form-control";
                }
            }
            // isset($directive['require']) ne lisait QUE
            // la convention de Dvups_adminForm.php ("directive" =>
            // ["require"=>true], 3 champs, 1 fichier) - mesure avant
            // d'ecrire : la convention reellement utilisee partout ailleurs
            // (plus d'une centaine d'occurrences dans src/) est
            // FH_REQUIRE => true au niveau du CHAMP, pas de la directive
            // (ex: ContestForm.php, "active"/"date_start"/"cover"/...).
            // Les deux etaient verifiees ici independamment, jamais
            // relues au meme endroit : l'astérisque n'apparaissait donc
            // JAMAIS sur aucun champ FH_REQUIRE => true de tout le depot -
            // seuls les 3 champs de Dvups_adminForm.php l'auraient
            // montree. Les deux conventions sont acceptees desormais, la
            // premiere trouvee gagne.
            if (isset($directive['require']) || (isset($field[FH_REQUIRE]) && $field[FH_REQUIRE])) {
                // required (le vrai attribut HTML5, pas
                // seulement visuel) pose ici : validation navigateur
                // gratuite. aria-required n'est pas ajoute en plus - un
                // <input required> expose deja aria-required="true" dans
                // l'arbre d'accessibilite (mapping HTML-ARIA standard),
                // l'ajouter serait redondant.
                $directive['required'] = 'required';
                // span, pas texte nu : aria-hidden parce que l'information
                // d'obligation doit venir de l'attribut required du champ,
                // pas d'un asterisque que le lecteur d'ecran annoncerait
                // sans contexte ("etoile" tout seul ne veut rien dire).
                $etoil = '<span class="dv_required" aria-hidden="true">*</span>';
            }
            // les trois doivent etre poses AVANT
            // serialysedirective() plus bas, qui lit FormFactory::$fieldid
            // pour l'attribut id= du champ courant. Poses apres (ordre
            // d'origine), serialysedirective() consommait encore la valeur
            // du champ PRECEDENT : chaque id= etait decale d'un champ (le
            // premier recevait une chaine vide, faute de valeur posee avant
            // la toute premiere iteration). serialysedirective() ne lit que
            // $fieldid, rien d'autre ne depend de l'ancien ordre.
            FormFactory::$fieldname = $entitycore->name . "_form[" . $key . ']';
            FormFactory::$fieldid = $entitycore->name . "-" . $key . '';
            FormFactory::$entityattrib = $key;

            $field['directive'] = FormFactory::serialysedirective($directive);

            if (!isset($field['setter'])) {
                $entitycore->field[$key]["setter"] = $key;
            }

            if (in_array($field['type'], [FORMTYPE_DATE, FORMTYPE_DATETIME, FORMTYPE_TEXT, FORMTYPE_EMAIL, FORMTYPE_NUMBER, FORMTYPE_PASSWORD])) {

                if (isset($field["lang"]) && $field["lang"]) {
                    $formfield = FormFactory::__input($entitycore, $field, $field['directive'], true);

                } else {
                    $formfield = FormFactory::__input($entitycore, $field, $field['directive']);
                }
            } elseif ($field['type'] == FORMTYPE_TEXTAREA) {
                $formfield = FormFactory::__textarea($entitycore, $field, $field['directive'], isset($field["lang"]));
            } elseif ($field['type'] == FORMTYPE_CHECKBOX) {
                $formfield = FormFactory::__checkbox($entitycore, $field, $field['directive']);
            } elseif ($field['type'] == FORMTYPE_RADIO) {
                $formfield = FormFactory::__radio($entitycore, $field, $field['directive']);
            } elseif ($field['type'] == FORMTYPE_SELECT) {
                $formfield = FormFactory::__select($entitycore, $field, $field['directive']);
            } elseif ($field['type'] == FORMTYPE_SELECTGROUP) {
                $formfield = FormFactory::__selectGroup($entitycore, $field, $field['callback'], $field['directive']);
            } elseif ($field['type'] == FORMTYPE_INJECTION) {
                $formfield = $field['imbricate'];
                unset($entitycore->field[$key]['imbricate']);
            } elseif ($field['type'] == FORMTYPE_FILE) {
                $formfield = FormFactory::__file($entitycore, $field, $field['directive']);
            } elseif ($field['type'] == FORMTYPE_FILEMULTIPLE) {

//                $input = FormFactory::__input($entitycore, $field, $require);
                $input = "<input " . $field['directive'] . " type='file' name='" . FormFactory::$fieldname . "[]' value='" . $field['value'] . "' multiple />";

                $file = "";
                if ($field['value']) {
                    $file = "<img class='dv-img' width='120' src='" . $field['src'] . "' />";
                }

                $formfield = $file . $input;
//                $formfield = FormFactory::__file($entitycore, $field, $field['directive']);
            } else {
                // type non gere : rendu visible en dev
                // (quel type, quel champ), jamais en production (aucun detail
                // technique, $formfield deja reinitialise a '' plus haut).
                if (!__prod) {
                    $formfield = "<!-- [FormFactory] type de champ non gere : '" . $field['type'] . "' (champ '" . $key . "') -->";
                }
            }

            $hidden = '';
            if (isset($field["hidden"]))
                $hidden = 'hidden';

            // gabarit en blocs, pas en tableau de mise
            // en page - decide apres recensement exhaustif des selecteurs
            // tr/td/table/id-de-ligne dans dclass/devupsjs/ ET dans tout
            // src/ (lecture seule) : dform.js n'a aucun selecteur de
            // formulaire par tr/td (confirme) ; le seul parents("tr") de
            // model.js (ligne 228) vise une ligne de DATATABLE lors d'une
            // suppression, pas un formulaire (confirme) ; model._formdata()
            // (le serialiseur reellement utilise a l'envoi, dform.js:106)
            // lit par form.find('input')/find('textarea')/find('select'),
            // insensible a la structure parente (confirme) ; une fonction
            // model._formdatacustom(tbody) qui lit bien des <tr> n'a AUCUN
            // appelant nulle part (code mort, confirme). Dans src/, les
            // seuls tr/td/table trouves sont (a) les tableaux de TRI de
            // datatable (fichiers *Ctrl.js - tous scopes sur #dv_table, sans rapport avec
            // un formulaire), (b) des GABARITS HTML de la barre d'outils du
            // bloc-editeur (cmstextForm.js/collectorForm.js, un <table>
            // genere pour des boutons, sans rapport avec le gabarit
            // FormFactory). Aucune dependance trouvee : conversion faite.
            $class .= ' dv_field';

            if (isset($field["lang"]) && $field["lang"] && is_array($formfield)) {

                // pas de for= ici : ce libelle coiffe
                // PLUSIEURS champs (un par langue, voir la boucle juste en
                // dessous), qui partagent d'ailleurs tous le meme id= (un
                // defaut preexistant, hors mandat de ce point) - aucun
                // for= unique ne serait correct, en poser un pointerait
                // arbitrairement sur le premier id dupliqué.
                $label = "";
                if (isset($field['label'])) {
                    $label = "<label class='dv_label ' >" . $field['label'] . " " . $etoil . "</label>";
                }
                $td = "";
                foreach ($formfield as $iso_code => $fieldlang) {
                    //$label = self::getLabel($field, $etoil, $iso_code);
                    $td .= "<div $hidden id='" . FormFactory::$fieldname . "_$iso_code' class='dv_field_lang form-group' >" . $iso_code . $fieldlang . "</div>";
                }
                $formrow .= "<div $hidden id='" . FormFactory::$fieldname . "' class='" . $class . " form-group' >
<div class='dv_field_label'>" . $label . "</div><div class='dv_field_value'>" . $td . "</div></div>";
            } else {

                // for= pose, mais verifie correspondant
                // d'abord - pas suppose : FormFactory::$fieldid est bien
                // l'id= que serialysedirective() pose par defaut sur le
                // CONTROLE de la plupart des types de champ (reparation du
                // 26/10, confirme en lisant chaque __xxx() de ce fichier) -
                // mais pas pour RADIO ni CHECKBOX. __radio() pose $directive
                // (donc cet id=) sur le <label> de CHAQUE option, pas sur
                // l'<input> - verifie sur le rendu reel : les options
                // partagent toutes le meme id, dupliquee, un for= ici
                // pointerait vers un <label> au hasard, pas vers un champ -
                // clic casse (confirme : le clic sur un libelle radio
                // deplacait le focus sur un AUTRE champ du formulaire).
                // __checkbox() ne lit meme pas $directive : aucun id= du
                // tout sur ses <input>. Dans les deux cas, for= omis
                // plutot que pose sur une correspondance fausse - mesure,
                // pas corrige (changer __radio()/__checkbox() pour qu'ils
                // posent l'id sur le bon <input> est plus large que "lier
                // le libelle").
                $label = "";
                if (isset($field['label'])) {
                    $for = in_array($field['type'], [FORMTYPE_RADIO, FORMTYPE_CHECKBOX]) ? '' : "for='" . FormFactory::$fieldid . "' ";
                    $label = "<label $for class='dv_label ' >" . $field['label'] . " " . $etoil . "</label>";
                }

                // $hidden pose une seule fois, en
                // attribut (hidden, fonctionne) - plus dans la liste de
                // classes ("... hidden", ne faisait rien : 0 regle .hidden
                // dans main.css, une classe Bootstrap 3 retiree en 4, poids
                // mort retire).
                $formrow .= "<div $hidden id='" . FormFactory::$fieldname . "' class='" . $class . " form-group' ><div class='dv_field_label'>" . $label . "</div><div class='dv_field_value'>" . $formfield . "</div></div>";

            }
        }

        // ouverture et fermeture de <form> vivaient
        // dans deux variables differentes ($formaction et $formbutton),
        // la fermeture </form> a l'INTERIEUR de $formbutton - formaction
        // vrai + formbutton faux laissait donc le <form> jamais ferme (le
        // navigateur referme ou il peut, tout ce qui suit dans la page
        // peut se retrouver dans le formulaire) ; formaction faux +
        // formbutton vrai laissait un </form> orphelin. Compte dans src/
        // (lecture) : ~25 fichiers *Form.php surchargent la liaison
        // automatique formaction<->formbutton de FormManager::__construct()
        // (qui derive toujours les deux du MEME $action, jamais divergents)
        // avec deux parametres INDEPENDANTS ($action et $button, parfois
        // $dataform['action'] et $button) - un risque structurel reel.
        // Echantillonne sur 5 modules (Dvups_admin, Town, Gender,
        // Notificationbroadcasted, Message) : chaque render() genere
        // (gabarit commun, meme generateur) appelle systematiquement
        // __renderForm(getFormData(...), true) - $button toujours vrai
        // quand $action l'est, aucune divergence constatee sur les chemins
        // reellement empruntes. Compte de divergence observee : zero -
        // une bombe non amorcee, desamorcee quand meme (comme demande).
        // Ouverture ET fermeture dans la MEME expression desormais, pour
        // que le prochain ne puisse plus les separer.
        $id = $entitycore->entity->getId();
        if (is_array($id)) {
            $id = implode(',', $id);
        }
        // $hasform, une seule variable, lue deux fois
        // (ouverture plus bas, fermeture dans $formbutton) - pas deux
        // conditions separees qui pourraient un jour diverger : si l'une
        // des deux lectures doit changer, l'autre le dira au premier
        // test manuel au lieu de laisser un <form> mal ferme en silence.
        $hasform = (bool)$entitycore->formaction;

        $formaction = "";
        if ($hasform) {
            $formaction = "<form id='" . $entitycore->name . "-form' action='" . $entitycore->formaction . "' data-id='" . $id . "' enctype='multipart/form-data' method='post' >\n";
        }

        // bouton d'envoi : --dv-color-primary
        // (l'action principale n'est pas une confirmation de succes,
        // c'est une action) a la place de btn-success (vert) ; pleine
        // largeur seulement sous le seuil mobile (dv_style.css), plus en
        // dur sur page large. Pas de bouton d'annulation ajoute en
        // modale : la croix de fermeture existe deja.
        $formbutton = "";
        if ($entitycore->formbutton) {
            $formbutton = "<button class='btn btn-primary dv_submit' type='submit' >" . t("Save") . "</button>";//<input class='btn btn-light' type='reset' value='reset' >
        }
        // </form> ne depend plus de $formbutton : lit
        // $hasform, la meme variable que l'ouverture ci-dessus, jamais
        // $entitycore->formbutton.
        if ($hasform) {
            $formbutton .= "</form>";
        }

        $formjs = "";
        if (isset($entitycore->addjs) && $entitycore->addjs) {
            foreach ($entitycore->addjs as $js) {
                $formjs .= "<script src='$js.js' ></script>";
            }
        }

        $formcss = "";
        if (isset($entitycore->addcss) && $entitycore->addcss) {
            foreach ($entitycore->addcss as $css) {
                $formcss .= "<link href='$css.css' rel=\"stylesheet\" />";
            }
        }

        // <table class='table'> -> <div class=
        // 'dv_form_rows'> : class='table' etait la classe des tableaux DE
        // DONNEES (bordures, rembourrage, rayures) posee sur un
        // formulaire, sans rapport. Le gabarit en blocs lui-meme est
        // construit plus haut (dv_field par champ) - ce conteneur n'est
        // plus qu'un simple wrapper.
        $form = $formcss . $formaction .
            "<div class='dv_form_rows'>" . $formrow . "</div>"
            //. $dvups_form
            . $formbutton . $formjs;

        return $form;
    }

}
