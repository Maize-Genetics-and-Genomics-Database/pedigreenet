<?php
/*
 * A collection of filter functions for the breeders' toolbox
 *
 * history:
 *   11/07/2015  bbraun created
 *
 */

/*
 * Get the intersection of filter results
 */
function getPedigreeResults($DBConn, $opts) {
  $results = array();
  $queries = array();
  //echo "<pre>";var_dump($opts);echo "</pre>";
  // Build queries based on opts
  if (array_key_exists('state0', $opts) && $opts['state'] != 'default' && $opts['filter-state'] == "on") {
   // $state = $opts['state'];
    array_push($queries, getPedigreesByStateQuery($opts));
  }
  // If no state is selected (all states and all developers
  if (array_key_exists('state0', $opts) && $opts['state'] == 'default') {
      array_push($queries, getPedigreesAllQuery());
  }
  if (array_key_exists('country0', $opts) && $opts['country'] != 'default' && $opts['filter-country'] == "on") {
   // $country = $opts['country'];
    array_push($queries, getPedigreesByCountryQuery($opts));
  }
  if (array_key_exists('developer0', $opts) && $opts['developer'] != 'default' && $opts['filter-developer'] == "on") {
    //$developer = $opts['developer'];
    array_push($queries, getPedigreesByDeveloperQuery($opts));
  }
  if (array_key_exists('source0', $opts) && $opts['source'] != 'default' && $opts['filter-sources'] == "on") { 
    $source = $opts['source'];
    array_push($queries, getPedigreesBySourceQuery($opts));
  }

  // Join queries so the result will be the intersection of the results of all queries
  $query = "SELECT * FROM (" . _intersect($queries) . ") ORDER BY NAME";
  //echo "<br>full filter query: " . $query;
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
}

function _intersect($queries) {
  return implode(" INTERSECT ", $queries);
}

function getRepresentedStates($DBConn) {
  $query = "SELECT DISTINCT(C.STATE_PROVINCE) as state
            FROM STOCK_COEFF_PARENT A, ID_NUM B, STOCK C
            WHERE A.STOCK1 = B.ID
             AND A.STOCK1 = C.ID
             AND B.CURATION_LVL = 0
             AND C.STATE_PROVINCE IS NOT NULL
            ORDER BY C.STATE_PROVINCE";
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
  //return array_map(function($res) { return $res['state_province']; }, get_all_rows($stmt));
}

function getPedigreesAllQuery() {
    return "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM STOCK_COEFF_PARENT A, ID_NUM B, STOCK C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID OR A.ID = C.ID)
             AND B.CURATION_LVL = 0";
}

function getPedigreesByStateQuery($opts) { 
 // echo "<pre>";var_dump($opts);echo "</pre>";
  $state0 = $opts["state0"];
  $sql = "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM STOCK_COEFF_PARENT A, ID_NUM B, STOCK C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID or A.ID = C.ID)
             AND B.CURATION_LVL = 0
             AND (C.STATE_PROVINCE like '". $state0 . "'";

  for ($i=1; $i<$opts["num-states"];$i++) {
        $state_i = $opts['state'.$i];
        $sql .= " OR C.STATE_PROVINCE like '$state_i'";
  }
  $sql .= ")";
 // echo "<br>state query: $sql";
  return $sql;
             
}

function getPedigreesByState($DBConn, $state) {
  $query = getPedigreesByStateQuery($state);
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
}

function getRepresentedCountries($DBConn) {
  $query = "SELECT DISTINCT(C.COUNTRY)
            FROM STOCK_COEFF_PARENT A, ID_NUM B, STOCK C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID OR A.ID = C.ID)
             AND B.CURATION_LVL = 0
             AND C.COUNTRY IS NOT NULL
            ORDER BY C.COUNTRY";
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
  //$rows = get_all_rows($stmt);
  //return _array_unique_nested($rows, 'country');
}

function getPedigreesByCountryQuery($opts) {
  $sql = "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM STOCK_COEFF_PARENT A, ID_NUM B, STOCK C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID OR A.ID = C.ID)
             AND B.CURATION_LVL = 0
             AND (C.COUNTRY like '" . $opts["country0"] . "'";
             
   for ($i=1; $i<$opts["num-countries"];$i++) {
        $sql .= " OR C.COUNTRY like '" . $opts['country'.$i] . "'";
   } 
   $sql .= ")";
   return $sql;
}

function getPedigreesByCountry($DBConn, $country) {
  $query = getPedigreesByCountryQuery($country);
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
}

function getRepresentedDevelopers($DBConn) {
  $query = "SELECT DISTINCT(p.name), p.id
            FROM person p
            INNER JOIN stock s
              ON p.id = s.developer
            INNER JOIN stock_coeff_parent scp on (scp.id = s.id or scp.stock1 = s.id) 
            ORDER BY p.name";
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
}

function getPedigreesByDeveloperQuery($opts) {
  $sql = "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM stock_coeff_parent A, id_num B, stock C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID OR A.ID = C.ID)
             AND B.CURATION_LVL = 0
             AND (C.developer = " . $opts["developer0"];
   
   for ($i=1; $i<$opts["num-developers"];$i++) {
        $sql .= " OR C.developer like '" . $opts['developer'.$i] . "'";
   } 
   $sql .= ")";
   return $sql;
}

function getPedigreesByDeveloper($DBConn, $developer) {
  $query = getPedigreesByDeveloperQuery($developer);
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
}

function getRepresentedSources($DBConn) {
  $query = "SELECT DISTINCT(p.name), p.id
            FROM person p
            INNER JOIN stock s
              ON p.id = s.available_from
            INNER JOIN stock_coeff_parent scp on (scp.id = s.id or scp.stock1 = s.id) 
            WHERE p.name != 'unassigned'
            ORDER BY p.name";
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
}

function getPedigreesBySourceQuery($opts) {
  $sql = "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM stock_coeff_parent A, id_num B, stock C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID OR A.ID = C.ID)
             AND B.CURATION_LVL = 0
             AND (C.available_from = " . $opts['source0'];
             
  for ($i=1; $i<=$opts["num-sources"];$i++) {
        $sql .= " OR C.available_from like '" . $opts['source'.$i] . "'";
  } 
  $sql .= ")";
  return $sql;
}

function getPedigreesBySource($DBConn, $source) {
  $query = getPedigreesBySourceQuery($source);
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
}

/*
 * Remove duplicate hashes from an array of hashes on a given key
 */
function _array_unique_nested($aoh, $key) {
  $seen = array();
  $unique = array();
  foreach ($aoh as $hash) {
    if (!array_key_exists($hash[$key], $seen)) {
      $seen[$hash[$key]] = true;
      array_push($unique, $hash);
    }
  }
  return $unique;
}
?>
