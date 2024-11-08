<?php
class IdRef extends Plugins
{
    public function enableAction(&$context, &$error)
    {
        if(!parent::_checkRights(LEVEL_ADMIN)) { return; }
        $result = $this->installIdRef($context);
	$this->addTranslations();
        echo $result ;
        return "_ajax" ;
    }

    public function disableAction(&$context, &$error)
    {
    }

    public function preview(&$context)
    {
    }

    public function postview(&$context)
    {
        $pluginrights = isset($this->_config['userrights']['value']) ? $this->_config['userrights']['value'] : 30;
        if ($context['view']['tpl'] == 'edit_entities_edition' && isset($context['persons']) && $context['lodeluser']['rights'] >= $pluginrights)
        {
            self::insertToPage("/<\/head>/", self::jsCssDeclaration($context) , true);
            self::insertToPage("/<\/div>\s*<\/div>\s*<\/body>/s", self::insertIdRefWidget($context) , true);
        }
    }

    private static function insertIdRefWidget($context)
    {
        $persons = $context['persons'];
        $site = $context['site'];
        $documentid = $context['iddocument'];

        global $db;

        $idref_widget = '<div class="advancedFunc">
                             <h4>IdRef</h4>
                             <form id="idref-form" action="index.php?do=_idref_record" method="POST">
                                 <div class="idref-widget">';

        foreach ($persons as $persontype => $authors)
        {
            if (self::getPersonType($persontype) == 'auteuroeuvre') continue;
            foreach ($authors as $author)
            {
                $forename = $author['data']['prenom'];
                $surname = $author['data']['nomfamille'];
                $person_id = $author['data']['idperson'];
                $idref = $author['data']['idref'];
                $idref_widget .= self::idrefSection($idref, $surname, $forename, $person_id);
            }
        }

        $idref_widget .= '<div class="idref-search-all"><button class="idref" id="idref-search-all">'.getlodeltextcontents('idref_find_all', 'edition').'</button></div>';
        $idref_widget .= '</div><!-- .idref-widget -->';
        $idref_widget .= '<input type="hidden" name="documentid" value="' . $documentid . '"/>';
        $idref_widget .= '<button type="submit" class="idref blue">'.getlodeltextcontents('idref_save', 'edition').'</button>';
        $idref_widget .= '</form></div>';
        return $idref_widget;
    }
    private function idrefSection($idref, $surname, $forename, $personid)
    {
        $idref_section = '<div class="idref-block"><div class="idref-block-title">' . $forename . ' ' . $surname . '</div>';
        $idref_section .= '<input type="hidden" name="personids[]" value="' . $personid . '" />';
        $idref_section .= '<div class="idref-block-content">';
        $idref_section .= '<label for="idref-' . $personid . '">IdRef</label>';
        $idref_section .= '<input id="idref-' . $personid . '" style="max-width:70px;' . $color . '" type="text" name="idrefs[]" value="' . $idref . '" data-surname="' . $surname . '" data-forename="' . $forename . '" data-personid="' . $personid . '" class="idref-field" / >';
        if (!empty($idref))
        {
            $idref_section .= '<span id="idref-status-' . $personid . '" class="idref-status idref-saved">'.getlodeltextcontents('idref_idref_saved', 'edition').'</span>';
        }
        else
        {
            $idref_section .= '<span id="idref-status-' . $personid . '" class="idref-status"></span>';
        }
        $idref_section .= '<p><span id="idref-num-found-' . $personid . '" class="idref-num-found"></span></p>';
        $idref_section .= '</div></div>';
        return $idref_section;
    }

    private function getPersonType($idtype)
    {
        global $db;
        $q = "select type from persontypes where id='$idtype'";
        return $db->getOne(lq("$q"));
    }

    public function recordAction(&$context, &$errors)
    {
        $personids = $_POST['personids'];
        $idrefs = $_POST['idrefs'];
        $documentid = $_POST['documentid'];
        global $db;
        for ($i = 0;$i < count($personids);$i++)
        {
            $personid = $personids[$i];
            $idref = $idrefs[$i];
            $q = "update entities_auteurs ea join relations r on ea.idrelation=r.idrelation set idref='$idref' where r.id1='$documentid' and r.id2='$personid' and nature='G'";
            $result = $db->execute(lq($q));
            if ($result === false)
            {
                trigger_error("SQL ERROR :<br />" . $GLOBALS['db']->ErrorMsg() , E_USER_ERROR);
            }
        }
        header("Location: index.php?do=view&id=$documentid");
        return "_ajax";
    }

    private static function jsCssDeclaration($context)
    {
        $static = $context['shareurl'] . "/plugins/custom/idref/static/";
        return '
	    <link rel="stylesheet" href="' . $static . 'css/style.css">
	    <link rel="stylesheet" href="' . $static . 'css/subModal.css">
	    <script src="' . $static . 'js/jquery/3.7.1/jquery.min.js"></script>
	    <script src="' . $static . 'js/idref.js"></script>
	    <script src="' . $static . 'js/form.js"></script>
	    <script src="' . $static . 'js/subModal.js"></script>
	    <script>'.self::jsTranslations().'</script>
	    ';
    }

