$(document).ready(function() {
    // IdRef Search all
    $(document).on("click", "#idref-search-all", function(e) {
        e.preventDefault();
        initPopUp();
        $.each($(".idref-field"), function() {
            var personid = $(this).attr("data-personid");
            var surname = $(this).attr("data-surname");
            var forename = $(this).attr("data-forename");
            var idref_num_found = "#idref-num-found-" + $(this).attr("data-personid");
            var idref_field = "#idref-" + $(this).attr("data-personid");
            var idref_status = "#idref-status-" + $(this).attr("data-personid");
            var idref_check = "check-person-" + $(this).attr("data-personid");

            // reset 
            $(idref_num_found).html();
            $(".js").remove();

            $.get("https://www.idref.fr/Sru/Solr?wt=json&q=persname_t:(" + encodeURIComponent($(this).attr("data-surname")) + " AND " + encodeURIComponent($(this).attr("data-forename")) + ")&fl=ppn_z", function(data) {
                $(idref_num_found).html(data.response.numFound + " " + translations['idref_found']);
                if (data.response.numFound == 1) {
                    if ($(idref_field).val() != "" && $(idref_field).val() != data.response.docs[0].ppn_z) {
                        if (confirm("IdRef already set for " + forename + " " + surname + ": \n" + $(idref_field).val() + "\nis different from IdRef found:\n" + data.response.docs[0].ppn_z + " \nReplace with this IdRef?")) {
                            var insert_idref = true;
                        }
                    } else if ($(idref_field).val() == "") {
                        var insert_idref = true;
                    }
                    if (insert_idref) {
                        $(idref_field).val(data.response.docs[0].ppn_z);
                        $(idref_status).html(translations['idref_not_saved']);
                        $(idref_status).addClass("idref-not-saved");
                    }
                    $(idref_num_found).after("<span class=\"js\"><button id=\"" + idref_check + "\" class=\"idref-check idref small\" data-personid=" + personid + ">" + translations['check_in_idref'] + "</button></span>");
                } else {
                    $(idref_num_found).after("<span class=\"js\"><button id=\"" + idref_check + "\" class=\"idref-check idref small\" data-personid=" + personid + ">" + translations['search_for_x_persons'].replace(/%s/g, data.response.numFound) + "</button></span>");
                }
            });
        });
    });
    $(document).on("click", ".idref-check", function(e) {
        e.preventDefault();
        var idref_id = "#idref-" + $(this).attr("data-personid");
        envoiClient('Nom de personne', $(idref_id).attr("data-forename") + ' ' + $(idref_id).attr("data-surname"), $(this).attr("data-personid"));
        return false;
    });

});

//145         $js = 'var translations = {';
//146         $js .= '"idref_saved" : "'.getlodeltextcontents('idref_idref_saved', 'edition').'",';
//147         $js .= '"idref_not_saved" : "'.getlodeltextcontents('idref_idref_not_saved', 'edition').'",';
//148         $js .= '"find_all" : "'.getlodeltextcontents('idref_find_all', 'edition').'",';
//149         $js .= '"idref_found" : "'.getlodeltextcontents('idref_idref_found', 'edition').'",';
//150         $js .= '"search_for_x_persons" : "'.getlodeltextcontents('idref_search_for_x_persons', 'edition').'",';
//151         $js .= '"check_in_idref" : "'.getlodeltextcontents('idref_check_in_idref', 'edition').'"';
//152         $js .= '};';

