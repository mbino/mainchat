<?php

function zeige_moderations_antworten($o_raum, $answer = "")
{
    global $t;
    global $id;
    global $dbase, $conn;
    global $u_id;
    global $http_host;
    global $farbe_tabelle_zeile1;
    global $farbe_tabelle_zeile2;
    global $farbe_moderationr_zeile1;
    global $farbe_moderationr_zeile2;
    global $farbe_moderationg_zeile1;
    global $farbe_moderationg_zeile2;
    
    $query = "SELECT c_id,c_text FROM moderation WHERE c_raum=" . intval($o_raum) . " AND c_typ='P' ORDER BY c_text";
    $result = mysql_query($query, $conn);
    echo "<table width=100% border=0 cellpadding=0 cellspacing=0>";
    if ($result > 0) {
        $i = 0;
        while ($row = mysql_fetch_object($result)) {
            $i++;
            if ($i % 2 == 0) {
                echo "<tr bgcolor=$farbe_tabelle_zeile1>";
                $g = $farbe_moderationg_zeile1;
                $r = $farbe_moderationr_zeile1;
            } else {
                echo "<tr bgcolor=$farbe_tabelle_zeile2>";
                $g = $farbe_moderationg_zeile2;
                $r = $farbe_moderationr_zeile2;
            }
            echo "<td align=left><small>";
            echo "<A HREF=\"#\" onclick=\"opener.parent.frames['schreibe'].location='schreibe.php?http_host=$http_host&id=$id&text=";
            echo urlencode($row->c_text);
            echo "'; return(false);\">";
            echo "$row->c_text</a></small></td><td align=right><small>";
            echo "<a href=moderator.php?id=$id&http_host=$http_host&mode=answeredit&answer=$row->c_id>$t[mod12]</a> ";
            echo "<a href=moderator.php?id=$id&http_host=$http_host&mode=answerdel&answer=$row->c_id>$t[mod13]</a> ";
            echo "</small></td>";
            echo "</tr>";
        }
        mysql_free_result($result);
    }
    echo "</table>";
    echo "<br><center>";
    echo "<form>";
    echo "<font>" . $t['mod11'] . "</font><br>";
    echo "<input type=hidden name=id value=$id>";
    echo "<input type=hidden name=http_host value=$http_host>";
    echo "<input type=hidden name=mode value=answernew>";
    if ($answer != "")
        echo "<input type=hidden name=answer value=$answer>";
    echo "<textarea name=answertxt rows=5 cols=60>";
    if ($answer != "") {
        $answer = intval($answer);
        $query = "SELECT c_id,c_text FROM moderation WHERE c_id=$answer AND c_typ='P'";
        $result = mysql_query($query, $conn);
        if ($result > 0) {
            echo mysql_result($result, 0, "c_text");
        }
        mysql_free_result($result);
    }
    echo "</textarea>";
    echo "<br><input type=submit value=\"$t[mod1]\">";
    echo "</form>";
    echo "<a href=# onclick=window.close();>" . $t['mod10'] . "</a></center>";
}

