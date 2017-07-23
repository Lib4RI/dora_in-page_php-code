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

// Get the name of the institute and set the page title accordingly
$site_name = variable_get('site_name', 'DORA Lib4RI'); // @CAVEAT: if the subsequent logic fails, people will be referred to as "Lib4RI-affiliated"!
$institute = preg_replace("/^DORA */", "", $site_name); // assumes $site_name = "DORA <institute>"

$page_title = preg_replace("/Lib4RI/", $institute, "Browse by Lib4RI-affiliated authors");
drupal_set_title($page_title);

// Set the namespace of the objects we want to list
// N.B.: we assume all authors are in the namespace '<subsite>-authors', where <subsite> is the name of the subsite as written in the url

$namespace = "*-authors"; // default; we get the actual namespace from the subsite name
global $base_url;
$publications_namespace = preg_replace("/^[^\/]*\/|^[^\/]*$/", "", preg_replace("/^[^\/]*:\/\//", "", $base_url));
if ($publications_namespace != "") {
  $namespace = $publications_namespace . "-authors"; // @CHANGEME?
}

// Set the maximum number of results per page

$limit = 500; // alternatively, make this customisable using: variable_get('islandora_solr_facet_pages_limit', 25) or define your own variable

// Define the solr-fields we are going to use

$labelfield = variable_get('islandora_solr_object_label_field', 'fgs_label_s');
$searchfield = preg_replace("/_(s|t)$/", "_mlt", $labelfield); // replace a single-valued text/string field with mapped-lower-text, otherwise leave unchanged
$facetfield = variable_get('lib4ridora_author_solr_field', 'mods_name_personal_nameIdentifier_authorId_ms');
$modelfield = variable_get('islandora_solr_content_model_field', 'RELS_EXT_hasModel_uri_ms');
$models = array('publications' => 'ir:citationCModel', 'authors' => 'islandora:personCModel');

// Get URL parameters

$params = $_GET;
unset($params['q']); // contains the node (this page)...[?]

$letter_default = 'A'; // default to showing the results for 'A'; set to NULL if you want to show all results by default
$letter = isset($params['letter']) ? $params['letter'] : $letter_default;
if ($letter == '*') { // hidden feature: if we request '*', then we show the complete list (former default)
  $letter = NULL;
}
else {
  $letter = in_array($letter, array_merge(range('A', 'Z'), range('a','z'))) ? ucfirst($letter) : $letter_default; // cleanup
}

$find = (isset($params['find']) && trim($params['find']) != "") ? trim($params['find']) : NULL;

$showall = isset($params['showall']) && (in_array(strtolower($params['showall']), array('', 'true')) || ((intval($params['showall']) == $params['showall']) && (intval($params['showall']) != 0))); // show also authors with no publications?

$page = isset($params['page']) ? $params['page'] : 0;
$page = ($page == intval($page))? intval($page) : 0; // cleanup

$offset = intval($page) * $limit;

// Where am I? And where do I want to go?

$myurl = url(current_path());
$searchurl = $base_url . "/islandora/search";

// Find all objects (PID + label; the rest might be restricted)

$myqp = new IslandoraSolrQueryProcessor();
$myqp->buildQuery("PID:(" . $namespace . "\:*)");
unset($myqp->solrParams['facet.date.start']); // cleanup
unset($myqp->solrParams['facet.date.end']); // cleanup
unset($myqp->solrParams['facet.date.gap']); // cleanup
unset($myqp->solrParams['facet.date']); // cleanup
unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt.facet.date.start']); // cleanup
unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt.facet.date.end']); // cleanup
unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt.facet.date.gap']); // cleanup
unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt.facet.mincount']); // cleanup
unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_type_reportingYear_dateOther_dt.facet.date.start']); // cleanup
unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_type_reportingYear_dateOther_dt.facet.date.end']); // cleanup
unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_type_reportingYear_dateOther_dt.facet.date.gap']); // cleanup
unset($myqp->solrParams['fq']);  // cleanup
$myqp->solrParams['fq'][] = $modelfield . ":" . islandora_solr_lesser_escape("info:fedora/" . $models['authors']);
if ($find) {
  $mb_regex_enc = mb_regex_encoding();
  mb_regex_encoding('UTF8');
  $searchstring = implode(" ", mb_split('\W+', $find)); // we do not want fancy search: all non-alph characters will be swallowed
  $searchstring = trim(preg_replace("/ +/", " ", $searchstring)); // we swallow multiple spaces
  mb_regex_encoding($mb_regex_enc);
  $myqp->solrParams['fq'][] = $searchfield . ":(*" . preg_replace("/ +/", "* *", $searchstring) . "*)";
}
elseif ($letter){
  $myqp->solrParams['fq'][] = $searchfield . ":(" . $letter . "*)";
}
unset($myqp->solrParams['sort']);  // cleanup
$myqp->solrParams['sort'] = $searchfield . " asc";
$myqp->solrParams['fl'] = 'PID,' . $labelfield; // just in case, let solr work less
$myqp->solrStart = $offset;
$myqp->solrLimit = $limit;
unset($myqp->solrParams['facet']); // cleanup
unset($myqp->solrParams['facet.field']); // cleanup
unset($myqp->solrParams['facet.mincount']); // cleanup
unset($myqp->solrParams['facet.limit']); // cleanup
unset($myqp->solrParams['facet.prefix']); // cleanup
$myqp->executeQuery(FALSE);

