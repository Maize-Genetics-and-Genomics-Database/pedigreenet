<?php
  include_once('./include/gp_lib.php');
  include_once('./lib/GraphMLFormatter.php');
  include_once('./lib/Uploader.php');
  include_once('./lib/Downloader.php');
  include_once('./lib/Benchmarker.php');
  include_once('./lib/ResponseCapturer.php');
  include_once('./include/breeders_functions.php');
    /*
     NOTE: The below two files were not included in the GitHub repo because they contain functions specifically designed to query MaizeGDB's database schema. 
     In particular, these files contained functions to create the filters in the PedNet search form.
     Users will need to implement their own filters for their own datasets here.
     include_once('./controllers/data_center/stock_functions.php'); 
     include_once('./include/stock_adv_functions.php'); 
  */
  
  // pseudo-routing
  $embed = !!getCGIParam('embed', 'PG');
  if (($data = getCGIParam('id', 'G'))) {
    echo json_encode(array(
      'id' => getIdForName($data)
    ));
    die();
  }
  //NOTE: imageData is an optional parameter used to create thumbnail images of networks.
  elseif ($imgData = getCGIParam('imageData', 'P', false)) {
    $stock = getCGIParam('stock', 'P', false); 
    $filename = "tools/breeders_toolbox/img_data/$stock.png";
    if (!file_exists($filename)) {
        file_put_contents($filename, base64_decode($imgData));
    }
    
    die();
  }
  elseif (getCGIParam('shortest-path', 'G', false)) { // ex: maizegdb.org/breeders_toolbox?shortest-path=1&from=BSSS&to=B68 (with current network in POST)
    $from = getCGIParam('from', 'G');
    $to = getCGIParam('to', 'G');
    $data = getCGIParam('network', 'P');
    if (!$data) $data = "{}";
      $dbh = connect_to_database();
      $tmpl = 'templates/tools/breeders_toolbox.bau';
      $cyto = 'templates/tools/breeders_toolbox-cytoscape_standalone.bau';
      $toolbox = $embed ? $bauplan->template($bauplan->template()->load($cyto)) : $mgdb->get('body')->load($tmpl);
    $network = parseData($data,$toolbox);
    $network->filterToMinimumDistance($from, $to, true);
    echo $network->toJSON();
    
    die(); 
  }
  elseif (($data = getCGIParam('csv-data', 'P'))) {
    exportFile($data, 'network', 'csv');
  }
  else {
    $dbh = connect_to_database();
    // NOTE: The 3 lines of code below are part of an internal templating language that is not included in the GitHub repository and won't work out-of-the-box. 
    // Ultimately, the templating language we used was to write output from the controllers to the html template (*.bau files)
    // Please modify accordingly to write output to the template files. 
    $tmpl = 'templates/tools/breeders_toolbox.bau';
    $cyto = 'templates/tools/breeders_toolbox-cytoscape_standalone.bau';
    $toolbox = $embed ? $bauplan->template($bauplan->template()->load($cyto)) : $mgdb->get('body')->load($tmpl);
    
    if ($data = getCGIParam('data', 'GP', false)) {
        try {
            if (!getIdForName($data)){
                throw new Exception('Node "' . $data . '" not found in network');
            }
            $toolbox->get('node-center')->replace($data);
        }
        catch (Exception $e) {
            handleError($toolbox, $e->getMessage());
        }
    }
    
    buildCytoscape($dbh, $toolbox, $embed);
    
  }
 
return;
// Actions

  /*
   * Return the stock ID for a stock by its exact name
   */
  function getIdForName($name, $dbh=null) {
    if (is_null($dbh)) $dbh = connect_to_database();
    $sql = "SELECT id FROM stock WHERE LOWER(name) = LOWER('$name')";
    $sth = make_query($dbh, $sql);
    $results = get_all_rows($sth);
    if ($results) {
      return $results[0]['id'];
    }
  }

