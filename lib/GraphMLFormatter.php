<?php

class NodeNotFoundException extends Exception {}

/*
 * A node for a connected graph. Internal nodes are not exposed because all
 * network-manipulating actions must go through the GraphMLFormatter's network
 */
class GraphMLNode {
  private $data;
  private $edges;

  function __construct($data) {
    $this->data = $data;
    $this->edges = array(
      'to'   => array(),
      'from' => array()
    );
  }

  function addEdge($node) {
    if (!($node instanceof GraphMLNode)) {
      $node = new GraphMLNode($node);
    }
    array_push($this->edges['from'], $node);
    array_push($node->edges['to'], $this);
    return $node;
  }

  function getData() {
    return $this->data;
  }

  function getEdges() {
    return array_merge($this->getEdgesFrom(), $this->getEdgesTo());
  }

  function getEdgesFrom() {
    return $this->edges['from'];
  }

  function getEdgesTo() {
    return $this->edges['to'];
  }

  /*
   * A node's ancestors are its parents and its parents' ancestors
   */
  function getAllAncestors($levelLimit=20, $collate=false) {
    return array_unique($this->getAllAncestorsRecurse(array(), array(), 0, $levelLimit));
  }

  private function getAllAncestorsRecurse($ancestors, $seen, $iteration, $limit) {
    if ($iteration < $limit) {
      $post = array(); // don't merge recursive results until the end so seen order can be preserved
      foreach ($this->getEdgesTo() as $directAncestor) {
        if (!array_key_exists($directAncestor->getData(), $seen)) {
          array_push($ancestors, $directAncestor);
          $seen[$directAncestor->getData()] = true;
          array_push($post, $directAncestor->getAllAncestorsRecurse($ancestors, $seen, $iteration+1, $limit));
        }
      }
      foreach ($post as $rec) {
        $ancestors = array_merge($ancestors, $rec);
      }
    }
    return $ancestors;
  }

  function hasEdge($edge) {
    if ($edge instanceof GraphMLNode) $edge = $edge->getData();
    foreach ($this->getEdges() as $contained) {
      if ($contained->getData() == $edge) {
        return true;
      }
    }

    return false;
  }

  function hasEdgeTo($edge) {
    if ($edge instanceof GraphMLNode) $edge = $edge->getData();
    foreach ($this->getEdgesTo() as $contained) {
      if ($contained->getData() == $edge) {
        return true;
      }
    }

    return false;
  }

  function hasEdgeFrom($edge) {
    if ($edge instanceof GraphMLNode) $edge = $edge->getData();
    foreach ($this->getEdgesFrom() as $contained) {
      if ($contained->getData() == $edge) {
        return true;
      }
    }

    return false;
  }

  /*
   * Find all the nodes in the network
   */
  function gatherNetwork() {
    $seen = array();
    $this->gatherNetworkRecurse($this, $seen);
    return array_values($seen);
  }

  private function gatherNetworkRecurse($node, &$seen) {
    if (array_key_exists($node->getData(), $seen)) return;
    $seen[$node->getData()] = $node;
    foreach ($node->getEdges() as $edgeNode) {
      $this->gatherNetworkRecurse($edgeNode, $seen);
    }
  }

  /*
   * Return a copy of this node with no connecions
   */
  function detach($node=null) {
    $detached = new GraphMLNode($this->data);
    if ($node) {
      foreach ($this->getEdgesFrom() as $edge) {
        if ($edge->getData() != $node->getData()) {
          $detached->addEdge($edge->detach());
        }
      }
      foreach ($this->getEdgesTo() as $edge) {
        if ($edge->getData() != $node->getData()) {
          $edge = $edge->detach();
          $edge->addEdge($detached);
        }
      }
    }
    return $detached;
  }

  function detachExcept($node, $removeTargetEdges=true) {
    $detached = $this->detach();
    foreach ($this->getEdgesTo() as $edge) {
      if ($edge == $node) {
        $edge = $removeTargetEdges ? $edge->detach() : $edge;
        $detached->edges['from'] = $edge;
        break;
      }
    }
    return $detached;
  }

  function __clone() {
    $clone = $this->detach();
    $edges = array(
      'to'   => array(),
      'from' => array()
    );
    foreach ($this->edges['to'] as $edge) {
      array_push($edges['to'], $edge);
    }
    foreach ($this->edges['from'] as $edge) {
      array_push($edges['from'], $edge);
    }

    $clone->edges = $edges;
    return $clone;
  }

