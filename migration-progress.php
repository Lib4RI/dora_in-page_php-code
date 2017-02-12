<?php

/***
  * Copyright (c) 2017 d-r-p (Lib4RI) <d-r-p@users.noreply.github.com>
  * 
  * Permission to use, copy, modify, and distribute this software for any
  * purpose with or without fee is hereby granted, provided that the above
  * copyright notice and this permission notice appear in all copies.
  * 
  * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
  * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
  * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
  * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
  * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
  * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
  * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 ***/

global $base_url;



/***** EDIT THIS PART ONLY *****/

$namespace = preg_replace("/.*\//", "", $base_url); // this is, of course, the wrong way of doing it...
$collection = preg_replace("/.*\//", "", $base_url) . ":publications"; // this is, of course, the wrong way of doing it...
$corrected_markers = array("AA", "BB", "CC", "DD", "EE");
$markers_to_be_ignored = array("AAV", "BBV", "FFV");
$source_array = array(
  'date' => 'yyyy-mm-dd', // use "yyyy-mm-dd" for consistency
  'source' => 'Source',
  'total' => 100, // the total number of objects to be migrated
  'autocomplete' => true,
  'missing' => array( // -1 auto-completes if autocomplete is true, and puts a question mark (along with an additional line "Uncategorised"), otherwise
    'Journal Article' => -1,
    'Newspaper or Magazine Article' => -1,
    'Book' => -1,
    'Edited Book' => -1,
    'Book Chapter' => -1,
    'Proceedings Paper' => -1,
    'Conference Proceedings' => -1,
    'Dissertation' => -1,
    'Master Thesis' => -1,
    'Bachelor Thesis' => -1,
    'Report' => -1,
  ),
  'footnotetitle' => 'Last ingests',
  'footnotes' => array( // use "(latest)" to insert the date of the youngest object (format yyyy-mm-dd) in that category; leave blank ("") if you want to skip this footnote
    'Journal Article' => "(latest)",
    'Newspaper or Magazine Article' => "(latest)",
    'Book' => "(latest)",
    'Edited Book' => "(latest)",
    'Book Chapter' => "(latest)",
    'Proceedings Paper' => "(latest)",
    'Conference Proceedings' => "(latest)",
    'Dissertation' => "(latest)",
    'Master Thesis' => "(latest)",
    'Bachelor Thesis' => "(latest)",
    'Report' => "(latest)",
  ),
  // below, the following replacements are active:
  //  - "(date)": the date defined above
  //  - "(source)": the source defined above
  //  - "(total)": the total defined above
  //  - "(now)": the current date ("yyyy-mm-dd")
  //  - "(site_name)": the site name (e.g. "DORA Lib4RI")
  'maintext' => "This page is intended to give you an idea on how the migration process from (source) into (main_site) is progressing. In parallel to migrating the publication data and establishing the novel workflows necessary for the complete transition to DORA, we have started to control and, when necessary, to correct the entries systematically. We are currently focussing primarily on journal articles of the past ten years. The progress can be seen live on this page.", // leave blank ("") for none
  'firstcolumnfootnote' => "The values in the first column are estimates, since the categorisation in (source) does not completely correspond to the categorisation in DORA. Also, notice that a few items will not be migrated.", // leave blank ("") for none
  'uncategorisedfootnote' => "We do not yet know to which category the remaining articles belong, but we will update this page continuously.", // only displayed when autocomplete is turned off; leave blank ("") for none
  'firstcoltitle' => "Items in<BR/>(source)(*)<BR/>(as of (date))", // mark the location of the footnote with "(*)"; leave blank ("") for none
  'secondcoltitle' => "Items in<BR/>DORA<BR/>(as of (now))", // leave blank ("") for none
  'thirdcoltitle' => "Corrected in<BR/>DORA<BR/>(as of (now))", // leave blank ("") for none
  'divisor' => 40, // used to be 33.3333333333333333; increase this to make the graphs shorter
);

/*******************************/