// Store the results, adding the number of publications

$result = array();
$total = 0;
if (!empty($myqp->islandoraSolrResult) && isset($myqp->islandoraSolrResult['response']) && isset($myqp->islandoraSolrResult['response']['numFound']) && $myqp->islandoraSolrResult['response']['numFound'] > 0) {
  $total = $myqp->islandoraSolrResult['response']['numFound'];
  $solrresult = array();
  if (isset($myqp->islandoraSolrResult['response']['objects']) && !empty($myqp->islandoraSolrResult['response']['objects'])) {
    $solrresult = $myqp->islandoraSolrResult['response']['objects'];
  }
  // Find the number of publications
  $myqp->buildQuery($facetfield . ":(" . $namespace . "\:*)"); // this restriction should not make a difference [?]
  unset($myqp->solrParams['facet.date.start']); // cleanup
  unset($myqp->solrParams['facet.date.end']); // cleanup
  unset($myqp->solrParams['facet.date.gap']); // cleanup
  unset($myqp->solrParams['facet.date']); // cleanup
  unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt.facet.date.start']); // cleanup
  unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt.facet.date.end']); // cleanup
  unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt.facet.date.gap']); // cleanup
  unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt.facet.mincount']); // cleanup
  unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_type_reportingYear_dateOther_dt.facet.date.start']); // cleanup
  unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_type_reportingYear_dateOther_dt.facet.date.end']); // cleanup
  unset($myqp->solrParams['f.mods_originInfo_encoding_w3cdtf_type_reportingYear_dateOther_dt.facet.date.gap']); // cleanup
  unset($myqp->solrParams['fq']);  // cleanup
  $myqp->solrParams['fq'][] = $modelfield . ":" . islandora_solr_lesser_escape("info:fedora/" . $models['publications']);
  unset($myqp->solrParams['sort']);  // cleanup
  $myqp->solrParams['fl'] = 'PID'; // just in case, let solr work less
  $myqp->solrStart = 0;
  $myqp->solrLimit = 0; // we use solr only for the facets
  $myqp->solrParams['facet'] = 'true';
  $myqp->solrParams['facet.field'] = $facetfield;
  $myqp->solrParams['facet.mincount'] = 1;
  $myqp->solrParams['facet.limit'] = -1; // we want _all_ the author-PIDs that have publications in DORA
//  $myqp->solrParams['facet.prefix'] = $namespace; // apparently, it is not enough to restrict with a filter (cf., however, https://cwiki.apache.org/confluence/display/solr/Faceting)
  $myqp->executeQuery(FALSE);
  if (!empty($myqp->islandoraSolrResult) && isset($myqp->islandoraSolrResult['facet_counts']) && isset($myqp->islandoraSolrResult['facet_counts']['facet_fields']) && isset($myqp->islandoraSolrResult['facet_counts']['facet_fields'][$facetfield])) {
    $facetresult = $myqp->islandoraSolrResult['facet_counts']['facet_fields'][$facetfield];
  }
  foreach ($solrresult as $obj) {
    if (!isset($obj['solr_doc']) || empty($obj['solr_doc']) || !isset($obj['solr_doc']['PID']) || !isset($obj['solr_doc'][$labelfield])) {
      continue;
    }
    $key = $obj['solr_doc']['PID'];
    $nam = $obj['solr_doc'][$labelfield];
    $val = array('name' => $nam, 'count' => (isset($facetresult[$key]) ? $facetresult[$key] : "0"));
    if ($showall || intval($val['count']) > 0) {
      $result = array_merge($result, array($key => $val));
    }
  }
}

// Do the markup
// @TODO: Make this through templates/views...

?>
<div class="islandora-objects">
<div style="padding-top: 5px; padding-bottom: 5px; display:table; width:100%;">
<div style="float: left; display: table-cell;"><?php
  foreach (range('A', 'Z') as $l) {
    if ($letter && $letter == $l) {
      echo "<b>" . $l . "</b>" ; // display currently chosen letter in bold font
    }
    else {
      echo "<a title=\"Browse for names starting in " . $l . "\" href=\"" . $myurl . "?letter=" . $l . ($showall ? "&showall" : "") . "\">" . $l . "</a>";
    }
    echo (($l != 'Z') ? "&nbsp;&nbsp;" : "");
  }
  ?>
