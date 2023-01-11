<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'applicantstop.php');

require_once "./global.php";
$lang->load('applicantstop');

// EINSTELLUNG
$applicantstop_solvedstop = $mybb->settings['applicantstop_solvedstop'];

//HAUPTSEITE
if (!$mybb->input['action'] == "applicantstop")
{
    //Navigation bauen
    add_breadcrumb("Aufnahmestop", "applicantstop.php");
    
    // Aktuelle Aufnahmestops
    $sql_active = "SELECT * FROM " . TABLE_PREFIX . "applicantstop_entries 
    WHERE archiv = 1";
    $query_active = $db->query($sql_active);
    
    while ($active = $db->fetch_array($query_active)){

        // LEER LAUFEN LASSEN, SONST STEHEN INHALTE VON VORHER DRINNEN VOR ALLEM BEI PHP 8 BEACHTEN!!!
        $startdate = "";
        $enddate = "";
        $stoptitel = "";
        $stopdesc = "";
		
		// Wenn kein Enddatum eingetragen ist => noch offen
        if (!empty($active['enddate'])){
            $enddate = date("d.m.Y", $active['enddate']);
        } else {
            $enddate = "<span style='color:green;'>$lang->applicantstop_active</span>";	
        }

        // MIT INFOS FÜLLEN
        $startdate = date("d.m.Y", $active['startdate']); // umwandlung von timestamp in Datum
        $stoptitel = $active['stoptitel']; //Benennung des Titels
        $stopdesc = $active['stopdesc']; //Beschreibung hinzufügen


        eval("\$stop_view .= \"" . $templates->get("applicantstop_view") . "\";"); //Template für die Einzelausgabe

        eval("\$aktiven = \"" . $templates->get("applicantstop_active") . "\";"); //Template für die aktiven
    } 

    //Wenn = 1 dann heißt: beendete sollen angezeigt werden. 
    //1 ist das was er sich aus der Einstellung zum Plugin zieht (im ACP). 
    if ($applicantstop_solvedstop == 1) {
   
        // beendete Aufnahmestops
        $sql_archiv = "SELECT * FROM " . TABLE_PREFIX . "applicantstop_entries 
        WHERE archiv = 0";   
        $query_archiv = $db->query($sql_archiv);

        // Leer laufen lassen, damit es nicht an die aktiven gehängt wird
        $stop_view = "";   
        while ($archiv = $db->fetch_array($query_archiv)){

            // LEER LAUFEN LASSEN, SONST STEHEN INHALTE VON VORHER DRINNEN VOR ALLEM BEI PHP 8 BEACHTEN!!!
            $startdate = "";
            $enddate = "";
            $stoptitel = "";   
            $stopdesc = "";
			
			// Wenn kein Enddatum eingetragen ist => noch offen
        	if (!empty($archiv['enddate'])){
            $enddate = date("d.m.Y", $archiv['enddate']);
        	} else {
            $enddate = "<span style='color:green;'>$lang->applicantstop_active</span>";	
        	}

            // MIT INFOS FÜLLEN
            $startdate = date("d.m.Y", $archiv['startdate']); // umwandlung von timestamp in Datum
            $stoptitel = $archiv['stoptitel']; //Benennung des Titels
            $stopdesc = $archiv['stopdesc']; //Beschreibung hinzufügen
  
   
            eval("\$stop_view .= \"" . $templates->get("applicantstop_view") . "\";"); //Template für die Einzelausgabe

            eval("\$alten = \"" . $templates->get("applicantstop_solved") . "\";"); //Template für die erledigten
        } 
   
    }

    eval('$page = "'.$templates->get('applicantstop_main').'";'); //Template der Hauptseite
    output_page($page);
}



?>
