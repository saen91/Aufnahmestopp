<?php

//Direkten Zugriff auf diese Datei aus Sicherheitsgründen nicht zulassen
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

//Informationen zum Plugin
function applicantstop_info()
{
	global $lang;
	$lang->load('applicantstop');
	
	return array(
		'name' => $lang->applicantstop_name,
		'description' => $lang->applicantstop_desc_acp,
		'author' => "saen",
		'authorsite' => "https://github.com/saen91",
		'version' => "1.0",
		'compatibility' => "18*"
	);
}

// Diese Funktion installiert das Plugin
function applicantstop_install()
{
	global $db, $cache, $mybb;
	
	//LEGE DATENBANK TABELLE AN aufnahmestop
	$db->write_query("
	CREATE TABLE " . TABLE_PREFIX . "applicantstop_entries (
		`stopid` int(11)  NOT NULL auto_increment, 
		`stoptitel`varchar(500) CHARACTER SET utf8 NOT NULL,
		`stopdesc` longtext CHARACTER SET utf8 NOT NULL,
		`startdate` varchar (140) NOT NULL, 
		`enddate` varchar (140) NOT NULL, 
		`archiv` tinyint (1) NOT NULL, 
		PRIMARY KEY (`stopid`)
		)
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
    ");



// EINSTELLUNGEN anlegen - Gruppe anlegen
	$setting_group = array (
		'name' => 'aufnahmestop',
		'title' => 'Aufnahmestop für Foren',
		'description' => 'Aufnahmestop für Foren Einstellungen',
		'disporder' => 1,
		'isdefault' => 0
	);

	$gid = $db->insert_query("settinggroups", $setting_group);
	
//Die dazugehörigen einstellungen
	$setting_array = array(
		// Einstellungen ob beendete angezeigt werden sollen, ja/nein
		'applicantstop_solvedstop' => array(
		'title' => 'Beendete anzeigen?',
	    'description' => 'Sollen auch beendete angezeigt werden?',
	    'optionscode' => 'yesno',
	    'value' => '1', // Default
	    'disporder' => 1 ),
	    );
	
	foreach ($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;
		
		$db->insert_query('settings', $setting);
	}

	rebuild_settings();
	
// Template hinzufügen:
	$insert_array = array(
		'title' => 'applicantstop_main',
		'template' => $db->escape_string('<html>
		<head>
		<title>{$settings[\'bbname\']} - {$lang->applicantstop_name}</title>
		{$headerinclude}
		</head>
			<body>
			{$header}
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
						<td class="thead"><strong>{$lang->applicantstop_name}</strong></td>
					</tr>
					<tr>
						<td class="trow1" align="center">
							{$lang->applicantstop_welcome}
						</td>
					<tr>
						<td  class="trow1">
						{$aktiven}
						{$alten}
						</td>
					</tr>
					</tr>
				</table>
			{$footer}
			</body>
		</html>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'applicantstop_view',
		'template' => $db->escape_string('<tr>
        <td width="33%" valign="top">{$startdate}</td>
		<td width="33%" valign="top"><b>{$stoptitel}</b><br>{$stopdesc}</td>
        <td width="33%" valign="top">{$enddate}</td>
    </tr>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
	
	$insert_array = array(
		'title' => 'applicantstop_active',
		'template' => $db->escape_string('<table width="80%" style="margin:auto;">
		<tr>
		<td class="tcat" colspan="3">{$lang->applicantstop_titel_active}</td>
		</tr>
		<tr>
			<td width="33%" class="tcat">{$lang->applicantstop_beginn}</td>
			<td width="33%" class="tcat">{$lang->applicantstop_art}</td>
			<td width="33%" class="tcat">{$lang->applicantstop_ende}</td>
		</tr>
	{$stop_view}
	</table>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
	
	$insert_array = array(
		'title' => 'applicantstop_solved',
		'template' => $db->escape_string('<table width="80%" style="margin:auto;">
		<tr>
		<td class="tcat" colspan="3">{$lang->applicantstop_titel_solved}</td>
		</tr>
		<tr>
			<td width="33%" class="tcat">{$lang->applicantstop_beginn}</td>
			<td width="33%" class="tcat">{$lang->applicantstop_art}</td>
			<td width="33%" class="tcat">{$lang->applicantstop_ende}</td>
		</tr>
	{$stop_view}
	</table>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
}

//INSTALLIEREN VOM PLUGIN - liefert true zurück, wenn Plugin installiert. Sonst false
	function applicantstop_is_installed()
	{
		global $db, $mybb;
		
		if ($db->table_exists("applicantstop_entries"))
		{
			return true;
		}
		return false;
	}
	
//DEINSTALLIEREN VOM PLUGIN
	function applicantstop_uninstall()
	{ 
		global $db;
		//Datenbank-Eintrag löschen
		if ($db->table_exists("applicantstop_entries"))
		{
			$db->drop_table("applicantstop_entries");
		}
		
		//Einstellungen deinstallieren:
		$db->query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name='aufnahmestop'"); //Gruppe löschen
		$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='applicantstop_solvedstop'"); //Einzel-Einstellung löschen
		
		rebuild_settings();
		
		//Templates löschen:
		$db->delete_query("templates", "title LIKE '%applicantstop%'");
	
	}
	
//AKTIVIEREN VOM PLUGIN - bspw. variablen einfügen (hier keine vorhanden)
	function applicantstop_activate()
	{
		global $db, $cache;
		require MYBB_ROOT . "/inc/adminfunctions_templates.php";
	
	}
	
//DEAKTIVIEREN VOM PLUGIN - bspw. variablen entfernen (hier keine vorhanden)
	function applicantstop_deactivate()
	{
		global $db, $cache;
		require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
	
	}


// DIE GANZE MAGIE!
// Admin CP konfigurieren - 
	//Action Handler erstellen
	$plugins->add_hook("admin_config_action_handler", "applicantstop_admin_config_action_handler");
	
	function applicantstop_admin_config_action_handler(&$actions)
	{
    	$actions['applicantstop'] = array('active' => 'applicantstop', 'file' => 'applicantstop');
	}

	//ACP Permissions - Berechtigungen für die Admins (über ACP einstellbar)
	$plugins->add_hook("admin_config_permissions", "applicantstop_admin_config_permissions");
	function applicantstop_admin_config_permissions(&$admin_permissions)
	{
	    $admin_permissions['applicantstop'] = "Kann Aufnahmestop verwalten?";
	    return $admin_permissions;
	}
	
	
	//ACP Menüpunkt unter Konfigurationen erstellen
	$plugins->add_hook("admin_config_menu", "applicantstop_admin_config_menu");
	function applicantstop_admin_config_menu(&$sub_menu)
	{
	    $sub_menu[] = [
	        "id" => "applicantstop",
	        "title" => "Aufnahmestop verwalten",
	        "link" => "index.php?module=config-applicantstop"
	    ];
	}

// Aufnahmestops hinzufügen im ACP!
	$plugins->add_hook("admin_load", "applicantstop_manage_applicantstop");
	function applicantstop_manage_applicantstop()
{
	global $mybb, $db, $lang, $page, $run_module, $action_file;
    $lang->load('applicantstop');
	
		if ($page->active_action != 'applicantstop') {
        return false;
    }
       
    if ($run_module == 'config' && $action_file == "applicantstop") {
    
    	//Aufnahmestop Übersicht 
    	if ($mybb->input['action'] == "" || !isset($mybb->input['action'])) {
			// Add a breadcrumb - Navigation Seite 
			$page->add_breadcrumb_item($lang->applicantstop_manage);
    
			//Header Auswahl Felder im Aufnahmestop verwalten Menü hinzufügen
			$page->output_header($lang->applicantstop_manage." - ".$lang->applicantstop_overview);
			
				//Übersichtsseite über alle Stops
				$sub_tabs['applicantstop'] = [
					"title" => $lang->applicantstop_overview_entries,
					"link" => "index.php?module=config-applicantstop",
					"description" => $lang->applicantstop_overview_entries_desc
					];
					
				//Neuen Stop hinterlegen, Button
				$sub_tabs['applicantstop_entry_add'] = [
					"title" => $lang->applicantstop_add_entry,
					"link" => "index.php?module=config-applicantstop&amp;action=add_entry",
					"description" => $lang->applicantstop_add_entry_desc
					];	
					
			$page->output_nav_tabs($sub_tabs, 'applicantstop');
			
			// Zeige Fehler an
		        if (isset($errors)) {
		            $page->output_inline_error($errors);
		        }
		
			//Übersichtsseite erstellen 
			$form = new Form("index.php?module=config-applicantstop", "post");
			
			//Die Überschriften!
			$form_container = new FormContainer("<div style=\"text-align: center;\">$lang->applicantstop_overview_titel_titel</div>");
			//Bezeichnung des Stops
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->applicantstop_overview_titel_name</div>");
			//Beschreibung des Stops
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->applicantstop_overview_titel_desc</div>");
			//Wann wurde der Stop eingetragen
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->applicantstop_overview_titel_start</div>");
			//Wann wurde ist er beendet
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->applicantstop_overview_titel_end</div>");
			//Aktiv oder beendet?
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->applicantstop_overview_titel_activesolved</div>");
			//Optionen
			$form_container->output_row_header($lang->applicantstop_options, array('style' => 'text-align: center; width: 5%;'));
			
			//Alle bisherigen Einträge herbeiziehen und nach Startdatum sortieren
			$query = $db->simple_select("applicantstop_entries", "*", "",
		                ["order_by" => 'startdate', 'order_dir' => 'ASC']);
			
			while($applicantstop_entries = $db->fetch_array($query)) {
				
				//Gestaltung der Übersichtsseite, Infos die angezeigt werden 
				//Stoptitel
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($applicantstop_entries['stoptitel']).'</strong>');
				//Stopbeschreibung
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($applicantstop_entries['stopdesc']).'</strong>');
				
				$applicantstop_entries['startdate'] = date("d.m.Y", $applicantstop_entries['startdate']);
                $applicantstop_entries['enddate'] = date("d.m.Y", $applicantstop_entries['enddate']);

				//Start Datum
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($applicantstop_entries['startdate']).'</strong>');
				//End Datum
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($applicantstop_entries['enddate']).'</strong>');
				
				//Anzeigen ob Stop aktiv oder alt ist ist. Erst mal IF Abfrage, was ausgewählt wurde:
				if ($applicantstop_entries['archiv'] == 0) {
					$altaktuell = "<img src=\"styles/default/images/icons/archiv.png\" alt title=\"Alter Stop\">";
				}
				else {
					$altaktuell = "<img src=\"styles/default/images/icons/aktiv.png\" alt title=\"Aktueller Stop\">";
				}
				//Hier die eigentliche Ausgabe!
				$form_container->output_cell('<center><strong>'.$altaktuell.'</strong></center>');
				
				//Pop Up für Bearbeiten & Löschen
				$popup = new PopupMenu("applicantstop_{$applicantstop_entries['stopid']}", $lang->applicantstop_options);
				$popup->add_item(
		                $lang->applicantstop_edit,
		                "index.php?module=config-applicantstop&amp;action=edit_entry&amp;stopid={$applicantstop_entries['stopid']}"
		        );
		        $popup->add_item(
		                $lang->applicantstop_delete,
		                "index.php?module=config-applicantstop&amp;action=delete_entry&amp;stopid={$applicantstop_entries['stopid']}"
		               ."&amp;my_post_key={$mybb->post_code}"
		        );
		    	$form_container->output_cell($popup->fetch(), array("class" => "align_center"));
		        $form_container->construct_row();
			}
			
				$form_container->end();
		        $form->end();
		        $page->output_footer();
		
		        exit;
    	}
        
         if ($mybb->input['action'] == "add_entry") {
            if ($mybb->request_method == "post") {
            	
                // Prüfen, ob erforderliche Felder nicht leer sind
                if (empty($mybb->input['stoptitel'])) {
                    $errors[] = $lang->applicantstop_error_titel;
                }
                
                if (empty($mybb->input['stopdesc'])) {
                    $errors[] = $lang->applicantstop_error_desc;
                }
                
                if (empty($mybb->input['startdate'])) {
                    $errors[] = $lang->applicantstop_error_start;
                }

                // keine Fehler - dann einfügen
                if (empty($errors)) {
	
					$startdate = strtotime($db->escape_string($mybb->input['startdate']));
                    $enddate = strtotime($db->escape_string($mybb->input['enddate']));
					
                    $new_entry = array(
                        "stopid" => (int)$mybb->input['stopid'],
                        "stoptitel" => $db->escape_string($mybb->input['stoptitel']),
                        "stopdesc" => $db->escape_string($mybb->input['stopdesc']),
						"archiv" => intval($mybb->input['archiv']),
                        "startdate" => $startdate,
                        "enddate" => $enddate
                    );

                    $db->insert_query("applicantstop_entries", $new_entry);

                    $mybb->input['module'] = "applicantstop";
                    $mybb->input['action'] = $lang->applicantstop_entry_solved;
                    log_admin_action(htmlspecialchars_uni($mybb->input['stoptitel']));

                    flash_message($lang->applicantstop_entry_solved, 'success');
                    admin_redirect("index.php?module=config-applicantstop");
                }
            }

                $page->add_breadcrumb_item($lang->applicantstop_add);

                // Editor scripts
                $page->extra_header .= <<<EOF
                
<link rel="stylesheet" href="../jscripts/sceditor/editor_themes/mybb.css" type="text/css" media="all" />
<script type="text/javascript" src="../jscripts/sceditor/jquery.sceditor.bbcode.min.js?ver=1805"></script>
<script type="text/javascript" src="../jscripts/bbcodes_sceditor.js?ver=1808"></script>
<script type="text/javascript" src="../jscripts/sceditor/editor_plugins/undo.js?ver=1805"></script>
EOF;

                // Build options header
                $page->output_header($lang->applicantstop_manage." - ".$lang->applicantstop_overview);

                //Übersichtsseite über alle Stops
				$sub_tabs['applicantstop'] = [
					"title" => $lang->applicantstop_overview_entries,
					"link" => "index.php?module=config-applicantstop",
					"description" => $lang->applicantstop_overview_entries_desc
					];
					
				//Neuen Stop hinterlegen, Button
				$sub_tabs['applicantstop_entry_add'] = [
					"title" => $lang->applicantstop_add_entry,
					"link" => "index.php?module=config-applicantstop&amp;action=add_entry",
					"description" => $lang->applicantstop_add_entry_desc
					];

                $page->output_nav_tabs($sub_tabs, 'applicantstop_entry_add'); 

                // Show errors
                if (isset($errors)) {
                    $page->output_inline_error($errors);
                }

                // Erstellen der "Formulareinträge"
                $form = new Form("index.php?module=config-applicantstop&amp;action=add_entry", "post", "", 1);
                $form_container = new FormContainer($lang->applicantstop_add);
                
                $form_container->output_row(
                    $lang->applicantstop_form_titel."<em>*</em>",
                    $lang->applicantstop_form_titel_desc,
                    $form->generate_text_box('stoptitel', $mybb->input['stoptitel'])
                );
               
                $text_editor = $form->generate_text_area('stopdesc', $mybb->input['stopdesc'], array(
                    'id' => 'stopdesc',
                    'rows' => '25',
                    'cols' => '70',
                    'style' => 'height: 150px; width: 75%'
                    )
                 );
 
                 $text_editor .= build_mycode_inserter('stopdesc');
                 $form_container->output_row(
                     $lang->applicantstop_form_desc. "<em>*</em>",
                     $lang->applicantstop_form_desc_desc,
                     $text_editor,
                     'text'
                 );                
               
                $form_container->output_row(
                    $lang->applicantstop_form_start. "<em>*</em>",
                    $lang->applicantstop_form_start_desc,
                    $form->generate_text_box('startdate', $mybb->input['startdate'])
                );
			 
			 	$form_container->output_row(
                    $lang->applicantstop_form_end,
                    $lang->applicantstop_form_end_desc,
                    $form->generate_text_box('enddate', $mybb->input['enddate'])
                );
 
			 	$form_container->output_row(
				$lang->applicantstop_form_radio."<em>*</em>", //Aktueller Stop?
				$lang->applicantstop_form_radio_desc,
				$form->generate_yes_no_radio('archiv', $mybb->get_input('archiv'))
				);		

                $form_container->end();
                $buttons[] = $form->generate_submit_button($lang->applicantstop_send);
                $form->output_submit_wrapper($buttons);
                $form->end();
                $page->output_footer();
    
                exit;         
        }
        
        
        if ($mybb->input['action'] == "edit_entry") {
            if ($mybb->request_method == "post") {
            	
            	
                // Prüfen, ob erforderliche Felder nicht leer sind
                if (empty($mybb->input['stoptitel'])) {
                    $errors[] = $lang->applicantstop_error_titel;
                }
                
                if (empty($mybb->input['stopdesc'])) {
                    $errors[] = $lang->applicantstop_error_desc;
                }
                
                if (empty($mybb->input['startdate'])) {
                    $errors[] = $lang->applicantstop_error_start;
                }

                // No errors - insert the terms of use
                if (empty($errors)) {
                    $stopid = $mybb->get_input('stopid', MyBB::INPUT_INT);

					$startdate = strtotime($db->escape_string($mybb->input['startdate']));
                    $enddate = strtotime($db->escape_string($mybb->input['enddate']));
					
                    $edited_entry = [
                        "stopid" => (int)$mybb->input['stopid'],
                        "stoptitel" => $db->escape_string($mybb->input['stoptitel']),
                        "stopdesc" => $db->escape_string($mybb->input['stopdesc']),
						"archiv" => intval($mybb->input['archiv']),
                        "startdate" => $startdate,
                        "enddate" => $enddate
                    ];

                    $db->update_query("applicantstop_entries", $edited_entry, "stopid='{$stopid}'");

                    $mybb->input['module'] = "applicantstop";
                    $mybb->input['action'] = $lang->applicantstop_edit_entry_solved;
                    log_admin_action(htmlspecialchars_uni($mybb->input['stoptitel']));

                    flash_message($lang->applicantstop_edit_entry_solved, 'success');
                    admin_redirect("index.php?module=config-applicantstop");
                }

            }
            
            $page->add_breadcrumb_item($lang->applicantstop_edit_entry);

            // Editor scripts
            $page->extra_header .= <<<EOF
<link rel="stylesheet" href="../jscripts/sceditor/editor_themes/mybb.css" type="text/css" media="all" />
<script type="text/javascript" src="../jscripts/sceditor/jquery.sceditor.bbcode.min.js?ver=1805"></script>
<script type="text/javascript" src="../jscripts/bbcodes_sceditor.js?ver=1808"></script>
<script type="text/javascript" src="../jscripts/sceditor/editor_plugins/undo.js?ver=1805"></script>
EOF;

            // Build options header
            $page->output_header($lang->applicantstop_manage." - ".$lang->applicantstop_overview);
            
            $sub_tabs['applicantstop'] = [
                "title" => "Aufnahmestop Übersicht",
                 "link" => "index.php?module=config-applicantstop",
                "description" => applicantstop_overview
            ];
            
            $sub_tabs['applicantstop_entry_add'] = [
                "title" => "Aufnahmestop hinzufügen",
                "link" => "index.php?module=config-applicantstop&amp;action=add_entry",
                "description" => applicantstop_add_entry_desc
            ];
            $sub_tabs['applicantstop_entry_edit'] = [
                "title" => "Aufnahmestop Bearbeiten",
                "link" => "index.php?module=config-applicantstop&amp;action=edit_entry",
                "description" => $lang->applicantstop_edit_entry_desc
            ];
            
            
            $page->output_nav_tabs($sub_tabs, 'applicantstop_entry_edit'); 

            // Show errors
            if (isset($errors)) {
                $page->output_inline_error($errors);
            }

            // Get the data
            $stopid = $mybb->get_input('stopid', MyBB::INPUT_INT);
            $query = $db->simple_select("applicantstop_entries", "*", "stopid={$stopid}");
            $edit_entry = $db->fetch_array($query);

            // Erstellen des "Formulars"
            $form = new Form("index.php?module=config-applicantstop&amp;action=edit_entry", "post", "", 1);
            echo $form->generate_hidden_field('stopid', $stopid);

            $form_container = new FormContainer($lang->applicantstop_edit_entry);
            
            $form_container->output_row(
                $lang->applicantstop_form_titel . "<em>*</em>",
                $lang->applicantstop_form_titel_desc,
                $form->generate_text_box('stoptitel', htmlspecialchars_uni($edit_entry['stoptitel']))
            );

            $text_editor = $form->generate_text_area('stopdesc', htmlspecialchars_uni($edit_entry['stopdesc']), array(
                'id' => 'stopdesc'. "<em>*</em>",
                'rows' => '25',
                'cols' => '70',
                'style' => 'height: 150px; width: 75%'
                )
             );

             $text_editor .= build_mycode_inserter('stopdesc');
             $form_container->output_row(
                 $lang->applicantstop_form_desc . "<em>*</em>",
                 $lang->applicantstop_form_desc_desc,
                 $text_editor,
                 'stopdesc'
             );           
			
			$edit_entry['startdate'] = date("d.m.Y", $edit_entry['startdate']);
            $edit_entry['enddate'] = date("d.m.Y", htmlspecialchars_uni($edit_entry['enddate']));
                
            $form_container->output_row(
               $lang->applicantstop_form_start . "<em>*</em>",
                $lang->applicantstop_form_start_desc,
                $form->generate_text_box('startdate', $edit_entry['startdate'])
            );
               
            $form_container->output_row(
                $lang->applicantstop_form_end,
                $lang->applicantstop_form_end_desc,
                 $form->generate_text_box('enddate', $edit_entry['enddate'])
            );
                
			$form_container->output_row(
              	$lang->applicantstop_form_radio."<em>*</em>", 
                $lang->applicantstop_form_radio_desc,
                $form->generate_yes_no_radio('archiv', $edit_entry['archiv'])
            );
 
            $form_container->end();
            $buttons[] = $form->generate_submit_button($lang->applicantstop_send);
            $form->output_submit_wrapper($buttons);
            $form->end();
            $page->output_footer();

            exit;
        }
       // Lösche den Stop
       if ($mybb->input['action'] == "delete_entry") {
       	
            // Get data
            $stopid = $mybb->get_input('stopid', MyBB::INPUT_INT);
            $query = $db->simple_select("applicantstop_entries", "*", "stopid={$stopid}");
            $del_entry = $db->fetch_array($query);

            // Error Handling
            if (empty($stopid)) {
                flash_message($lang->applicantstop_error_option, 'error');
                admin_redirect("index.php?module=config-applicantstop");
            }

            // Cancel button pressed?
            if (isset($mybb->input['no']) && $mybb->input['no']) {
                admin_redirect("index.php?module=config-applicantstop");
            }

            if (!verify_post_check($mybb->input['my_post_key'])) {
                flash_message($lang->invalid_post_verify_key2, 'error');
                admin_redirect("index.php?module=config-applicantstop");
            }  
            
		   
            // Wenn alles okay ist
            else {
                if ($mybb->request_method == "post") {
                    
                    $db->delete_query("applicantstop_entries", "stopid='{$stopid}'");

                    $mybb->input['module'] = "applicantstop";
                    $mybb->input['action'] = $lang->applicantstop_delete_entry_solved;
                    log_admin_action(htmlspecialchars_uni($del_entry['stoptitel']));

                    flash_message($lang->applicantstop_delete_entry_solved, 'success');
                    admin_redirect("index.php?module=config-applicantstop");
                } 
                
                else {
					
                    $page->output_confirm_action(
                        "index.php?module=config-applicantstop&amp;action=delete_entry&amp;stopid={$stopid}",
						$lang->applicantstop_delete_entry_question
                    );
                }
            }
            exit;
        }
        
}
}