  function __toString() {
    return $this->data;
  }
}

abstract class GraphMLNetwork {
  protected $manifest;

  function __construct($node=null) {
    $manifest = array();
    if (!is_null($node) && isset($node) && method_exists($node,"gatherNetwork")) { // traverse node relations to build network
      $network = $node->gatherNetwork();
      foreach ($network as $node) {
        $manifest[$node->getData()] = $node;
      }
    }
    $this->manifest = $manifest;
  }

  function addEdge($node1, $node2) {
    $this->manifest[$node1->getData()] = $node1;
    $this->manifest[$node2->getData()] = $node2;
    array_push($this->manifest, $node);
  }

  function hasNode($node) {
    return array_key_exists($this->getNodeName($node), $this->manifest);
  }

  function getNode($node) {
    $nodeName = $this->getNodeName($node);
    if ($this->hasNode($nodeName)) {
      return $this->manifest[$nodeName];
    }
    if (nodeInDB($node)){
        $node_str = "<a href='/data_center/stock/$node/'>$node</a>";
        throw new NodeNotFoundException("Node \"$node_str\" does not have pedigree data but still exists in our database.");
    }
    throw new NodeNotFoundException("Node \"$node\" not found in network");
    
  }

  abstract function findShortestPath($nodeA, $nodeB);

  /*
   * Find the name for a node. If $node is a string, that is returned instead
   */
  private function getNodeName($node) {
    if (!@get_class($node)) {
      return $node;
    }
    return $node->getData();
  }
}

/*
 * An undirected network of GraphMLNodes
 *
 */
class UndirectedGraphMLNetwork extends GraphMLNetwork {

  /*
   * Find the shortest path between two undirected nodes using Dijkstra's Algorithm where
   * every edge has the same weight
   */
  function findShortestPath($nodeA, $nodeB) {
    $nodeA = $this->getNode($nodeA);
    $nodeB = $this->getNode($nodeB);
    $curr = $this->getNode($nodeA);

    $S = array();
    $network = $this->mapNetwork($curr->gatherNetwork());
    foreach (array_keys($network) as $key) {
      $Q[$key] = PHP_INT_MAX;
    }
    $Q[$nodeA->getData()] = 0;

    while (!empty($Q)) {
      $min = array_search(min($Q), $Q);
      if ($min == $nodeB) break;

      foreach ($network[$min]->getEdges() as $neighbor) {
        $key = $neighbor->getData();
        if (!empty($Q[$key])) {
          $alt = $Q[$min] + 1;
          if ($alt < $Q[$key]) {
            $Q[$key] = $alt;
            $S[$key] = array($min, $alt);
          }
        }
      }

      unset($Q[$min]);
    }

    $path = array();
    $pos = $nodeB->getData();
    while ($pos != $nodeA->getData()) {
      array_push($path, $network[$pos]);
      $pos = $S[$pos][0];
    }
    array_push($path, $nodeA);
    return array_reverse($path);
  }

  /*
   * Create a network map from nodes for O(1) access
   */
  private function mapNetwork($nodes) {
    $mapped = array();
    foreach ($nodes as $node) {
      $mapped[$node->getData()] = $node;
    }
    return $mapped;
  }
}

/*
 * A single, connected network of GraphMLNodes
 */
class DirectedGraphMLNetwork extends GraphMLNetwork {

  /*
   * Find the shortest path between two nodes in an unweighted digraph
   */
  function findShortestPath($nodeA, $nodeB) {
    $paths = array();
    $start = $this->getNode($nodeA);
    $this->getShortestPathRec($start, null, $this->getNode($nodeB), array(), $paths);
    $shortest = array();
    foreach ($paths as $path) {
      $shortestCount = count($shortest);
      if ($shortestCount < 1 || count($path) < $shortestCount) {
        $shortest = $path;
      }
    }

    return $shortest;
  }

  private function getShortestPathRec($currNode, $prevNode, $dest, $path, &$paths) {
    array_push($path, $currNode->detachExcept($prevNode));
    if ($currNode == $dest) {
      array_push($paths, $path);
      return;
    }
    foreach ($currNode->getEdgesFrom() as $edge) {
      $this->getShortestPathRec($edge, $currNode, $dest, $path, $paths);
    }
  }

}

/*
 * GraphML parser/formatter for cytoscape.js
 *  Usage:
 *    $formatter = new GraphMLFormatter();
 *    $json = $formatter->parseCSVFile('graph.csv')->toJSON();
 * Author: Bremen Braun
 */
