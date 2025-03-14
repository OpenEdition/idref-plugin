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
        $context['idref_report_to_email'] = C::get('idref_report_to_email', 'cfg');
        $context['idref_report_from_email'] = C::get('idref_report_from_email', 'cfg');

        if ($context['view']['tpl'] == 'edit_entities_edition' && isset($context['persons']))
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
        $documenturl = $context['permanent_link'];

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
                $forename = $author['data']['prenom'] ?? null;
                $surname = $author['data']['nomfamille'] ?? null;
                $person_id = $author['data']['idperson'] ?? null;
                $idref = $author['data']['idref'] ?? null;
                $description = preg_replace('/(\s\s*|&#39;)/', ' ', htmlspecialchars(html_entity_decode(strip_tags($author['data']['description'])), ENT_COMPAT | ENT_XML1, 'UTF-8', false));
                $idref_widget .= self::idrefSection($idref, $surname, $forename, $person_id, $description, $documenturl);
            }
        }

        $idref_widget .= '<div class="idref-search-all"><button class="idref" id="idref-search-all">'.getlodeltextcontents('idref_find_all', 'edition').'</button></div>';
        $idref_widget .= '</div><!-- .idref-widget -->';
        $idref_widget .= '<input type="hidden" name="documentid" value="' . $documentid . '"/>';
        $idref_widget .= '<button type="submit" class="idref blue">'.getlodeltextcontents('idref_save', 'edition').'</button>';
	$idref_widget .= '</form>';
        $idref_widget .= '
	    <script>
	    $(document).ready(function() {
                // IdRef validation
                $( "#idref-form,#edit_ent,#actionentity" ).on( "submit", function( event ) {
                    var first_invalid_idref = false;
                    let idref_regex = /^[0-9]{8}[0-9X]{1}$|^$/;
                    $(".idref-field").each(function(field) {
                        if (!idref_regex.test($(this).val())) {
                           $(this).addClass("invalid-idref");
                           first_invalid_idref = (first_invalid_idref === false) ? $(this) : first_invalid_idref;
                        }
                    });
                    if ( first_invalid_idref === false ) {
                      return;
                    }
                    $.alert({
                        useBootstrap: false,
                        boxWidth: "500px",
                        scrollToPreviousElement: false,
                        title: "'.getlodeltextcontents('idref_invalid_title', 'edition').'",
                        content: "'.getlodeltextcontents('idref_invalid', 'edition').'",
                    });
                    $("html, body").animate({
                        scrollTop: first_invalid_idref.offset().top
                    }, 500);
                    event.preventDefault();
                }); 
                
                $(".idref-field").change(function(field) {
                   $(this).removeClass("invalid-idref");
                });
                $(".idref-field").click(function(field) {
                   $(this).removeClass("invalid-idref");
                });
            });
            </script>
        ';

	if (false !== $context['idref_report_to_email'] && false !== $context['idref_report_from_email'] ){
            $idref_widget .= '<hr class="idref"/><div class="report_issue"><button class="idref info" id="idref-report">'.getlodeltextcontents('idref_report_issue', 'edition').'</button></div>';
            $idref_widget .= '
	    <script>
            $(document).ready(function() {
                $("#idref-report").on({
                    click: function() {
                        $.confirm({
                            useBootstrap: false,
                            boxWidth: "500px",
                            title: "'.getlodeltextcontents('idref_report_issue_confirm_title', 'edition').'",
                            content: "'.getlodeltextcontents('idref_report_issue_confirm_content', 'edition').'",
                            buttons: {
                                cancel: {
                                    action: function() {},
                                    text: "'.getlodeltextcontents('idref_report_issue_confirm_no', 'edition').'",
                                    keys: ["esc"]
                                },
                                confirm: {
                                    text: "'.getlodeltextcontents('idref_report_issue_confirm_yes', 'edition').'",
                                    btnClass: "btn-blue",
                                    keys: ["enter"],
                                    action: function() {
                                        $.post(
                                            "index.php?do=_idref_report", {
                                                id: "'.$context["id"].'",
                                                site: "'.$context["site"].'"
                                            },
                                            function(data, status) {
                                                $("#idref-report").off("click");
                                                $("#idref-report").addClass("disabled");
                                                $("#idref-report").text("'.getlodeltextcontents('idref_report_issue_disabled', 'edition').'");
                                                $.alert({
                                                    escapeKey: "ok",
                                                    title: "'.getlodeltextcontents('idref_report_issue_confirm_thankyou', 'edition').'",
                                                    content: data,
                                                    useBootstrap: false,
                                                    boxWidth: "500px"
                                                });
                                            }
                                        );
                                    }
                                }
                            }
                        });
                    },
                });

            });
            </script>
            ';
	}
        $idref_widget .= '</div>';
        return $idref_widget;
    }
    private static function idrefSection($idref, $surname, $forename, $personid, $description, $documenturl)
    {
        $idref_section = '<div class="idref-block"><div class="idref-block-title">' . $forename . ' ' . $surname . '</div>';
        $idref_section .= '<input type="hidden" name="personids[]" value="' . $personid . '" />';
        $idref_section .= '<div class="idref-block-content">';
        $idref_section .= '<label for="idref-' . $personid . '">IdRef</label>';
        $idref_section .= '<input id="idref-' . $personid . '" style="max-width:70px;" type="text" name="idrefs[]" value="' . $idref . '" data-surname="' . $surname . '" data-forename="' . $forename . '" data-personid="' . $personid . '" data-description="' . $description . '" data-documenturl="' . $documenturl . '" class="idref-field" / >';
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

    private static function getPersonType($idtype)
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

    public function reportAction(&$context, &$errors)
    {
        if (empty($_POST)) {
            echo "Invalid POST data"; die();
        }
        if (!preg_match("/[0-9]?/",$_POST['id'])) {
            echo "Invalid id"; die();
        }
        $id = $_POST['id'];
	$url = $context['siteinfos']['url'].'/lodel/edition/index.php?do=view&id='.$id;

        $context['idref_report_to_email'] = C::get('idref_report_to_email', 'cfg');
        $context['idref_report_from_email'] = C::get('idref_report_from_email', 'cfg');
	if (false === $context['idref_report_to_email']) {
            echo "idref_report_to_email missing"; die();
        }
	if (false === $context['idref_report_from_email']) {
            echo "idref_report_from_email missing"; die();
        }

        if ($context['lodeluser']['adminlodel']) {
            $user_table="#_MTP_users";
        } else {
            $user_table="#_TP_users";
        }
        global $db;
        $get_email = $db->getAll(lq('SELECT email FROM '.$user_table.' where username="'.$context['lodeluser']['name'].'"'));
        $user_email = $get_email[0]['email'];

        $subject = "Missing Idref: ".$url;
        $body = "Missing Idref: ".$url."\nfrom: ". $user_email;
        if (send_mail($context['idref_report_to_email'], $body, $subject, $context['idref_report_from_email'], "Lodel IdRef Plugin", [], false, false, false)) {
            echo getlodeltextcontents('idref_report_issue_msg_ok', 'edition');
        } else {
            echo getlodeltextcontents('idref_report_issue_msg_error', 'edition');
        }
            return "_ajax";

    }

    private static function jsCssDeclaration($context)
    {
        $static = $context['shareurl'] . "/plugins/custom/idref/static/";
        return '
	    <link rel="stylesheet" href="' . $static . 'css/style.css">
	    <link rel="stylesheet" href="' . $static . 'css/subModal.css">
            <link rel="stylesheet" href="' . $static . 'css/jquery-confirm/3.3.4/jquery-confirm.min.css">
	    <script src="' . $static . 'js/jquery/3.7.1/jquery.min.js"></script>
            <script src="' . $static . 'js/jquery-confirm/3.3.4/jquery-confirm.min.js""></script>
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

    private static function jsTranslations()
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
        $localcontext['edition'] = 'display';
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
            'idref_report_issue' =>
                [
                    'fr' => 'Signaler un IdRef manquant',
                    'en' => 'Report missing IdRef',
                ],
            'idref_report_issue_disabled' =>
                [
                    'fr' => 'Signalement déjà effectué',
                    'en' => 'Missing IdRef already reported',
                ],

            'idref_report_issue_msg_ok' =>
                [
                    'fr' => 'Votre signalement a bien été envoyé',
                    'en' => 'Your report has been sent',
                ],
            'idref_report_issue_msg_error' =>
                [
                    'fr' => 'Erreur lors de l\'envoi du signalement',
                    'en' => 'Error in sending the report',
                ],
            'idref_report_issue_confirm_title' =>
                [
                    'fr' => 'Confirmez !',
                    'en' => 'Confirm!',
                ],
            'idref_report_issue_confirm_content' =>
                [
                    'fr' => 'Voulez-vous vraiment signaler un IdRef manquant pour ce document ? <br>Cette action enverra un mail à notre documentaliste pour la création de la notice IdRef.',
                    'en' => 'Do you really want to report a missing IdRef for this document? <br>This action will send an email to our documentalist to create the IdRef record.',
                ],
            'idref_report_issue_confirm_no' =>
                [
                    'fr' => 'non',
                    'en' => 'no',
                ],
            'idref_report_issue_confirm_yes' =>
                [
                    'fr' => 'oui',
                    'en' => 'yes',
                ],
            'idref_report_issue_confirm_thankyou' =>
                [
                    'fr' => 'Merci !',
                    'en' => 'Thank you!',
                ],
            'idref_invalid_title' =>
                [
                    'fr' => 'Un ou plusieurs IdRef sont invalides',
                    'en' => 'One or more IdRef are invalid',
                ],
            'idref_invalid' =>
                [
                    'fr' => 'Le format de l\'IdRef doit être : 8 chiffres suivis d\'un chiffre ou d\'un \'X\'.',
                    'en' => 'The format of the IdRef must be: 8 digits followed by a digit or an \'X\'.',
                ],
        ];
    }
}