</div><div style="float: right; display: table-cell;"><form method="get"><input type="text" placeholder="Look for a name..." name="find" value="<?php echo $find ? preg_replace('/"/', "&quot;", $find) : ""; ?>" size="40" maxlength="64" class="form-text"/><?php echo ($showall) ? '<input type="hidden" name="showall"/>' : ''; ?></form></div>
</div>
<div class="object-mock-table-header"><div class="object-mock-pager"><div class="item-list">
<?php

if (!empty($result)) {
  $total_pages = (int) ceil($total / $limit);
  if ($page >= $total_pages) {
    $page = $total_pages - 1;
  }

  if ($total_pages > 1) { // @TODO: Re-implement the pager w/out copy-pasting (and make the no. of pages configurable, as well as stable)...
    if (!$find) {
      $urlquerystart = $myurl . "?letter=" . ($letter ? $letter : "*") . ($showall ? "&showall" : "");
    }
    else {
      $urlquerystart = $myurl . "?find=" . urlencode($find) . ($showall ? "&showall" : "");
    }
    $urlquerystartand = $urlquerystart . "&";
    ?>
<ul class="pager"><?php
    if ($page > 0) {
      ?>
  <li class="pager-first"><a title="Go to first page" href="<?php echo $urlquerystart; ?>">&laquo; First</a></li>
  <li class="pager-previous"><a title="Go to previous page" href="<?php echo ($page - 1 > 0) ? $urlquerystartand . "page=" . ($page - 1) : $urlquerystart; ?>">&lt; Previous</a></li><?php
    }
    if ($page - 3 >= 0) {
      ?>
  <li class="pager-ellipsis">&#8230;</li><?php
    }
    if ($page - 2 >= 0) {
      ?>
  <li class="pager-item"><a title="Go to page <?php echo ($page - 2) + 1;?>" href="<?php echo ($page - 2 > 0) ? $urlquerystartand . "page=" . ($page - 2) : $urlquerystart; ?>"><?php echo ($page - 2) + 1; ?></a></li><?php
    }
    if ($page - 1 >= 0) {
      ?>
  <li class="pager-item"><a title="Go to page <?php echo ($page - 1) + 1;?>" href="<?php echo ($page - 1 > 0) ? $urlquerystartand . "page=" . ($page - 1) : $urlquerystart; ?>"><?php echo ($page - 1) + 1; ?></a></li><?php
    }
    ?>
  <li class="pager-current"><?php echo $page + 1; ?></li><?php
    if ($page + 1 < $total_pages) {
      ?>
  <li class="pager-item"><a title="Go to page <?php echo ($page + 1) + 1;?>" href="<?php echo $urlquerystartand . "page=" . ($page + 1); ?>"><?php echo ($page + 1) + 1; ?></a></li><?php
    }
    if ($page + 2 < $total_pages) {
      ?>
  <li class="pager-item"><a title="Go to page <?php echo ($page + 2) + 1;?>" href="<?php echo $urlquerystartand . "page=" . ($page + 2); ?>"><?php echo ($page + 2) + 1; ?></a></li><?php
    }
    if ($page + 3 < $total_pages) {
      ?>
  <li class="pager-ellipsis">&#8230;</li><?php
    }
    if ($page < $total_pages - 1) {
      ?>
  <li class="pager-next"><a title="Go to next page" href="<?php echo $urlquerystartand . "page=" . ($page + 1); ?>">Next &gt;</a></li>
  <li class="pager-last"><a title="Go to last page" href="<?php echo $urlquerystartand . "page=" . ($total_pages - 1); ?>">Last &raquo;</a></li><?php
    }
    ?>
</ul>
<?php
  }
  else {
    echo "&nbsp;";
  }
  ?>
</div></div></div>
<div class="islandora-objects-list"><?php
  foreach($result as $key => $val){
      ?>
  <div class="islandora-objects-list-item"><dl class="islandora-object"><dd class="islandora-object-caption"><strong><a title="View <?php echo (($val['count'] != 1) ? "all " . ($val['count'] > 0 ? $val['count'] . " " : "") . "publications" : "the " . $val['count'] . " publication"); ?> by <?php echo $val['name']; ?>" href='<?php echo $searchurl; ?>?f[0]=<?php echo $facetfield . ":\"" . islandora_solr_lesser_escape($key) . "\""; ?>'><?php echo $val['name']; ?></a></strong></dd></dl></div><?php
  }
  // add an empty line at the bottom; @TODO: do spacing more elegantly
  ?>
  <div class="islandora-objects-list-item"><dl class="islandora-object"><dd class="islandora-object-caption"><strong>&nbsp;</strong></dd></dl></div>
</div>
<?php
}
else {
  // add an empty entry; @TODO: do spacing more elegantly
  echo "&nbsp;
</div></div></div><div class=\"islandora-objects-list\"><div class=\"islandora-objects-list-item\"><dl class=\"islandora-object\"><dd class=\"islandora-object-caption\"><strong>&nbsp;</strong></dd></dl></div></div>";
}
?>
</div>
<?php

?>
