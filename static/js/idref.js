$(document).ready(function() {
    // IdRef Search all
    $(document).on("click", "#idref-search-all", function(e) {
        e.preventDefault();
        initPopUp();
        $.each($(".idref-field"), function() {
            var personid = $(this).attr("data-personid");
            var surname = $(this).attr("data-surname").replace(/\s\s+/g, ' ');
            var forename = $(this).attr("data-forename").replace(/\s\s+/g, ' ');
            var name = surname + ' ' + forename;
            var name_array = name.split(" ");
            var name_solr_query = name_array.join(' AND ');
            var idref_num_found = "#idref-num-found-" + $(this).attr("data-personid");
            var idref_field = "#idref-" + $(this).attr("data-personid");
            var idref_status = "#idref-status-" + $(this).attr("data-personid");
            var idref_check = "check-person-" + $(this).attr("data-personid");

            // reset 
            $(idref_num_found).html();
            $(".js").remove();

            $.get("https://www.idref.fr/Sru/Solr?wt=json&q=persname_t:(" + encodeURIComponent(name_solr_query) + ")&fl=ppn_z", function(data) {
                $(idref_num_found).html("<span>" + data.response.numFound + " " + translations['idref_found'] + "</span>");
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
                        $(idref_field).removeClass("invalid-idref");
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
	var unimarc = 'z200_a:\''+$(idref_id).attr("data-surname")+'\',z200_b:\''+$(idref_id).attr("data-forename")+'\'';
        envoiClient('Nom de personne', $(idref_id).attr("data-surname") + ' ' + $(idref_id).attr("data-forename"), $(this).attr("data-personid"),unimarc);return false;
    });

});

