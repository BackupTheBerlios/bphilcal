<?php

/**
 *
 * A tiny script to retrieve a personalised concert calendar of the
 * Berlin Philharmonic Orchestra (Sir Simon Rattle)
 *
 * V1.37	02.07.2005	first release
 * V1.01	18.06.2005	initial version
 *
 * Copyright (C) 2005 Thomas Gries <mail@tgries.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 */

	$progname = 'BPhilCal';
	$version = '1.37 02.07.2005';
	$lastvalue='';

	function fetchData($fi) {
		$fp = fopen( $fi, "r");
		$fl = filesize ($fi);
		$data = fread($fp, $fl);
		fclose($fp);
		return $data;
	}

	function readCounter($fi) {
		$fp = fopen( $fi, "r");
		$fl = filesize ($fi);
		$data = fread($fp, $fl);
		fclose($fp);
		return $data;
	}

	function writeCounter($fi, $data) {
		$fp = fopen( $fi, "w");
		fwrite($fp, $data);
		fclose($fp);
		return;
	}

	function day2( $day ) {
		return ($day <= 9) ? '0'.$day : $day;
	}

	function changeDateToISO( &$date ) {
		$month = array( 'Jan' => '01', 'Feb' => '02', 'M?r' => '03', 'Mar' => '03',
				'Apr' => '04', 'Mai' => '05', 'May' => '05', 'Jun' => '06',
				'Jul' => '07', 'Aug' => '08', 'Sep' => '09', 'Okt' => '10',
				'Oct' => '10', 'Nov' => '11', 'Dez' => '12', 'Dec' => '12');
		foreach ($month as $monthstring => $monthnumber) {
			if ( preg_match("!([0-9]{1,2})\..*$monthstring.*([0-9]{4})!iU", $date, $res) ) {
				$date = day2($res[1]).'.'.$monthnumber.'.'.$res[2];
				return $res[2].$monthnumber.day2($res[1]);
			}
		}
		$date = '01.01.1970';
		return '19700101';
	}

	function make_entry(&$mem, &$entry) {
	global $place,$timeinfo,$artist,$conductor,$soloists,$title,$pieces,$introduction,$managerlink;
	global $time,$date,$serie_uc,$concertnumber,$lang;
	global $bpobase,$bpoabourl,$bposaalurl,$seriesrange_lower,$short,$uhr;
	global $doubl;

		$dummy = preg_match("/([0-9]{1,2}\..*[^0-9]* [0-9]{4}) *([0-9:.]*) *(Uhr|h) *({$seriesrange_lower}[0-9])/iU", $timeinfo, $res2);
		$date = $res2[1];
		$time = $res2[2];
		$concertnumber = $res2[4];
		$serie_uc = strtoupper(substr($concertnumber, 0, 1));

		/* mark concert times which differ from 20:00 b underlining them */
		if ( $time <> '20' ) $time = "<u>$time</u>";

		$ISOdate = changeDateToISO($date);

		$bpodayurl = "$bpobase/kalender/$ISOdate/day/all/";
		if ($soloists != '') $soloists = $soloists.'<br>';
		if ($title != '') $title = $title.'<br>';
		if ($introduction != '') $introduction = '<br>'.$introduction.'<br>';
		if ($managerlink != '') $managerlink = $managerlink.'<br>';


		$entryshort = $pieces;
		$match = array_search( $entryshort, $doubl );
		if ($match !== false) {
			$conflict = "<b><font color=\"red\"> x $match</font></b>";
			$startdiv = '<font color="red">';
			$enddiv = '</font>';
			$td = "<td>$startdiv";
		}
		else {
			$conflict='';
			$startdiv = '';
			$enddiv = '';
			$td = '<td>';
		}
		$dup = array($concertnumber => $entryshort);
		$doubl = array_merge($doubl, $dup);


		switch ($short) {
		case 1: $entry = "$ISOdate $startdiv<a href=\"$bpodayurl\" title=\"Philharmonie Tagesprogramm/View all concerts this day\">$date</a>
			$time $uhr
			<a href=\"$bposaalurl\">$place</a>
			<a href=\"$bpoabourl\" title=\"Abonnement Serie $serie_uc &Uuml;bersicht/Subscription series $serie_uc overview\">$concertnumber</a>$conflict
			$artist
			$conductor<br>
			$pieces$enddiv";
			/*preg_replace("!<br/>!", "; ", $pieces);*/
			break;
		case 0: $entry = "$ISOdate <tr>$td<a href=\"$bpodayurl\" title=\"Philharmonie Tagesprogramm/View all concerts this day\">$date</a></td>
			$td$time $uhr</td>
			$td<a href=\"$bposaalurl\">$place</a></td>
			$td<a href=\"$bpoabourl\" title=\"Abonnement Serie $serie_uc &Uuml;bersicht/Subscription series $serie_uc overview\">$concertnumber</a></td>
			$td$conflict</td>
			$td$artist</td>$td";

			$more='&nbsp;';
			while ( preg_match("!<b>(.*)</b>(.*)!", $conductor, $res) ) {
				$conductor = preg_replace("!<b>{$res[1]}</b>!U", "", $conductor, 1);
				$cond=$res[1];
				if (substr($cond,0,3) == "Sir") $cond= substr($cond,4);
				$entry .= $more."<a href=\"http://$lang.wikipedia.org/wiki/$cond\">".$res[1]."</a>";
				$more='; ';
			}
			$entry .= '</td>';
			$more=$td;
			while ( preg_match("!<b>(.*)</b>!U", $pieces, $res) ) {
				$pieces = preg_replace("!<b>{$res[1]}</b>!U", "", $pieces, 1);
				$entry .= $more."<a href=\"http://$lang.wikipedia.org/wiki/$res[1]\">".$res[1]."</a>";
				$more='; ';
			}
			$entry .= '</td></tr>';
			break;
		default: $entry= "$ISOdate $startdiv<a href=\"$bpodayurl\" title=\"Philharmonie Tagesprogramm/View all concerts this day\">$date</a>
			$time $uhr
			<a href=\"$bposaalurl\">$place</a>
			<a href=\"$bpoabourl\" title=\"Abonnement Serie $serie_uc &Uuml;bersicht/Subscription series $serie_uc overview\">$concertnumber</a>$conflict
			$artist
			$conductor<br>
			$title
			$soloists
			$pieces
			$introduction
			$managerlink$enddiv";
		}


		array_push($mem, $entry);

	}

	function clear_entry( &$entry) {
	global $place,$timeinfo,$artist,$conductor,$soloists,$title,$pieces,$introduction,$managerlink;
		$place = $timeinfo = $artist = $conductor = $soloists = $title = $pieces = $introduction = $managerlink = '';
	}

	function backlink() {
	global $scriptbaseurl,$series;
		return "<a href=\"$scriptbaseurl?series=$series&show=0\">&rarr; Neustart und zur&uuml;ck zur Hauptseite</a><br>";
	}

	function footer( $back = false) {
	global $counter;
		$backlink = ($back) ? backlink() : '';
		echo "<br>
			$backlink&rarr; zur offiziellen <a href=\"$bpo\">Webseite der Philharmonie</a><br>
			<table width=100%><tr>
			<td width=33%>Alle Angaben ohne Gew&auml;hr.<p>
			$counter visitors since 19.06.2005<br>
			&copy;Wikinaut <a href=\"http://www.disclaimer.de/disclaimer.htm?farbe=FFFFFF/000000/000000/000000\">Disclaimer/Haftungsausschluss <a href=\"http://www.tgries.de/impressum/index.html\">Impressum</a></td>
			<td width=34% align=\"center\"><img src=\"http://www.opteryx.de/opteryx_B100.jpg\" align=\"top\">&reg;<p>
				Opteryx&reg; creative webdesign 2005</td>
			<td width=33% align=\"right\">
					<a href=\"http://sourceforge.net\">
					<img src=\"http://sourceforge.net/sflogo.php?group_id=141854&amp;type=4\"
					 style=\"border: 0px solid ; width: 125px; height: 37px;\"
					 alt=\"SourceForge.net Logo\" title=\"\" align=\"top\"></a><p>
					<a href=\"http://sourceforge.net/projects/bphilcal\">BPhilCal project page</a>

			</td></table>";
	}