    private static function insertToPage($pattern, $insert, $beforeMatch = true)
    {
        $page = View::$page;
        $offset = 0;
        preg_match($pattern, $page, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches as $match)
        {
            $position = $match[1] + $offset;
            if (!$beforeMatch)
            {
                $position = $position + strlen($match[0]);
            }
            $page = substr($page, 0, $position) . $insert . substr($page, $position);
            $offset += strlen($insert);
        }
        View::$page = $page;
    }

    private function jsTranslations()
    {
        $js = 'var translations = {';
        $js .= '"idref_saved" : "'.getlodeltextcontents('idref_idref_saved', 'edition').'",';
        $js .= '"idref_not_saved" : "'.getlodeltextcontents('idref_idref_not_saved', 'edition').'",';
        $js .= '"find_all" : "'.getlodeltextcontents('idref_find_all', 'edition').'",';
        $js .= '"idref_found" : "'.getlodeltextcontents('idref_idref_found', 'edition').'",';
        $js .= '"search_for_x_persons" : "'.getlodeltextcontents('idref_search_for_x_persons', 'edition').'",';
        $js .= '"check_in_idref" : "'.getlodeltextcontents('idref_check_in_idref', 'edition').'"';
        $js .= '};';
	return $js;
    }

    private function installIdRef($context)
    {
        $idref_field_defined = DAO::getDao('tablefields')->find('name="idref" AND class="entities_auteurs"', 'id');
        if ($idref_field_defined !== NULL)
	{
	    return "IdRef field exists";
	}
        $localcontext = $context;
        $localcontext['name'] = 'idref';
        $localcontext['title'] = 'IdRef';
        $localcontext['class'] = 'entities_auteurs';
        $localcontext['type'] = 'tinytext';
        $localcontext['gui_user_complexity'] = 16;
        $localcontext['cond'] = '*';
        $localcontext['edition'] = 'editable';
        $localcontext['otx'] = '//tei:idno[@type=\'IDREF\']';
        if (true !== ($err = Controller::addObject('tablefields', $localcontext)))
                trigger_error(print_r($err,true), E_USER_ERROR);

        return "Successful installation" ;
    }

    private function addTranslations()
    {
        global $db;
        $lodeladmin_languages = $db->getAll(lq("SELECT DISTINCT(lang) FROM #_MTP_translations"));

        $texts = $this->getTranslations();
        $logic = Logic::getLogic('texts');
        $langs = array();
        foreach ($lodeladmin_languages as $row)
	{
            $langs[] = $row['lang'];
        }
        foreach ($langs as $lang) {
            foreach ($texts as $name => $text)
	    {
	        $status=$db->getOne(lq("SELECT status FROM #_MTP_texts WHERE lang='$lang' AND textgroup='edition' AND name='$name'"));
	        if (null !== $status && "-1" !== $status) continue; // If translation is set and translation status != -1, don't overwrite value
                $db->execute(lq("delete FROM #_MTP_texts WHERE lang='$lang' AND textgroup='edition' AND name='$name'")); // delete old value
                $text_to_insert = isset($text[$lang]) ? $text[$lang] : $text['en']; // If translation value is not defined for a language, use english value
                $context = array('name' => $name, 'contents' => $text_to_insert, 'lang' => $lang, 'textgroup' => 'edition', 'status' => 2);
                $error = null;
                $logic->editAction($context, $error);
                if ($error)
                    echo "Error while importing lang ".var_export($error,true)."\n";
            }
        }
    }

    private function getTranslations()
    {
        return [
            'idref_save' =>
                [
                    'fr' => 'Enregistrer',
                    'en' => 'Save',
                ],
            'idref_idref_saved' =>
                [
                    'fr' => 'IdRef enregistré',
                    'en' => 'IdRef saved',
                ],
            'idref_idref_not_saved' =>
                [
                    'fr' => 'IdRef non enregistré',
                    'en' => 'IdRef not saved',
                ],
            'idref_find_all' =>
                [
                    'fr' => 'Rechercher toutes les personnes dans IdRef',
                    'en' => 'Search for all persons in IdRef',
                ],
            'idref_idref_found' =>
                [
                    'fr' => 'IdRef trouvé(s)',
                    'en' => 'IdRef found',
                ],
            'idref_search_for_x_persons' =>
                [
                    'fr' => 'Rechercher parmi %s personnes dans IdRef',
                    'en' => 'Search for %s persons in IdRef',
                ],
            'idref_check_in_idref' =>
                [
                    'fr' => 'Vérifier dans IdRef',
                    'en' => 'Check in IdRef',
                ],
        ];
    }
}