$is_corrected = function($facet) use ($corrected_markers, $markers_to_be_ignored) {
  $f_a = explode(" ", $facet);
  foreach ($f_a as $f) {
    $f = trim($f, " \t\n\r\0\x0B,;.-");
   // remove unwanted tokens:
    foreach ($markers_to_be_ignored as $token){
	$f = str_ireplace($token, "", $f);
    }
    foreach ($corrected_markers as $m) {
      // @TODO: The following should be revisited, as it will also match
      //        substrings, yielding wrong results depending on chosen markers
      if (stripos($f, $m) !== FALSE) {
        return TRUE;
      }
    }
  }
  return FALSE;
};

$mkreplacements = function($str) use ($source_array) {
  $replacements = array(
    '(date)' => $source_array['date'],
    '(source)' => $source_array['source'],
    '(total)' => $source_array['total'],
    '(now)' => date("Y-m-d"),
    '(main_site)' => variable_get('site_name', 'DORA Lib4RI'),
  );
  foreach ($replacements as $key => $val) {
    $str = str_replace($key, $val, $str);
  }
  return $str;
}

?>

<?php
if ($source_array['maintext'] !== "") {
  echo $mkreplacements($source_array['maintext']);
}
?>

<?php
$genres = array(
  "Journal Article",
  "Newspaper or Magazine Article",
  "Book Chapter",
  "Book",
  "Edited Book",
  "Dissertation",
  "Master Thesis",
  "Bachelor Thesis",
  "Proceedings Paper",
  "Conference Proceedings",
  "Report",
);

$mycolors = array('total' => 'darkmagenta', 'stored' => 'blue', 'corrected' => 'green');
$divby = $source_array['divisor'];

$genre_field = "mods_genre_s";
$facet_field = "mods_note_department descriptor_mlt";

$solr = new IslandoraSolrQueryProcessor();
$solr->buildQuery("*:*");
$solr->solrLimit = 1;
$solr->solrParams['sort'] = "fgs_createdDate_dt desc";
$solr->solrParams['fl'] = implode(
  ',',
  array(
    'PID',
    'fgs_createdDate_dt',
  )
);
$solr->solrParams['facet'] = "on";
$solr->solrParams['facet.field'] = $facet_field;
$solr->solrParams['facet.limit'] = -1;
$solr->solrParams['facet.mincount'] = 1;
$genre_count = array();
$grand_total = 0;
$explicit_missing = 0;
foreach ($genres as $gen) {
  $solr->solrParams['fq'] = "$genre_field:(\"$gen\") mods_identifier_local_s:* PID:$namespace\:* RELS_EXT_isMemberOfCollection_uri_ms:info\:fedora/" . str_replace(":", "\:", $collection);
  $solr->executeQuery(FALSE);
  $genre_count[$gen]['stored'] = $solr->islandoraSolrResult['response']['numFound'];
  if ($genre_count[$gen]['stored'] != 0) {
    $genre_count[$gen]['last'] = $solr->islandoraSolrResult['response']['objects'][0]['solr_doc']['fgs_createdDate_dt'];
  }
  $genre_count[$gen]['total'] = $genre_count[$gen]['stored'] + $source_array['missing'][$gen];
  if ($source_array['missing'][$gen] > -1) {
    $explicit_missing += $source_array['missing'][$gen];
  }
  else {
    $genre_count[$gen]['total'] -= $source_array['missing'][$gen];
  }
  $grand_total += $genre_count[$gen]['total'];
  $res = $solr->islandoraSolrResult['facet_counts']['facet_fields'][$facet_field];
  $genre_count[$gen]['corrected'] = 0;
  foreach ($res as $key => $val) {
    if ($is_corrected($key)) {
      $genre_count[$gen]['corrected'] += $val;
    }
  }
}
$to_distribute = 0;
if ($grand_total < $source_array['total']) {
  $to_distribute = $source_array['total'] - $grand_total;
  if ($source_array['autocomplete']) {
    foreach ($genres as $gen) {
      if ($source_array['missing'][$gen] == -1) {
        $genre_count[$gen]['total'] += round($to_distribute * $genre_count[$gen]['total'] / ($grand_total - $explicit_missing));
      }
    }
  }
}

?>

<TABLE cellpadding="5px">
  <TR><TH>Genre</TH><TH><?php echo $mkreplacements(str_replace("(*)", ($source_array['firstcolumnfootnote'] !== "" ? "<SUP>&dagger;</SUP>" : ""), $source_array['firstcoltitle'])); ?></TH><TH><?php echo $mkreplacements($source_array['secondcoltitle']); ?></TH><TH><?php echo $mkreplacements($source_array['thirdcoltitle']); ?></TH><TH/></TR><?php