class GraphMLFormatter {
  private $READLEN = 4096;
  private $manifest;

  const GRAPHML   = 0;
  const CSV       = 1;
  const ADHOC     = 2;
  const GUESS_FMT = 3;
  const JSON      = 4;

  function __construct($source=null, $format=GraphMLFormatter::GRAPHML, $isFile=true) {
    $this->manifest = array(); // constant-time lookup

    if ($source) {
      if ($isFile) {
        $this->parseFile($source, $format);
      }
      else {
        $this->parse($source, $format);
      }
    }
  }

  function parse($source, $format=GraphMLFormatter::GRAPHML) {
    if ($format == GraphMLFormatter::GUESS_FMT) $format = $this->guessFormat($source);

    switch ($format) {
      case GraphMLFormatter::GRAPHML:
        $this->parseGraphML($source);
        break;
      case GraphMLFormatter::CSV:
        $this->parseCSV($source);
        break;
      case GraphMLFormatter::JSON:
        $this->parseJSON($source);
        break;
      default:
        throw new Exception("Can't parse source; got unknown format");
    }
  }

  function parseFile($file, $format=GraphMLFormatter::GRAPHML) {
    if ($format == GraphMLFormatter::GUESS_FMT) $format = $this->guessFormatFromFile($file);

    switch ($format) {
      case GraphMLFormatter::GRAPHML:
        $this->parseGraphMLFile($file);
        break;
      case GraphMLFormatter::CSV:
        $this->parseCSVFile($file);
        break;
      default:
        throw new Exception("Can't parse source; got unknown format");
    }
  }

  function containsNode($node) {
    return array_key_exists($node, $this->manifest);
  }

  function getNode($node) {
    return $this->containsNode($node) ? $this->manifest[$node] : $node;
  }

  /*
   * Filter the network to a list of nodes, retaining only edges for nodes which
   *  are in the network
   */
  function filterToNodes($nodes) {
    $filteredManifest = array();

    // Add nodes
    foreach ($nodes as $node) {
      if (($node = $this->getNode($node))) {
          if (isset($node) && method_exists($node,'getData')){
              $filteredManifest[$node->getData()] = $node->detach();
          }
      }
    }

    // Add edges
    foreach ($filteredManifest as $name => $detached) {
      $node = $this->getNode($name);

      foreach ($node->getEdgesFrom() as $from) {
        $fromName = $from->getData();
        if (in_array($fromName, $nodes)) {
          $detached->addEdge($filteredManifest[$fromName]);
        }
      }
      foreach ($node->getEdgesTo() as $to) {
        $toName = $to->getData();
        if (in_array($toName, $nodes)) {
          $filteredManifest[$toName]->addEdge($detached);
        }
      }
    }
    $this->manifest = $filteredManifest;
  }

  /*
   * Find the least (lowest) common ancestor between two nodes
   */
  function findLeastCommonAncestor($node1, $node2, $limit=100) {
    $node1_src = $this->getNode($node1);
    $node2_src = $this->getNode($node2);
    $map1 = array();
    $map2 = array();
    $mapped = array();
    if (!($node1_src && isset($node1_src) && method_exists($node1_src,'getAllAncestors'))){
      if (nodeInDB($node1)){
        $node_str = "<a href='/data_center/stock/$node1/'>$node1</a>";
        throw new NodeNotFoundException("Node \"$node_str\" does not have pedigree data but still exists in our database.");
      }
      throw new Exception('Node "' . $node1 . '" not found in network');
    }
    if (!($node2_src && isset($node2_src) && method_exists($node2_src,'getAllAncestors'))){
      if (nodeInDB($node2)){
        $node_str = "<a href='/data_center/stock/$node2/'>$node2</a>";
        throw new NodeNotFoundException("Node \"$node_str\" does not have pedigree data but still exists in our database.");
      }
      throw new Exception('Node "' . $node2 . '" not found in network');
    }
      $node1Ancestors = $node1_src->getAllAncestors($limit);
      $node2Ancestors = $node2_src->getAllAncestors($limit);

      foreach ($node1Ancestors as $ancestor) {
        $mapped[$ancestor->getData()] = $ancestor;
        array_push($map1, $ancestor->getData());
      }
      foreach ($node2Ancestors as $ancestor) {
        $mapped[$ancestor->getData()] = $ancestor;
        array_push($map2, $ancestor->getData());
      }
    $common = array_values(array_intersect($map1, $map2));
    return $mapped[$common[0]];
  }

