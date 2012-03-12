<?php
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'FourStore/Store.php';
require_once 'Zend/Http/Client.php';

class FourStore_StoreTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->client = $this->getMock('Zend_Http_Client');
        $this->store = new FourStore_Store('http://example.com/');
        $this->store->setClient($this->client);
    }
    public function testSelect() {
        $query = "SELECT ?src ?bobNick
            FROM NAMED <http://example.org/foaf/aliceFoaf>
            FROM NAMED <http://example.org/foaf/bobFoaf>
            WHERE
              {
                GRAPH ?src
                { ?x foaf:mbox <mailto:bob@work.example> .
                  ?x foaf:nick ?bobNick
                }
              }";

        $this->client->expects($this->once())
                    ->method('setParameterPost')
                    ->with('query', 'PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
' . $query);

        $this->client->expects($this->once())
                    ->method('setUri')
                    ->with('http://example.com/');

        $response = $this->getMock('Zend_Http_Response', array(), array(null, array()));

        $this->client->expects($this->once())
                    ->method('request')
                    ->with('POST')
                    ->will($this->returnValue($response));

        $response->expects($this->once())
                    ->method('getBody')
                    ->will($this->returnValue('<foo><result><binding name="hello">world</binding><binding name="goodbye">cruel world</binding></result><result><binding name="hello">world</binding><binding name="goodbye">world cruel</binding></result></foo>'));

        $this->assertSame(array(
            0 => array('hello' => 'world', 'goodbye' => 'cruel world'),
            1 => array('hello' => 'world', 'goodbye' => 'world cruel')
        ), $this->store->select($query));
    }



    // Does a SPARQL query and puts the results in a PHP array of hashes (keys are the selected SPARQL variables)
    public function select($query) {
        $client = $this->client;
        $client->setUri($this->_endpoint);
        $client->setParameterPost('query', FourStore_Namespace::to_sparql() . $query);
        $response = $client->request('POST');

        $body = $response->getBody();

        $doc = clone $this->document;
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
        $client = $this->client;
        $client->setUri($this->_endpoint);
        $client->setParameterPost('query', FourStore_Namespace::to_sparql() . $query);
        $response = $client->request('POST');

        $body = $response->getBody();

        $doc = clone $this->document;
        $doc->loadXML($body);
        $result = $doc->getElementsByTagName('boolean')->item(0)->textContent;
        if ($result == 'true') {
            return true;
        } else {
            return false;
        }
    }

    public function set($graph, $turtle) {
        $client = $this->client;
        $client->setUri($this->_endpoint . $graph);
        $client->setHeaders('Content-Type', 'application/x-turtle');
        $client->setRawData(FourStore_Namespace::to_turtle() . $turtle);
        $response = $client->request('PUT');

        return $response;
    }

    public function add($graph, $turtle) {
        $post_endpoint = array_shift(split("/sparql/", $this->_endpoint)) . "/data/";
        $client = $this->client;
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
        $client = $this->client;
        $client->setUri($this->_endpoint . $graph);
        $response = $client->request('DELETE');

        return $response;
    }
}
