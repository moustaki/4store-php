<?php

class FourStore_Store {

    private $_endpoint;

    public function __construct($endpoint) {
        $this->_endpoint = $endpoint;
    }

    // Does a SPARQL query and puts the results in a PHP array of hashes (keys are the selected SPARQL variables)
    public function select($query) {
        $client = new Zend_Http_Client();
        $client->setUri($this->_endpoint);
        $client->setParameterPost('query', FourStore_Namespace::to_sparql() . $query);
        $response = $client->request('POST');

        $body = $response->getBody();
        $doc = new DOMDocument();
        $doc->loadXML($body);
        $results = $doc->getElementsByTagName('result');

        $r = array();
        foreach($results as $result) {
            $bindings = $result->getElementsByTagName('binding');
            $t = array();
            foreach($bindings as $binding) {
                $t = array_merge($t, array($binding->getAttribute('name') => $binding->textContent));
            }
            $r[] = $t;
        }
        return $r;
    }

    public function ask($query) {
        $client = new Zend_Http_Client();
        $client->setUri($this->_endpoint);
        $client->setParameterPost('query', FourStore_Namespace::to_sparql() . $query);
        $response = $client->request('POST');

        $body = $response->getBody();
        $doc = new DOMDocument();
        $doc->loadXML($body);
        $result = $doc->getElementsByTagName('boolean')->item(0)->textContent;
        if ($result == 'true') {
            return true;
        } else {
            return false;
        }
    }

    public function set($graph, $turtle) {
        $client = new Zend_Http_Client();
        $client->setUri($this->_endpoint . $graph);
        $client->setHeaders('Content-Type', 'application/x-turtle');
        $client->setRawData(FourStore_Namespace::to_turtle() . $turtle);
        $response = $client->request('PUT');

        return $response;
    }

    public function add($graph, $turtle) {
        $post_endpoint = array_shift(split("/sparql/", $this->_endpoint)) . "/data/";
        $client = new Zend_Http_Client();
        $client->setUri($post_endpoint);
        $client->setParameterPost('graph', $graph);
        $client->setParameterPost('data', FourStore_Namespace::to_turtle() . $turtle);
        $client->setParameterPost('mime-type', 'application/x-turtle');
        $response = $client->request('POST');

        return $response;
    }

    public function add_if_new($graph, $turtle) {
        // FIXME - should it be scoped by graph?
        $exists = $this->ask(FourStore_Namespace::to_sparql() . "ASK WHERE { $turtle }");
        if (!$exists) {
            return $this->add($graph, $turtle);
        }
    }

    public function delete($graph) {
        $client = new Zend_Http_Client();
        $client->setUri($this->_endpoint . $graph);
        $response = $client->request('DELETE');

        return $response;
    }

}

?>