function bearbeite_moderationstexte($o_raum)
{
    global $t;
    global $id;
    global $dbase, $conn;
    global $action;
    global $u_id;
    global $system_farbe;
    
    if (is_array($action)) {
        echo "<font><small>";
        $a = 0;
        reset($action);
        // erst mal die Datensätze reservieren...
        while ($a < count($action)) {
            $key = key($action);
            // nur markieren, was noch frei ist.
            $query = "UPDATE moderation SET c_moderator=$u_id WHERE c_id=" . intval($key) . " AND c_typ='N' AND c_moderator=0";
            $result = mysql_query($query, $conn);
            next($action);
            $a++;
        }
        // jetzt die reservierten Aktionen bearbeiten.
        $a = 0;
        reset($action);
        while ($a < count($action)) {
            $key = key($action);
            // nur auswählen, was bereits von diesem Moderator reserviert ist
            $query = "SELECT * FROM moderation WHERE c_id=" . intval($key) . " AND c_typ='N' AND c_moderator=$u_id";
            $result = mysql_query($query, $conn);
            if ($result > 0) {
                if (mysql_num_rows($result) > 0) {
                    $f = mysql_fetch_array($result);
                    switch ($action[$key]) {
                        case "ok":
                        case "clear":
                        case "thru":
                        // vorbereiten für umspeichern... geht leider nicht 1:1, 
                        // weil fetch_array mehr zurückliefert als in $f[] sein darf...
                            $c['c_von_user'] = $f['c_von_user'];
                            $c['c_an_user'] = $f['c_an_user'];
                            $c['c_typ'] = $f['c_typ'];
                            $c['c_raum'] = $f['c_raum'];
                            $c['c_text'] = $f['c_text'];
                            $c['c_farbe'] = $f['c_farbe'];
                            $c['c_zeit'] = $f['c_zeit'];
                            $c['c_von_user_id'] = $f['c_von_user_id'];
                            if ($action[$key] == "ok") {
                                // eigene ID vermerken
                                $c['c_moderator'] = $u_id;
                                // Zeit löschen, damit markierte oben erscheint...
                                unset($c['c_zeit']);
                            } else {
                                // freigeben -> id=0 schreiben
                                $c['c_moderator'] = 0;
                            }
                            // und in moderations-tabelle schreiben
                            if ($action[$key] == "thru") {
                                unset($c['c_moderator']);
                                schreibe_chat($c);
                            } else {
                                schreibe_moderiert($c);
                            }
                            break;
                        case "notagain":
                            system_msg("", 0, $f['c_von_user_id'],
                                $system_farbe, $t['moderiertdel1']);
                            system_msg("", 0, $f['c_von_user_id'],
                                $system_farbe, "&gt;&gt;&gt; " . $f['c_text']);
                            break;
                        case "better":
                            system_msg("", 0, $f['c_von_user_id'],
                                $system_farbe, $t['moderiertdel2']);
                            system_msg("", 0, $f['c_von_user_id'],
                                $system_farbe, "&gt;&gt;&gt; " . $f['c_text']);
                            break;
                        case "notime":
                            system_msg("", 0, $f['c_von_user_id'],
                                $system_farbe, $t['moderiertdel3']);
                            system_msg("", 0, $f['c_von_user_id'],
                                $system_farbe, "&gt;&gt;&gt; " . $f['c_text']);
                            break;
                        case "delete":
                            system_msg("", 0, $f['c_von_user_id'],
                                $system_farbe, $t['moderiertdel4']);
                            system_msg("", 0, $f['c_von_user_id'],
                                $system_farbe, "&gt;&gt;&gt; " . $f['c_text']);
                            break;
                    }
                    // jetzt noch aus moderierter Tabelle löschen.
                    mysql_free_result($result);
                    $query = "DELETE FROM moderation WHERE c_id=" . intval($key) . " AND c_moderator=$u_id";
                    $result2 = mysql_query($query, $conn);
                } else {
                    echo "$t[mod9]<br>";
                }
            }
            next($action);
            $a++;
        }
        echo "</small></font>";
    }
}

