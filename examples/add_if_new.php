<?php
require '../lib/FourStore/Store.php';
require '../lib/FourStore/Namespace.php';
require '/usr/share/php/libzend-framework-php/Zend/Loader.php';
spl_autoload_register(array('Zend_Loader', 'autoload'));

$s = new FourStore_Store('http://dbtune.org/beancounter/sparql/');
$r = $s->add_if_new('http://github.com/moustaki/4store-php', "
	<http://moustaki.org/foaf.rdf#moustaki> foaf:nick \"moustaki\".
");
var_dump($r);

?>
