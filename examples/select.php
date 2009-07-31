<?php
require '../lib/FourStore/Store.php';
require '../lib/FourStore/Namespace.php';
require '/usr/share/php/libzend-framework-php/Zend/Loader.php';
spl_autoload_register(array('Zend_Loader', 'autoload'));

$s = new FourStore_Store('http://dbtune.org/beancounter/sparql/');
$r = $s->select('SELECT ?a ?b ?c WHERE {?a ?b ?c} LIMIT 10');
var_dump($r);
?>