/*
* Return the exact stock  name for a stock by its id
*/
function getNameForId($id, $dbh=null) {
    if (is_null($dbh)) $dbh = connect_to_database();
    $sql = "SELECT name FROM stock WHERE id = ($id)";
    $sth = make_query($dbh, $sql);
    $results = get_all_rows($sth);
    if ($results) {
        return $results[0]['name'];
    }
}

/*
 * Returns case-corrected name
 */
function rectifyName($name){
  return getNameForId(getIdForName($name));
}

/*
 * Checks names, and returns an array with the case-corrected name
 */
function rectifyNames($names){
  return array_map("rectifyName",$names);
}

  /*
   * Run main functionality
   *
   * NOTE: The $toolbox variable contains the structure of a custom template that is not included in GitHub. 
   * The $toolbox is used to write data directly to variables declared inside the template and won't work out-of-the-box. 
   * Please modify accordingly 
   */
  function buildCytoscape($dbh, $toolbox, $embed=false) {
    $system = getSystemInfo('mgdb.conf');
    $datafile = $system['cytoscape_datafile'];
    $nodeLimit = $system['cytoscape_node_limit'];
    $edgeLimit = $system['cytoscape_edge_limit'];

    if (!$embed) {
      buildFilterUI($dbh, $toolbox);
    }
    else {
      $toolbox->get('standalone')->unmute();
      $toolbox->get('pednet_link')->unmute();
    }
    $formatter = parseData($datafile,$toolbox); 
    $toolbox->get('full-node-count')->replace($formatter->getNodeCount()); 
    $toolbox->get('full-edge-count')->replace($formatter->getEdgeCount()); 

    // Running filters
    $opts = buildOpts();
    
    if (count(array_keys($opts)) > 0) {
      $filtered = runPedigreeFilter($dbh, $opts);
      $stocks = array_map(function($el) { return $el['name']; }, $filtered['stock_list']);
      $stocks = array_unique($stocks);
      $stocks = array_map("rectifyName",$stocks);
      //echo_to_screen($stocks); //Debugging
      $numNodes = count($stocks);
      try {
        if ($numNodes > $nodeLimit){
            throw new Exception("The network ($numNodes nodes) is larger than the limit of $nodeLimit nodes.<br> Please make your network smaller.");
        }

        $formatter->filterToNodes($stocks);
        foreach ($opts as $key => $val) { // set the options in the UI
          if ($toolbox->has("$key-checked")) {
            $toolbox->get("$key-checked")->unmute();
            $toolbox->get("checked-$key")->replace($val);
          }
        }
      }
      catch (Exception $e) {
        handleError($toolbox, $e->getMessage());
      }
    }
      /*if (getCGIParam('input-area')){
          $data = getCGIParam('input-area');
      }*/
      
    if (getCGIParam('node-from') && getCGIParam('node-to')) { // shortest pathh
      $from = getCGIParam('node-from');
      $to = getCGIParam('node-to');

      try {
        if (!getIdForName($from)){
            throw new Exception('Node "' . $from . '" not found in network');
        }
          if (!getIdForName($to)){
              throw new Exception('Node "' . $to . '" not found in network');
          }
          $from = rectifyName($from);
          $to = rectifyName($to);
        $formatter->filterToMinimumDistance($from, $to, true);
        $toolbox->get('node-from')->replace($from);
        $toolbox->get('node-to')->replace($to);
      }
      catch (Exception $e) {
        handleError($toolbox, $e->getMessage());
      }
    }
    
    if (getCGIParam('lca', 'G', false)) { // least common ancestor
      $node1 = getCGIParam('node1', 'G', false);
      $node2 = getCGIParam('node2', 'G', false);
      try {
          if (!getIdForName($node1)){
              throw new Exception('Node "' . $node1 . '" not found in network');
          }
          if (!getIdForName($node2)){
              throw new Exception('Node "' . $node2 . '" not found in network');
          }
          $node1 = rectifyName($node1);
          $node2 = rectifyName($node2);
        $formatter->filtertoLeastCommonAncestor($node1, $node2);
        $toolbox->get('lca-a')->replace($node1);
        $toolbox->get('lca-b')->replace($node2);
      }
      catch (Exception $e) {
        handleError($toolbox, $e->getMessage());
      }
    }

    $json = $formatter->toJSON();
    $csv = $formatter->toCSV();
    $nodeCount = $formatter->getNodeCount();
    $edgeCount = $formatter->getEdgeCount();
    if ($nodeCount > $nodeLimit || $edgeCount > $edgeLimit) {
      $toolbox->get('node-count')->replace($nodeCount);
      $toolbox->get('edge-count')->replace($edgeCount);
      $toolbox->get('node-limit')->replace($nodeLimit);
      $toolbox->get('edge-limit')->replace($edgeLimit);
    }
    else { 
      $nodesList = $formatter->getNodeList();
      $toolbox->get('textarea')->replace($csv);
      $toolbox->get('nodes-from')->unroll('node', $nodesList);
      $toolbox->get('nodes-to')->unroll('node', $nodesList);
    }

    if ($nodeCount == 0) {
      // Check if stock is in db, but just has no pedigree info
      $data = getCGIParam('data', 'GP', false);
      if ($id = getIdForName($data)) {
        $toolbox->get('stock_name')->replace($data);
        $toolbox->get('no-pedigree')->unmute();
      }
      $toolbox->get('no-results')->unmute();
    }
    
    $layout = getCGIParam('layout', 'GP', false);
    if (!$layout) { //default layout
      $layout = "cose";
    }
    $toolbox->get('cytoscape-data')->replace($json);
    $toolbox->get('network-csv')->replace($csv);
    $toolbox->get('initial-layout')->replace($layout);
    
    if (!$embed) {
        $toolbox->get($layout."_selected")->replace("selected");
    }
    
  }

  function echo_to_screen($var,$color='white'){
    echo '<p style="color: '. $color . '";>';
    var_dump($var);
    echo '</p>\n<br>';
  }

  function handleError($toolbox, $msg) {
    $toolbox->get('error-message')->replace($msg);
    $toolbox->get('error-singleton')->mute();
    $toolbox->get('network-appearance-options')->mute();
  }

  function exportFile($data, $basename, $type, $b64enc=false, $embed=false) {
    if ($b64enc) {
      list($strtype, $data) = explode(';', $data);
      list(, $data)      = explode(',', $data);

      $data = base64_decode($data);
    }

    $filename = uniqid($basename) . ".$type";
    $downloader = new Downloader('/tmp', true);
    $downloader->sendHeader(!$embed);
    $downloader->cleanup(!$embed);
    $downloader->downloadString($data, $filename);
    return '/tmp/' . $filename;
  }

