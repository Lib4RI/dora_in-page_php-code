<p>
Lib4RI – Library for the Research Institutes within the ETH Domain: Eawag, Empa, PSI &amp; WSL <br><br>

Lib4RI <br>
&#220;berlandstrasse 133 <br>
8600 D&#252;bendorf Switzerland <br>

<br> t. +41 58 765 5700 <br>

<?php
$mailTo = "dora@lib4ri.ch";

// ------------------------------------------
// technical stuff follows:

$inst = array_shift(explode("/",ltrim($_SERVER['REDIRECT_URL'],"/")));
$inst = ( strchr("eawag|empa|psi|wsl",$inst) ? ( strlen($inst) < 4 ? strtoupper($inst) : ucfirst($inst) ) : "Lib4RI" );

// show e-mail and link with institute's name:
echo "<br> e. <a href='about:blank' id='cUsByE'>Write us!</a><br>\r\n"; 
echo "<br> w. <a href='http://www.lib4ri.ch/" . ( $inst != "Lib4RI" ? strtolower($inst) : "" ) . "' target='_blank'>www.lib4ri.ch" . ( $inst != "Lib4RI" ? "/".strtolower($inst) : "" ) . "</a>\r\n";

// psuedo encoding for e-mail:
$pNum = intval(substr(strval(10000*10000*355/113-11),4,5));
$link = "mailto:" . $mailTo . "?subject=DORA%20" . $inst;
$data = "120879,-243516";	// 2 arbitrary values
for($i=0;$i<strlen($link);$i++) { $data .= "," . strval(ord(substr($link,$i,1))*$pNum+(12345*($i+2))-6543210); }
echo "<sc"."ript type='text/ja"."vasc"."ript'><!--\r\n";
echo "var dec = ''; var dVal = parseFloat(10000*355/113); var ele = document.getElementById('cUs'+'ByE');\r\n";
echo "dVal = String(10000*dVal-11).substr(4,5);\r\nvar ary = new Array(" . $data . ");\r\n";
echo "for(i=1;i<ary.length;i++) { dec += String.fromCharCode(parseInt((ary[i]-(12345*i)+6543210)/dVal)) };\r\n";
echo "ele.innerHTML = dec.substring(dec.indexOf(':')+1,(dec+'?').indexOf('?')); ele.href = dec.substr(1);\r\n";
echo "//--></sc"."ript>\r\n";

?>
</p>