$saison_default = '2005.2006';
$lang_default = 'de';
$font='<font size=-2>';
$seriesrange_lower='[a-ik-x]';
$bpo = "http://www.berliner-philharmoniker.de/";
$verbose=0;

$scriptbaseurl="/bphilcal";
$counterfile = '/home/www/bphilcal/data/index.cnt';

$items = array(	'place', 'timeinfo', 'artist', 'conductor', 'soloists', 'title', 'pieces', 'managerlink', 'introduction' );
$mem = array();
$doubl = array();

header('Content-type: text/html; charset=iso-8859-1');
$qs = strtolower( $_SERVER['QUERY_STRING'] );

$counter = readCounter($counterfile);

/* echo "<b>QUERY_STRING='$qs'</b><hr>"; */
$error = false;


/* read the GET variables */

if (isset($_GET['show'])) {
	$input = trim($_GET['show']);
} else $show=1;

if (isset($_GET['lang'])) {
	$lang = strtolower(trim($_GET['lang']));
} else {
	$lang = $lang_default;
}

$series = strtolower(trim($_GET['series']));

if (isset($_GET['saison'])) {
	$saison = strtolower(trim($_GET['saison']));
} else {
	$saison = $saison_default;
}

$short = strtolower(trim($_GET['short']));

if (preg_match("/(..)/", $lang, $res) ) {
	if (($res[1] == 'de') || ( $res[1] == 'en' )) $lang = $res[1];
	else {
		$langerror = '<p><b><font color="red">Falsche Angabe: Diese Sprache wird nicht unterst&uuml;tzt.</font></b><br>';
		$lang='';
		$error = true;
	}
} else {
	if ($res[1]=='') $lang = $lang_default;
}