  /*
   * Build a network rooted around the least common ancestor of two nodes
   */
  function filterToLeastCommonAncestor($node1, $node2, $limit=100) {
    $lca = $this->findLeastCommonAncestor($node1, $node2, $limit);

    $this->filterToNetwork($lca);
  }

  /*
   * Filter to a network built around nodes
   */
  function filterToNetwork($node) {
    $manifest = array();
    $node = $this->getNode($node);

    if ($node && isset($node) && method_exists($node,'getData')) {
      $manifest[$node->getData()] = $node;
      foreach ($node->getEdgesFrom() as $edge) {
        $edge = $edge->detach();
        $node->addEdge($edge);
        $manifest[$edge->getData()] = $edge;
      }
      foreach ($node->getEdgesTo() as $edge) {
        $edge = $edge->detach();
        $edge->addEdge($node);
        $manifest[$edge->getData()] = $edge;
      }
    }
    $this->manifest = $manifest;
  }

  /*
   * Find the shortest path from $node1 to $node2. If $store is true, the
   * internal state of this formatter is set to the network represented by the
   * shortest path.
   */
  function filterToMinimumDistance($node1, $node2, $store=false) { // HIRO
    $node1 = $this->getNode($node1);
    $node2 = $this->getNode($node2);

    $network = new UndirectedGraphMLNetwork($node1);
    $shortest = $network->findShortestPath($node1, $node2); // shortest path as an array; need to built it into a network structure

    if ($store) { // store the shortest path as the internal structure of this network
      $manifest = array(); // if no shortest path is found, the manifest is empty
      if ($shortest) {
        $manifest = $this->mapNetwork($shortest);
      }
      $this->manifest = $manifest;
    }
    return $shortest;
  }

  private function mapNetwork($nodes) {
    $mapped = array();
    foreach ($nodes as $node) {
      $mapped[$node->getData()] = $node;
    }
    return $mapped;
  }

  /*
   * Export the state of this object as a JSON string in the format expected by
   * cytoscape:
   *
   * {
   *   "nodes": [
   *     "data": {
   *       "id": "parentNode1",
   *       "name": "parentNode1"
   *     },
   *     "data": {
   *       "id": "childNode1",
   *       "name": "childNode1"
   *     }
   *     // so on...
   *   ],
   *   "edges": [
   *     "data": {
   *       "id": "parentNode1-childNode1",
   *       "source": "parentNode1",
   *       "target": "childNode1"
   *     },
   *     "data": {
   *     	"id": "parentNode1-childNode2",
   *     	"source": "parentNode1",
   *     	"target": "childNode2"
   *     }
   *     // so on...
   *   ]
   * }
   */
  function toJSON() {
    $data = array(
      'nodes' => array(),
      'edges' => array()
    );
    foreach ($this->manifest as $name => $node) {
      array_push($data['nodes'], array(
        'data' => array(
          'id'   => $name,
          'name' => $name
        )
      ));

      foreach ($node->getEdgesFrom() as $edge) {
        $edgeName = $edge->getData();
        array_push($data['edges'], array(
          'data' => array(
            'id'     => $name . '-' . $edgeName,
            'source' => $name,
            'target' => $edgeName
          )
        ));
      }
    }

    return json_encode($data);
  }

  /*
   * Export the state of this object as a CSV string
   */
  function toCSV() {
    $lines = array();
    foreach ($this->manifest as $name => $node) {
      foreach ($node->getEdgesFrom() as $edge) {
        $target = $edge->getData();

        array_push($lines, "$name,$target");
      }
    }
    return join("\n", $lines);
  }

  function getNodeList() {
    return array_keys($this->manifest);
  }

  function getNodeCount() {
    return count($this->getNodeList());
  }

  /*
   * This edge count assumes a connected graph, thus only counting edges from
   * nodes in order to avoid counting duplicate edges in the opposite direction
   */
  function getEdgeCount() {
    $count = 0;
    foreach ($this->manifest as $nodeName => $node) {
      $count += count($node->getEdgesFrom());
    }

    return $count;
  }

  // http://homepage.cs.uiowa.edu/~sriram/196/spring12/lectureNotes/Lecture10.pdf
  // http://www.iaees.org/publications/journals/nb/articles/2011-1(3-4)/an-algorithm-for-calculation-of-degree-distribution.pdf
  function getDegreeDistribution() {
    // TODO
  }

