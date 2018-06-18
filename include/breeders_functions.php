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
  // Build queries based on opts
  if (array_key_exists('state', $opts) && $opts['state'] != 'default') {
    $state = $opts['state'];
    array_push($queries, getPedigreesByStateQuery($state));
  }
  // If no state is selected (all states and all developers
  if (array_key_exists('state', $opts) && $opts['state'] == 'default') {
      array_push($queries, getPedigreesAllQuery());
  }
  if (array_key_exists('country', $opts) && $opts['country'] != 'default') {
    $country = $opts['country'];
    array_push($queries, getPedigreesByCountryQuery($country));
  }
  if (array_key_exists('developer', $opts) && $opts['developer'] != 'default') {
    $developer = $opts['developer'];
    array_push($queries, getPedigreesByDeveloperQuery($developer));
  }
  if (array_key_exists('source', $opts) && $opts['source'] != 'default') {
    $source = $opts['source'];
    array_push($queries, getPedigreesBySourceQuery($source));
  }

  // Join queries so the result will be the intersection of the results of all queries
  $query = "SELECT * FROM (" . _intersect($queries) . ") ORDER BY NAME";
  //echo $query;
  $stmt = make_query($DBConn, $query);
  return get_all_rows($stmt);
}

function _intersect($queries) {
  return implode(" INTERSECT ", $queries);
}

function getRepresentedStates($DBConn) {
  $query = "SELECT DISTINCT(C.STATE_PROVINCE)
            FROM STOCK_COEFF_PARENT A, ID_NUM B, STOCK C
            WHERE A.STOCK1 = B.ID
             AND A.STOCK1 = C.ID
             AND B.CURATION_LVL = 0
             AND C.STATE_PROVINCE IS NOT NULL
            ORDER BY C.STATE_PROVINCE";
  $stmt = make_query($DBConn, $query);
  return array_map(function($res) { return $res['state_province']; }, get_all_rows($stmt));
}

function getPedigreesAllQuery() {
    return "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM STOCK_COEFF_PARENT A, ID_NUM B, STOCK C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID OR A.ID = C.ID)
             AND B.CURATION_LVL = 0";
}

function getPedigreesByStateQuery($state) { 
  return "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM STOCK_COEFF_PARENT A, ID_NUM B, STOCK C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID or A.ID = C.ID)
             AND B.CURATION_LVL = 0
             AND C.STATE_PROVINCE like '$state'";
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
  $rows = get_all_rows($stmt);
  return _array_unique_nested($rows, 'country');
}

function getPedigreesByCountryQuery($country) {
  return "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM STOCK_COEFF_PARENT A, ID_NUM B, STOCK C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID OR A.ID = C.ID)
             AND B.CURATION_LVL = 0
             AND C.COUNTRY like '$country'";
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

function getPedigreesByDeveloperQuery($developer) {
  return "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM stock_coeff_parent A, id_num B, stock C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID OR A.ID = C.ID)
             AND B.CURATION_LVL = 0
             AND C.developer = $developer";
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

function getPedigreesBySourceQuery($source) {
  return "SELECT B.ID, C.NAME, C.DEVELOPER, C.STATE_PROVINCE, C.PEDIGREE, A.ID as STOCK_CHILD_ID
            FROM stock_coeff_parent A, id_num B, stock C
            WHERE A.STOCK1 = B.ID
             AND (A.STOCK1 = C.ID OR A.ID = C.ID)
             AND B.CURATION_LVL = 0
             AND C.available_from = $source";
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

/*
 * Return a hash for the given request types
 */
function getParamDump($type='GPS') {
  $params = array();
  if (strchr($type, 'G') > -1) {
    $params = array_merge($params, $_GET);
  }
  if (strchr($type, 'P') > -1) {
    $params = array_merge($params, $_POST);
  }
  if (strchr($type, 'S') > -1) {
    $params = array_merge($params, $_SESSION);
  }

  return $params;
}//getParamDump

// doesn't throw warnings if param doesn't exist.
/* jp - added condition for session variables */
function getCGIParam($name, $type='GPS', $default='') {
   if (strchr($type, 'G') > -1 && isset($_GET[$name]))
      if (is_array($_GET[$name]))
         return $_GET[$name];
      else
         return trim($_GET[$name]);
   else if (strchr($type, 'P') > -1 && isset($_POST[$name]))
         if (is_array($_POST[$name]))
            return $_POST[$name];
         else
            return trim($_POST[$name]);
   else if (strchr($type, 'S') > -1 && isset($_SESSION[$name]))
         if (is_array($_SESSION[$name]))
            return $_SESSION[$name];
         else
            return trim($_SESSION[$name]);
   else
      return $default;
}//getCGIParam()

function getSystemInfo($filename='mgdb.conf') {
   $system_info_file = getSystemInfoFile($filename);
   if ($system_info_file == '') {
      // eeek! We're stuck!
      echo "
        <span class=\"pc-error\">
          Unable to find system configuration file!
        </span>";
      exit;
   }

   $system = readConfFile($system_info_file);

   // Get some system information from the $_SERVER variable
   $system['root_url'] = 'http://' . $_SERVER['HTTP_HOST'];

   // Put the path to the system info file into the system object
   $system['SYSTEM_INFO'] = $system_info_file;

   if (isset($system['error_reporting'])) {
     // Turn on error reporting (according to setting in conf file)
     turnOnErrorReporting($system);
   }

   return $system;
}//getSystemInfo()

?>