$uhr = ($lang == 'de') ? 'Uhr' : 'h';

if ( preg_match("/([012])/", $short, $res) ) {
	$short = $res[1];

} else {
	$short = 0;
}

if ( preg_match("/(2004\.2005|2005\.2006)/", $saison, $res) ) {
	$saison = $res[1];
} else {
	$saison = $saison_default;
	if ($qs != '') {
		$saisonerror = '<p><b><font color="red">Falsche Saison. Daten f&uuml;r diese Saison sind nicht vorhanden.</font></b><br>';
		$error = true;
	}
}

/*	this allows arbitrary separators between the letters,
	the separators are filtered out */

$series = eregi_replace('[^A-Z]','', $series);

preg_match("/({$seriesrange_lower}*)/", $series, $res);
$series=$res[1];

if ( !$error && ($show == '1') && ( $series !='' ) ) {

	if ( $verbose > 0 ) echo '<table border=1 width=80%>';

	for ($j = 0; $j < strlen($series); $j++) {

	$serie = substr($series, $j, 1);

	$bpobase	= $bpo.$lang;
	$bpoabourl	= "$bpobase/abo/$saison/$serie";
	$bposaalurl 	= "$bpobase/saalplan/";

	$tmpfile='/home/www/bphilcal/data/abo.'.$saison.'.'.$lang.'.'.$serie;

	if ( !file_exists($tmpfile) ) $retc = exec("wget -O $tmpfile $bpoabourl");

	$daten = fetchData($tmpfile);
	ereg("<table class=\"concerts\">(.*)</table>", $daten, $res);
	$daten = $res[1];
	$daten = preg_replace("!<img .*>!iU", "", $daten);
	$daten = preg_replace("!<.*(href).*>!iU", "", $daten);
	$daten = preg_replace("!&nbsp;!", " ", $daten);
	$daten = preg_replace("!PHILHARMONIE!i", "PHIL", $daten);
	$daten = preg_replace("!BERLINER PHILHARMONIKER!i", "BPO", $daten);
	$daten = preg_replace("!KAMMERMUSIKSAAL!i", "KMS", $daten);
	$daten = preg_replace("!CHAMBER MUSIC HALL!i", "CMH", $daten);
	$daten = preg_replace("!VORVERKAUF BEGINNT AM!i", "Vorverkauf ab", $daten);
	$daten = preg_replace("!Einf?hrungsveranstaltung!", "Einf&uuml;hrung", $daten);

	$collect=false;
	clear_entry($entry);
	while ( preg_match("!<div class=\"(.*)\">(.*)</div>!U", $daten, $res) ) {

		$daten = preg_replace("!<div class=\"{$res[1]}\">.*</div>!U", "", $daten, 1);
		if ( ($verbose > 0) && (in_array($res[1], $items) ) ) echo "<br>$res[1]= $res[2]";

		if ( $verbose > 1) echo "<tr>";

		switch ($res[1]) {
		case $items[0]:	if ($collect) make_entry($mem, $entry);
				$collect= true;
				clear_entry($entry);
				$place = $res[2];
				break;
		case $items[1]:	$timeinfo = $res[2];
				break;
		case $items[2]:	$artist = $res[2];
				break;
		case $items[3]:	$conductor = $res[2];
				break;
		case $items[4]:	$soloists = $res[2];
				break;
		case $items[5]:	$title = $res[2];
				break;
		case $items[6]:	$pieces = $res[2];
				break;
		case $items[7]:	$managerlink = $res[2];
				break;
		case $items[8]:	$introduction = $res[2];
				break;
		default:
		}


		if ($verbose > 0) echo "<td nowrap>$font<a href=\"$bpodayurl\" title=\"Philharmonie Tagesprogramm/View all concerts this day\">$date</a></font></td><td nowrap>$font$time $uhr</font></td>
				<td nowrap>$font<a href=\"$bpoabourl\" title=\"Abonnement Serie $serie_uc ?bersicht/Subscription series $serie_uc overview\">$concertnumber</a></font></td>";
		if ( $verbose > 0 ) echo "<td nowrap>$font$result</font></td>";


	if ( $verbose > 0 ) echo "</tr>";


	if ( $verbose > 0) echo "</table>";

	}
	make_entry($mem, $entry);

	} /* do for all series */

	sort($mem);
	$n = (strlen($series) == 1) ? '' : 'n';
	$series = strtoupper($series);
	$xx = substr($series, 0, 1);
	for ($i=1; $i < strlen($series); $i++) $xx .= ', '.substr($series,$i,1);
	setlocale(LC_TIME, "de_DE");
	echo "<h2>Philharmonie Berlin Abonnementserie$n $xx - nach Konzertdaten sortiert. Stand: ".strftime('%x')."</h2>";
	echo backlink()."<p>";

	if ($short == 0) echo "<table>";
	foreach ($mem as $line) {
		echo substr($line, 9);
		if ($short != 0) echo '<hr>';
	}
	if ($short == 0) echo "</table>";

	$counter = $counter + 1;
	writeCounter($counterfile, $counter);
	footer( true );

} else  { if ( ($qs != '') && ($res[1] == '') ) { $errstr = '<p><b><font color="red">Sie haben keine Abonnentmentbuchstaben eingegeben.</font></b><br>';
	$saisonerror ='';
	$langerror = '';
	}
	echo '<div style="margin:0; margin-top:0; border:1px solid #dfdfdf; padding: 0em 0em 0em 0em; backgound-color:#F3FFFF; align:left;">';
	echo '<div style="margin:0; padding: 0.5em 0.5em 0.5em 0.5em; background-color:#006699; align:left;">';
	echo '<font color="FFFFFF"><strong>'.$progname.' - Pers&ouml;nlicher Philharmonie-Abonnementskalender</strong></font>';
	echo '<font color="FFFFFF"> v'.$version.'</font></div>';
	echo '<div style="margin:0; padding: 0.5em 0.5em 0.5em 0.5em; background-color:#DFDFDF; align:left; ">';
	echo '<div align="left"><font size=-2>';
	echo '<body onload="if (document.bpo) document.bpo.series.focus()">';
	echo $errstr.$saisonerror.$langerror;
	echo "<form name=\"bpo\" action=\"$scriptbaseurl\" method=\"get\">";
	echo "<table>";
	echo "<tr><td>Bitte geben Sie hier die Buchstaben Ihrer Abonnements ein.<br>Beispiel: AEG<p>G&uuml;ltige Buchstaben sind ABCDEFGHIKLMNOPQRSTUVWX</td>";
	echo "<td><input type=\"text\" name=\"series\" value=\"$series\" size=\"15\" ";
	echo ' onkeypress="if (event) {if (this.value == this.defaultValue) this.value=\'\'; else if (event.keyCode==13) document.bpo.submit()}">';
	echo '</td></tr>';
	echo "<tr><td>Saison 2005/2006</td><td><input type=\"radio\" name=\"saison\" value=\"2005.2006\" checked=\"checked\"></td></tr>";
	echo "<tr><td>Saison 2004/2005</td><td><input type=\"radio\" name=\"saison\" value=\"2004.2005\"></td></tr>";
	echo "<tr><td>Ausgabeformat/Print layout</td>
		<td><table><tr>
			<td>short<input type=\"radio\" name=\"short\" value=\"0\" checked=\"checked\"></td>
			<td>medium<input type=\"radio\" name=\"short\" value=\"1\"></td>
			<td>long<input type=\"radio\" name=\"short\" value=\"2\"></td>
		</tr></table>
		</td></tr>";
	echo "<tr><td>Sprache/Language</td><td><table><tr><td>Deutsch <input type=\"radio\" name=\"lang\" value=\"de\" checked=\"checked\"></td>";
	echo "<td>English <input type=\"radio\" name=\"lang\" value=\"en\"></td></tr></table></td></tr>";
	echo "<tr><td colspan=2><input type=\"submit\" value=\"Klicken Sie hier, um online Ihren pers&ouml;nlichen Abo-Kalender erzeugen zu lassen !\"></td></tr></table>";
	echo '</div></form>';
	footer(false);
	echo '</div>';
	echo '</div>';
}
?>