function zeige_moderationstexte($o_raum, $limit = 20)
{
    global $t;
    global $farbe_tabelle_kopf;
    global $farbe_tabelle_zeile1;
    global $farbe_tabelle_zeile2;
    global $farbe_moderationr_zeile1;
    global $farbe_moderationr_zeile2;
    global $farbe_moderationg_zeile1;
    global $farbe_moderationg_zeile2;
    global $http_host;
    global $id;
    global $dbase, $conn;
    global $action;
    global $moderation_rueckwaerts;
    global $moderation_grau;
    global $moderation_schwarz;
    global $moderationsexpire;
    global $u_id;
    global $o_js;
    
    // gegen DAU-Eingaben sichern...
    $limit = max(intval($limit), 20);
    // erst mal alle alten Msgs expiren...
    if ($moderationsexpire == 0)
        $moderationsexpire = 30;
    $expiretime = $moderationsexpire * 60;
    $query = "DELETE from moderation WHERE unix_timestamp(c_zeit)+$expiretime<unix_timestamp(NOW()) AND c_moderator=0 AND c_typ='N'";
    $result = mysql_query($query, $conn);
    
    if ($moderation_rueckwaerts == 1)
        $rev = " DESC";
    $query = "SELECT c_id,c_text,c_von_user,c_moderator FROM moderation WHERE c_raum=" . intval($o_raum) . " AND c_typ='N' ORDER BY c_id $rev LIMIT 0, " . intval($limit);
    $result = mysql_query($query, $conn);
    $i = 0;
    $rows = 0;
    if ($result > 0) {
        $rows = mysql_num_rows($result);
        if ($o_js) {
            echo "<SCRIPT LANGUAGE=Javascript>\n";
            echo "function sel() {\n";
            echo "	document.forms['modtext'].elements['ok'].focus();\n";
            echo "}\n";
            echo "</SCRIPT>\n";
        }
        echo "<form name=modtext action=\"moderator.php?http_host=$http_host&id=$id\" method=\"post\">\n";
        if ($rows > 0) {
            
            echo "<table width=100% cellpadding=0 cellspacing=0 border=0>\n";
            echo "<tr bgcolor=$farbe_tabelle_kopf>";
            echo "<td align=center valign=bottom><img src=\"pics/ok.gif\" height=20 width=20 alt=\""
                . $t['mod16'] . "\"></td>";
            echo "<td align=center valign=bottom><img src=\"pics/nope.gif\" height=20 width=20 alt=\""
                . $t['mod17'] . "\"></td>";
            echo "<td valign=bottom>";
            echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td>";
            echo "<small><b>" . $t['mod2'];
            echo "</td><td align=right>";
            echo "<input type=submit name=ok2 value=\"go!\">\n";
            echo "</b></small>";
            echo "</td></tr></table>";
            echo "</td>";
            echo "<td align=center valign=bottom><img src=\"pics/ok.gif\" height=20 width=20 alt=\""
                . $t['mod14'] . "\"></td>";
            echo "<td align=center valign=bottom><img src=\"pics/wdh.gif\" height=20 width=20 alt=\""
                . $t['mod3'] . "\"></td>";
            echo "<td align=center valign=bottom><img src=\"pics/smile.gif\" height=20 width=20 alt=\""
                . $t['mod4'] . "\"></td>";
            echo "<td align=center valign=bottom><img src=\"pics/time.gif\" height=20 width=20 alt=\""
                . $t['mod5'] . "\"></td>";
            echo "<td align=center valign=bottom><img src=\"pics/nope.gif\" height=20 width=20 alt=\""
                . $t['mod15'] . "\"></td>";
            echo "</tr>\n";
            
            while ($row = mysql_fetch_object($result)) {
                $i++;
                if ($i % 2 == 0) {
                    echo "<tr bgcolor=$farbe_tabelle_zeile1>";
                    $g = $farbe_moderationg_zeile1;
                    $r = $farbe_moderationr_zeile1;
                } else {
                    echo "<tr bgcolor=$farbe_tabelle_zeile2>";
                    $g = $farbe_moderationg_zeile2;
                    $r = $farbe_moderationr_zeile2;
                }
                echo "<td align=center bgcolor=$g><small>";
                if ($row->c_moderator == $u_id || $row->c_moderator == 0) {
                    echo "<input type=radio name=action[$row->c_id] value='ok' onclick=submit();";
                    if ($row->c_moderator == $u_id)
                        echo " checked";
                    echo ">";
                    $tc = $moderation_schwarz;
                } else {
                    echo "&nbsp";
                    $tc = $moderation_grau;
                }
                echo "</small></td>";
                echo "<td align=center bgcolor=$g><small>";
                if ($row->c_moderator == $u_id) {
                    echo "<input type=radio name=action[$row->c_id] value='clear' onclick=submit();>";
                    $b1 = "<b>";
                    $b2 = "</b>";
                } else {
                    $b1 = "";
                    $b2 = "";
                }
                echo "&nbsp;</small></td>";
                echo "<td width=100%><small><font color=$tc>$b1$row->c_von_user: $row->c_text$b2</font></small></td>";
                if ($row->c_moderator == $u_id || $row->c_moderator == 0) {
                    echo "<td align=center bgcolor=$g><small><input type=radio name=action[$row->c_id] value='thru';></small></td>";
                    echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='notagain';></small></td>";
                    echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='better';></small></td>";
                    echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='notime';></small></td>";
                    echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='delete';></small></td>";
                } else {
                    echo "<td bgcolor=$g><small>&nbsp;</small></td>";
                    echo "<td bgcolor=$r><small>&nbsp;</small></td>";
                    echo "<td bgcolor=$r><small>&nbsp;</small></td>";
                    echo "<td bgcolor=$r><small>&nbsp;</small></td>";
                    echo "<td bgcolor=$r><small>&nbsp;</small></td>";
                }
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
        echo "<center>";
        echo "<input type=text name=limit value=$limit size=5>\n";
        echo "<input type=submit name=ok value=" . $t['mod_ok'] . ">\n";
        echo "</form>\n";
    }
    return $rows;
}

function anzahl_moderationstexte($o_raum)
{
    global $http_host;
    global $id;
    global $dbase, $conn;
    
    $query = "SELECT c_id FROM moderation WHERE c_raum=" . intval($o_raum) . " AND c_typ='N' ORDER BY c_id";
    $result = mysql_query($query, $conn);
    if ($result > 0) {
        $rows = mysql_num_rows($result);
    }
    ;
    return $rows;
}

?>