///////////////////////////
  function buildTab($content, $label) {
    return array(
      'tab'     => '<li class="tab_focus">' . $label . '</li>',
      'content' => '<div class="active">' . $content . '</div>'
    );
  }

  function buildFilterUI($dbh, $template) {
    $template->get('state-list')->unroll('state', getRepresentedStates($dbh));
    // Pedigree filters
    $template->get('country-list')->loop(getRepresentedCountries($dbh));
    $template->get('developer-list')->loop(getRepresentedDevelopers($dbh));
    $template->get('sources-list')->loop(getRepresentedSources($dbh));
     
    // Stock filters
    $template->get('developer_options')->replace(getDeveloperOptions($dbh)); 
    $template->get('type_options')->replace(getTypeOptions($dbh)); 
    $template->get('linkage_options')->replace(getLinkageOptions($dbh)); 
    $template->get('karyotype_options')->replace(getKaryotypeOptions($dbh)); 
    
  }

  function buildOpts() {
    $vars = getParamDump('GP');
  
    // Stock Filters
    $opts = array();
    $dependents = array( // checkbox names to input names
      'stock_center'                => 'stock_center',
      'filter-developer'            => 'developer',
      'filter-sources'              => 'source',
      'filter-country'              => 'country',
      'filter-identifier'           => 'name',
      'filter-type'                 => 'type',
      'filter-state'                => 'state',
      'filter-line'                 => 'line', // TODO
      'filter-linkage_groups'       => 'linkage_groups',
      'filter-genotypic_variation1' => 'genotypic_variation1',
      'filter-karyotypic_variation' => 'karyotypic_variation'
    );
    foreach ($dependents as $selected => $key) {
      if ((array_key_exists($selected, $vars) && $vars[$selected] == 'on')) {
        $opts[$key] = $vars[$key];
      }
    }
   
    // Filters passed as url params
    $dependentVals = array_values($dependents);
    foreach ($vars as $varKey => $varVal) {
      if (in_array($varKey, $dependentVals)) {
        $opts[$varKey] = $vars[$varKey];
      }
    }
  
    return $opts;
  }

  function runPedigreeFilter($dbh, $opts) {
    return array( 'stock_list' => getPedigreeResults($dbh, $opts) );
  }

  function parseData($defaultFile,$toolbox) {
    $formatter = new GraphMLFormatter();
    $uploader = new Uploader();

    if ($uploader->hasFiles()) { // input was uploaded as file
      $file = $uploader->uploadAsUnique('file', true);
      if (!$file) reportError("Error uploading file");
      if (isNetwork($file->getStream()->getLine())) {
        $formatter->parseFile($file->getPath(), GraphMLFormatter::GUESS_FMT);
      }
      else {
        $formatter->filterToNodes($file->getLines());
      }
    }
    else if ($data = getCGIParam('input-area', 'GP', false)) { // input was uploaded as text
      if (isNetwork($data)) {
        $formatter->parse($data, GraphMLFormatter::GUESS_FMT);
      }
      else {
        $formatter->parseFile($defaultFile, GraphMLFormatter::GUESS_FMT);
        $filterTo = explode("\n", str_replace(array("\r\n", "\n\r", "\r"), "\n", $data));
          try {
              if (!getIdForName($filterTo[0])){
                  throw new Exception('Node "' . $filterTo[0] . '" not found in network');
              }
              else{
                  $formatter->filterToNetwork(rectifyName($filterTo[0]));
              }
          }
          catch (Exception $e) {
              handleError($toolbox, $e->getMessage());
          }
      }
    }
    else if ($data = getCGIParam('data', 'GP', false)) {
        try {
            if (!getIdForName($data)){
                throw new Exception('Node "' . $data . '" not found in network');
            }
            else{
                // ORIGINAL
                if (isNetwork($data)) {
                    $formatter->parse($data, GraphMLFormatter::GUESS_FMT);
                }
                else {
                    $formatter->parseFile($defaultFile, GraphMLFormatter::GUESS_FMT);
                    $filterTo = explode("\n", str_replace(array("\r\n", "\n\r", "\r"), "\n", $data));
                    $formatter->filterToNetwork(rectifyName($filterTo[0]));
                }
            }
        }
        catch (Exception $e) {
            handleError($toolbox, $e->getMessage());
        }
    }
    else { // show the default network
      if (file_exists($defaultFile)) {
        $formatter->parseFile($defaultFile, GraphMLFormatter::GUESS_FMT);
      }
      else {
        $formatter->parse($defaultFile, GraphMLFormatter::GUESS_FMT);
      }
    }

    return $formatter;
  }

  /*
   * Return whether or not a string represents a network (as CSV)
   */
  function isNetwork($data) {
    $lines = substr_count($data, "\n");
    $commas = substr_count($data, ',');

    $lastChar = $data[strlen($data)-1];
    if ($lastChar != "\n") {
      $lines += 1;
    }

    return $commas == $lines;
  }
  
?>
