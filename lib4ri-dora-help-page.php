<?php

// saving the institute's name into a PHP variable, once for display, once for links (lower case).
$inst_link = strtolower( dirname($_SERVER['SCRIPT_NAME']) );	// something like '/eawag'
if ( strlen($inst_link) < 2 /* if on main site: */ ) {
	$inst_link = "";
	$inst_label = "4RI";
} else {		// for all the sub-institutes:
	$inst_label = ( strlen($inst_link) < 5 ) ? strtoupper(substr($inst_link,1)) : ucFirst(substr($inst_link,1));
}

// Adding Lib4RI CSS for Icons:
echo '<link type="text/css" rel="stylesheet" href="' . $inst_link . '/sites/all/themes/libfourri_theme/css/styles.css" />';
?>


<h2>Search</h2>

<ul>
	<li><strong>Search box: </strong>Use the search box on top of the DORA webpage for a general search in all metadata (use quotes to search for exact phrases)</li>
	<li><strong>Advanced search: </strong>Use the <a href="./advanced-search">Advanced Search</a> for more options, e.g.:
	<ul>
		<li>full-text search</li>
		<li>search with Boolean operators (AND, OR, NOT)</li>
	</ul>
	</li>
	<li><strong>Author list: </strong>Use the search box on the <a href="./author-list"><?php echo $inst_label; ?> Authors</a> page to find all publications linked to <?php echo $inst_label; ?>-affiliated authors</li>
	<li><strong>Facet</strong>: Use the facet on the left to filter publications (the facet is visible on the <a href="./islandora/search">Browse</a> page and on any results page)</li>
</ul>

<h2>Citation export</h2>

<ul>
	<li><strong>RIS, RTF, PDF</strong>: Export <em>all or a selction </em>of citations in RIS, RTF or PDF format with the ‘Export As’ option on top of the result list (the citation style can be changed as soon as the export type is chosen):

	<ul>
		<li>use RIS export to get a file which can then be imported into a reference management software like EndNote (see <a href="./tips-and-tricks">Tips &amp; Tricks</a> for HTML tags export to EndNote)</li>
		<li>use RTF export to get an editable text file</li>
		<li>use PDF export to get a PDF</li>
	</ul>
	</li>
</ul>

<h2>Data export</h2>

<ul>
	<li><strong>Excel</strong>: <img style="background-size: 20px 18px; padding:18px 20px 0px 0px; position:relative; top:-1px;" src="<?php echo $inst_link; ?>/sites/all/modules/lib4ri_solr_export_extra/images/xls.green.32x32.png?css=dominating" height="18" width="18" class="secondary-display-xls" alt="Excel Icon" /> Export <em>publication medata</em> in Excel format by clicking on the spreadsheet icon on the top right-hand corner of the list</li>
	<li><strong>CSV</strong>: <img style="background-size: 20px 18px; padding:18px 20px 0px 0px; position:relative; top:-1px; left:2px;" src="<?php echo $inst_link; ?>/sites/all/modules/islandora_solr_search/islandora_solr_config/images/csv.png?css=dominating" height="18" width="18" class="secondary-display-csv" alt="CSV Icon" />&nbsp; Export <em>publication medata</em> in CSV format by clicking on the CSV icon on the top right-hand corner of the list</li>
</ul>

<h2>DORA links</h2>

<ul>
	<li>Use the address in the URL bar of the browser to link to result lists or individual publications in DORA (e.g. on a website)</li>
<?php if($inst_label == 'Eawag'): ?>
	<li>As a member of <?php echo $inst_label; ?>, you can embed links to individual publications:
	<ul>
		<li>on your personal homepage via the web interface described <a href="https://www.internal.eawag.ch/de/informatik/datenmanagement/web-content-management/persoenliche-webseite/" target="_blank">here</a></li>
		<li>on all other external <?php echo $inst_label; ?> webpages via the web interface described in the <a href="https://www.internal.<?php echo $inst_link; ?>.ch/fileadmin/intranet/kommunikation/beratung/web/typo3_handbuch.pdf#page=33">TYPO3 handbook</a> (PDF, p.33)</li>
	</ul>
	</li>
	<li>To enhance the visibility of your publications, we strongly recommend using the above interfaces to embed publications</li>
<?php endif; ?>
</ul>

<h2>Submit your publication</h2>

<ul>
	<li>Please submit your publications via our <a href="./submit">online form</a></li>
	<li>For cases not covered by the form, please send us an <a href="mailto:dora@lib4ri.ch?subject=DORA%20<?php echo $inst_label; ?>%20submission">email</a></li>
	<li>Our&nbsp;<a href="./dora_content_policy">content policy</a> defines which content is accepted in DORA</li>
</ul>

<p>&nbsp;</p>