$i = 0;
foreach ($genre_count as $gen => $cnt) {
  if ($source_array['footnotes'][$gen] !== "") {
    $i += 1;
  }
?>

  <TR><TD><?php echo $gen; if ($source_array['footnotes'][$gen] !== "") {?><SUP><?php echo $i; ?></SUP><?php }?></TD><TD style="text-align:right; color:<?php echo $mycolors['total'];?>"><?php echo (!$source_array['autocomplete'] && $source_array['missing'][$gen] == -1 ? "?" : $cnt['total']);?></TD><TD style="text-align:right; color:<?php echo $mycolors['stored'];?>"><?php echo $cnt['stored'];?></TD><TD style="text-align:right; color:<?php echo $mycolors['corrected'];?>"><?php echo $cnt['corrected'];?></TD><TD style="padding-left:25px;"><DIV style="height:15px; width:<?php echo ($cnt['stored']/$divby);?>px; border:1px solid <?php echo (!$source_array['autocomplete'] && $source_array['missing'][$gen] == -1 ? "transparent" : $mycolors['total']);?>; padding-right:<?php echo (($cnt['total']-$cnt['stored'])/$divby);?>px;"><DIV style="height:15px; width:<?php echo ($cnt['corrected']/$divby);?>px; padding-right:<?php echo (($cnt['stored']-$cnt['corrected'])/$divby);?>px; background-color:<?php echo $mycolors['stored'];?>;"><DIV style="height:15px; width:0px; padding-right:<?php echo ($cnt['corrected']/$divby);?>px; background-color:<?php echo $mycolors['corrected'];?>;"></DIV></DIV></DIV></TD></TR><?php
}
if (!$source_array['autocomplete']) {
?>
  <TR><TD><I>Uncategorised</I><?php if ($source_array['uncategorisedfootnote'] !== "") {?><SUP>*</SUP><?php }?></TD><TD style="text-align:right; color:<?php echo $mycolors['total'];?>"><?php echo $to_distribute;?></TD><TD style="text-align:right; color:<?php echo $mycolors['stored'];?>"></TD><TD style="text-align:right; color:<?php echo $mycolors['corrected'];?>"></TD><TD style="padding-left:25px;"><DIV style="height:15px; width:<?php echo ($to_distribute/$divby);?>px; border:1px solid <?php echo $mycolors['total'];?>;"></DIV></TD></TR>
<?php
}
?>

</TABLE>
<?php
if ($source_array['firstcolumnfootnote'] !== "" || $i > 0 || (!$source_array['autocomplete'] && $source_array['uncategorisedfootnote'] !== "")) {
?><FONT size=-2>
<?php
  if ($source_array['firstcolumnfootnote'] !== "") {
?>  <SUP>&dagger;</SUP><?php
    echo $mkreplacements($source_array['firstcolumnfootnote']);
?><BR/>
<?php
  }
  if ($i > 0) {
?>  <SUP><?php
    if ($source_array['footnotetitle'] !== "") {
      if ($i > 1) {
        echo "1-";
      }
      echo $i;
?></SUP><?php echo $source_array['footnotetitle'];?>: <?php
    }
    $j = 0;
    foreach ($genre_count as $gen => $cnt) {
      if ($source_array['footnotes'][$gen] === "") {
        continue;
      }
      $j += 1;
      if ($j > 1) {
        echo ", ";
      }
      if ($i > 1) {
?><SUP><?php echo $j; ?></SUP><?php
      }
      $datestr = "";
      if ($genre_count[$gen]['stored'] != 0) {
        $datestr = preg_replace("/T[0-9:.]*Z/", "", $genre_count[$gen]['last']);
      }
      else {
        $datestr = "never";
      }
      echo str_replace("(latest)", $datestr, $source_array['footnotes'][$gen]);
    }
?><BR/>
<?php
  }
  if (!$source_array['autocomplete'] && $source_array['uncategorisedfootnote'] !== "") {
  ?>  <SUP>*</SUP><?php
    echo $mkreplacements($source_array['uncategorisedfootnote']);
  ?><BR/>
  <?php
  }
?></FONT><?php
}
?>