  private function parseCSVFile($file) {
    $fp = fopen($file, 'r');
    if ($fp === false) throw new Exception("Can't open file $file for reading");

    return $this->parseCSV(file_get_contents($file));
  }

  /*
   * Read CSV data into internal data field so it can be exported as some other
   * format
   */
  private function parseCSV($csv) {
    $lines = explode(PHP_EOL, $csv);
    foreach ($lines as $line) {
      if (!$line) continue;
      list($parent, $child) = str_getcsv($line);

      if (!$this->containsNode($child)) {
        $this->manifest[$child] = new GraphMLNode($child);
      }
      if (!$this->containsNode($parent)) {
        $this->manifest[$parent] = new GraphMLNode($parent);
      }

      $child = $this->getNode($child);
      $parent = $this->getNode($parent);
      $parent->addEdge($child);
    }
  }

  /*
   * Opposite action of toJSON
   */
  private function parseJSON($json) {
    $this->manifest = array();
    $json = json_decode($json, true);
    foreach ($json['nodes'] as $node) {
      $name = $node['data']['name'];
      $this->manifest[$name] = new GraphMLNode($name);
    }
    foreach ($json['edges'] as $edge) {
      $data =  $edge['data'];
      $source = $data['source'];
      $target = $data['target'];

      if (!array_key_exists($source, $this->manifest)) {
        $this->manifest[$source] = new GraphMLNode($source);
      }
      if (!array_key_exists($target, $this->manifest)) {
        $this->manifest[$target] = new GraphMLNode($target);
      }
      $sourceNode = $this->manifest[$source];
      $targetNode = $this->manifest[$target];
      $sourceNode->addEdge($targetNode);
    }
  }

  private function guessFormat($src) {
    if ($src[0] == '{') {
      return GraphMLFormatter::JSON;
    }
    $lines = substr_count($src, "\n") + 1;
    if (substr_count($src, ',') == $lines) {
      return GraphMLFormatter::CSV;
    }
    if ($src[0] == '<') return GraphMLFormatter::GRAPHML;
    return 'unknown';
  }

  private function guessFormatFromFile($file) {
    $line = "";
    $fp = fopen($file, 'r');
    if ($fp !== false) {
      $line = trim(fgets($fp));
    }

    $format = $this->guessFormat($line);
    fclose($fp);
    return $format;
  }

  /* UNIMPLEMENTED */

  private function parseGraphMLFile($file) {
    die("UNIMPLEMENTED"); // The below code needs to be adapted to the new network structure
    $parser = xml_parser_create();
    $fp = fopen($file, 'r');
    if ($fp === false) throw new Exception("Can't open file $file for reading");

    $state = 'nodes';
    $json = $this->initData();
    xml_set_element_handler($parser, function($parser, $elementName, $attrs) use (&$json, &$state) {
      switch ($elementName) {
        case 'NODE':
          $state = 'nodes';
          array_push($json[$state], array(
            'data' => array(
              'id' => $attrs['ID']
            )
          ));
          break;
        case 'EDGE':
          $state = 'edges';
          array_push($json[$state], array(
            'data' => array(
              'id'     => "$source-$target",
              'source' => $attrs['SOURCE'],
              'target' => $attrs['TARGET']
            )
          ));
          break;
      }
    }, function() {});
    xml_set_character_data_handler($parser, function($p, $data) use (&$json, $state) {
      $data = trim($data);
      if (!$data) return;
      if ($state != 'nodes') return;

      $element = array_pop($json[$state]);
      $element['data']['name'] = $data;
      array_push($json[$state], $element);
    });

    while ($data = fread($fp, $this->READLEN)) {
      if (xml_parse($parser, $data, feof($fp)) === false) {
        throw new Exception(sprintf("XML Error: %s at line %d", xml_error_string(xml_get_error_code($parser)), xml_get_current_line_number($parser)));
      }
    }

    xml_parser_free($parser);
    fclose($fp);
    return $json;
  }

  private function parseGraphML($graphML) {
    die("UNIMPLEMENTED");
  }
  

}


  function nodeInDB($node) {
    $DBConn = connect_to_database();
    $sql = "SELECT NAME FROM STOCK WHERE NAME LIKE '$node'";
    $stmt = make_query($DBConn, $sql);
    return retrieve_row($stmt);
  }


?>
