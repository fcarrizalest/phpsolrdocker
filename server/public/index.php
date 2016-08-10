<?php 

require '../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', true);


$config = array(
    'adapteroptions' => array(
        'host' => 'solr',
        'port' => 8983,
        'path' => '/solr/core/',
    )
);

// create a client instance
$client = new Solarium_Client($config);


if( isset($_GET['crea']) ){

	file_put_contents('./p.txt', '0');

	$clientes = file_get_contents( './clientes.json' );

$arrayC = json_decode($clientes);



$total = count($arrayC);

$i = 0 ;


foreach ($arrayC as $key => $cliente) {
$i++;

$porcentaje = $i * 100 / $total ;

    file_put_contents('./p.txt', number_format($porcentaje ,2) );





	// // get an update query instance
$update = $client->createUpdate();
// create a new document for the data
$doc1 = $update->createDocument();
$doc1->id = $cliente->id;
$doc1->nombre = $cliente->nombre;
$doc1->identificacion = $cliente->identificacion;
$doc1->activo = $cliente->activo;
$doc1->calle_y_numero = $cliente->calle_y_numero;
$doc1->celular = $cliente->celular;
$doc1->codigo_postal = $cliente->codigo_postal;
$doc1->colonia = $cliente->colonia;
$doc1->correo = $cliente->correo;
$doc1->estado_id = $cliente->estado_id;
$doc1->estatus_id = $cliente->estatus_id;
$doc1->municipio_id = $cliente->municipio_id;
$doc1->num_identificacion = $cliente->num_identificacion;
$doc1->sucursal_id = $cliente->sucursal_id;
$doc1->telefono = $cliente->telefono;

$doc1->busqueda =  $cliente->nombre .  " ". $cliente->telefono . " " .  $cliente->num_identificacion . " " . $cliente->correo. " " . $cliente->colonia . " " .$cliente->codigo_postal . " ".  $cliente->celular ." " . $cliente->identificacion . " " . $cliente->calle_y_numero ;
$update->addDocuments(array($doc1));
$update->addCommit();
	# code...
$result = $client->update($update);

	echo '<b>Update query executed</b><br/>';
    echo 'Query status: ' . $result->getStatus(). '<br/>';
    echo 'Query time: ' . $result->getQueryTime();

}

die();
}

if( isset( $_POST['draw']) ){

	$data = array();
	$data['draw'] = $_POST['draw'];
	$data['sucursal_id'] =  ( isset($_POST['sucursal_id']) )?$_POST['sucursal_id']:-1 ;

	$data['estado_id'] =  ( isset($_POST['estado_id']) )?$_POST['estado_id']:-1 ;
	$data['municipio_id'] =  ( isset($_POST['municipio_id']) )?$_POST['municipio_id']:-1 ;
	$data['status_id'] =  ( isset($_POST['status_id']) )?$_POST['status_id']:-1 ;


	$data['data'] = array();

	$query = $client->createSelect();



	$query->setStart($_POST['start'])->setRows( $_POST['length'] );
	$consulta = "*:* ";

	if( $data['sucursal_id'] > 0 ){

		$consulta .= ' AND ( sucursal_id: '.$data['sucursal_id'].')  ';

	}


	if( $data['estado_id'] > 0 ){

		$consulta .= ' AND ( estado_id: '.$data['estado_id'].')  ';

	}

	if( $data['municipio_id'] > 0 ){

		$consulta .= ' AND ( municipio_id: '.$data['municipio_id'].')  ';

	}


	if( $data['status_id'] > 0 ){

		$consulta .= ' AND ( status_id: '.$data['status_id'].')  ';

	}



	if( !empty( $_POST['search']['value'] )){
		

		$consulta .= ' AND ( ';

		//foreach ($_POST['columns'] as $key => $column) {
			
			//if( $column['searchable'] == "true" ){
				$palabras = explode( " " , $_POST['search']['value'] );

				if( count( $palabras) > 0 ){

					foreach ($palabras as $key => $palabra) {
						
						$consulta .=  ' ( busqueda:*'.$palabra.'*)';
					}
				}
			//}

		//}

		$consulta .= ')';
	}

	if( !empty($consulta)){
		$data['consulta'] = $consulta;
		$query->setQuery( $consulta  );
	}

	$query->addSort("score" , Solarium_Query_Select::SORT_DESC);
	foreach ($_POST['order'] as $key => $value) {
			
			
			if( $value['dir'] == 'asc' ){
				$query->addSort($_POST['columns'][$value['column']]['data'] , Solarium_Query_Select::SORT_ASC);	
			}else{

				$query->addSort($_POST['columns'][$value['column']]['data'] , Solarium_Query_Select::SORT_DESC);	
			}
	}
	try {
		
	$facetSet = $query->getFacetSet();

	$facetSet->createFacetField('Sucursal')->setField('sucursal_id');
	$facetSet->createFacetField('Estado')->setField('estado_id');
	$facetSet->createFacetField('Status')->setField('estatus_id');
	$facetSet->createFacetField('Municipio')->setField('municipio_id');

	$resultset = $client->select($query);
	

	$data['sucursales'] = array();

	$facet = $resultset->getFacetSet()->getFacet('Sucursal');
	foreach($facet as $value => $count) {
		if( $count > 0)
	    $data['sucursales'][] = array( 'key' => $value , 'label' => $value . ' [' . $count . ']'  )  ;
	}

	$data['Estado'] = array();

	$facet = $resultset->getFacetSet()->getFacet('Estado');
	foreach($facet as $value => $count) {
		if( $count > 0)
	    $data['Estado'][] = array( 'key' => $value , 'label' => $value . ' [' . $count . ']'  )  ;
	}

	$data['Status'] = array();

	$facet = $resultset->getFacetSet()->getFacet('Status');
	foreach($facet as $value => $count) {
		if( $count > 0)
	    $data['Status'][] = array( 'key' => $value , 'label' => $value . ' [' . $count . ']'  )  ;
	}

	$data['Municipio'] = array();

	$facet = $resultset->getFacetSet()->getFacet('Municipio');
	foreach($facet as $value => $count) {
		if( $count > 0)
	    $data['Municipio'][] = array( 'key' => $value , 'label' => $value . ' [' . $count . ']'  )  ;
	}

	$data["recordsTotal"]= $resultset->getNumFound();
	$data["recordsFiltered"] = $resultset->getNumFound();

	foreach ($resultset as $document) {

		$row = array();

		foreach($document as $field => $value){
			$row[$field]=$value;

    	}

    	$data['data'][] = $row;

	}
	} catch (Exception $e) {
		$data["recordsTotal"]= 0;
		$data["recordsFiltered"] = 0;



	}

	echo json_encode($data);

	die();
}
?>
<html>
<head>
	<script   src="https://code.jquery.com/jquery-3.1.0.min.js"   integrity="sha256-cCueBR6CsyA4/9szpPfrX3s49M9vUU5BgtiJj06wt/s="   crossorigin="anonymous"></script>
	
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css">
	<script src="//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js" ></script>
	<title></title>
	<style type="text/css">

	#clientes{

		margin: 0px auto;
		width: 60%;
	}
	</style>
</head>
<body>

<button id="index" >INDEXAR</button><br><br><br>

Sucurales
<select id="sucursales">
<option value="-1">Todas</option>
</select>


Estado
<select id="Estado">
<option value="-1">Todas</option>
</select>



Municipio
<select id="Municipio">
<option value="-1">Todas</option>
</select>



Status
<select id="Status">
<option value="-1">Todas</option>
</select>
<hr>
<table id="clientes" class="display" cellspacing="0" >
	<thead>
		<tr>
			<th>id</th>
			<th>nombre</th>
			<th>identificacion</th>
			<th>activo</th>
			<th>calle_y_numero</th>
			<th>celular</th>
			<th>codigo_postal</th>
			<th>colonia</th>
			<th>correo</th>
			<th>estado_id</th>
			<th>estatus_id</th>
			<th>municipio_id</th>
			<th>num_identificacion</th>
			<th>sucursal_id</th>
			<th>telefono</th>
			
		</tr>
	</thead>

</table>

<script type="text/javascript">

$(document).ready(function() {

	function progress() {


	    	$.get('p.txt',function( data ){

	    		$('#index').html(data+"%");

	    	});
	}
	

	$('#index').click(function(){


		var myVar = setInterval(function(){ progress() }, 1000);


		$('#index').attr('disabled',true);


		$.get('index.php?crea',function(){

			$('#index').attr('disabled',false);
			clearInterval(myVar);
			$('#index').html("INDEXAR");
		});



	});

    var t = $('#clientes').DataTable( {
    	serverSide: true,
    	ajax: {
        	url: 'index.php',
        	type: 'POST'
    	},
    	columns:[
    		 { data: 'id' , searchable:false },
    		 { data: 'nombre' ,searchable:true },
    		{ data: 'identificacion' ,searchable:true },
			{ data: 'activo' ,searchable:false },
			{ data: 'calle_y_numero'  ,searchable:false },
			{ data: 'celular' ,searchable:true },
			{ data: 'codigo_postal' ,searchable:false },
			{ data: 'colonia' ,searchable:false },
			{ data: 'correo' ,searchable:false },
			{ data: 'estado_id' ,searchable:false },
			{ data: 'estatus_id' ,searchable:false },
			{ data: 'municipio_id' ,searchable:false },
			{ data: 'num_identificacion' ,searchable:false },
			{ data: 'sucursal_id' ,searchable:false },
			{ data: 'telefono' ,searchable:false },
			// { data: '_version_' ,searchable:false },
			// { data: 'score' ,searchable:false },
    	]
        
    } );

    t.on('preXhr',function(e, settings, data ){

    	console.log(data );

    	data['sucursal_id'] = $('#sucursales').val();
    	
    	data['estado_id'] = $('#Estado').val();
    	data['municipio_id'] = $('#Municipio').val();
    	data['status_id'] = $('#Status').val();


    } );

    t.on( 'xhr', function ( e, settings, json ) {
    	
    	console.log( 'Ajax event occurred. Returned data: ', json );

    	$('#sucursales').html('<option value="-1">Todas</option>');

    	for (var i = 0; i < json.sucursales.length; i++) {
    	 	var sucursal = json.sucursales[i];
    	 	var sel = "";

    	 	if(json.sucursal_id == sucursal.key ){
    	 		sel = "selected";
    	 	}
    	 	$('#sucursales').append('<option '+sel+' value="'+sucursal.key+'">'+ sucursal.label +'</option>');
    	 	console.log(sucursal);
    	 }; 



    	 $('#Estado').html('<option value="-1">Todas</option>');

    	for (var i = 0; i < json.Estado.length; i++) {
    	 	var sucursal = json.Estado[i];
    	 	var sel = "";

    	 	if(json.estado_id == sucursal.key ){
    	 		sel = "selected";
    	 	}
    	 	$('#Estado').append('<option '+sel+' value="'+sucursal.key+'">'+ sucursal.label +'</option>');
    	 	console.log(sucursal);
    	 }; 


    	 $('#Municipio').html('<option value="-1">Todas</option>');

    	for (var i = 0; i < json.Municipio.length; i++) {
    	 	var sucursal = json.Municipio[i];
    	 	var sel = "";

    	 	if(json.municipio_id == sucursal.key ){
    	 		sel = "selected";
    	 	}
    	 	$('#Municipio').append('<option '+sel+' value="'+sucursal.key+'">'+ sucursal.label +'</option>');
    	 	console.log(sucursal);
    	 }; 


    	 $('#Status').html('<option value="-1">Todas</option>');

    	for (var i = 0; i < json.Status.length; i++) {
    	 	var sucursal = json.Status[i];
    	 	var sel = "";

    	 	if(json.status_id == sucursal.key ){
    	 		sel = "selected";
    	 	}
    	 	$('#Status').append('<option '+sel+' value="'+sucursal.key+'">'+ sucursal.label +'</option>');
    	 	console.log(sucursal);
    	 }; 


	} );


    $('#sucursales').change(function(){
    	t.ajax.reload();

    });

    $('#Estado').change(function(){
    	t.ajax.reload();

    });

    $('#Municipio').change(function(){
    	t.ajax.reload();

    });

    $('#Status').change(function(){
    	t.ajax.reload();

    });
} );

</script>
</body>
</html><?php
die();
//$client->setAdapter('Solarium_Client_Adapter_Curl');// create a ping query
//$ping = $client->createPing();

// // execute the ping query
// try{
//     $result = $client->ping($ping);
//     echo 'Ping query successful';
//     echo '<br/><pre>';
//     var_dump($result->getData());
// }catch(Solarium_Exception $e){
//     echo 'Ping query failed';
// }




// // get an update query instance
// $update = $client->createUpdate();
// // optimize the index
// $update->addOptimize(true, false, 1);
// // this executes the query and returns the result
// $result = $client->update($update);
// echo '<b>Update query executed</b><br/>';
// echo 'Query status: ' . $result->getStatus(). '<br/>';
// echo 'Query time: ' . $result->getQueryTime();


// // get an update query instance
// $update = $client->createUpdate();
// // add the delete query and a commit command to the update query
// $update->addDeleteQuery('id:*2*');
// $update->addCommit();
// // this executes the query and returns the result
// $result = $client->update($update);
// echo '<b>Update query executed</b><br/>';
// echo 'Query status: ' . $result->getStatus(). '<br/>';
// echo 'Query time: ' . $result->getQueryTime();


// // get an update query instance
// $update = $client->createUpdate();
// // create a new document for the data
// $doc1 = $update->createDocument();
// $doc1->id = 1;
// $doc1->nombre = "Hola Mundo ";
// $update->addDocuments(array($doc1));
// $update->addCommit();


// $result = $client->update($update);
// echo '<b>Update query executed</b><br/>';
// echo 'Query status: ' . $result->getStatus(). '<br/>';
// echo 'Query time: ' . $result->getQueryTime();


// create a client instance
//$client = new Solarium_Client($config);
// get a select query instance
$query = $client->createSelect();

$limit =  ( isset( $_REQUEST['limit']))?$_REQUEST['limit']:10;

$query->setRows($limit);
$q = "";
if( isset( $_REQUEST['q'])  && ( !empty($_REQUEST['q'])  ) ){ 
	$query->setQuery($_REQUEST['q'] );
	$q = $_REQUEST['q'];
}


$facetSet = $query->getFacetSet();

// $facetSet = $facetSet->createFacetQuery('Estado');


// $facetSet->setField('estado_id');
$facetSet->createFacetField('Sucursal')->setField('sucursal_id');

$facetSet->createFacetField('Estado')->setField('estado_id');
$facetSet->createFacetField('Status')->setField('estatus_id');


$facetSet->createFacetField('Municipio')->setField('municipio_id');

//$facetSet->createFacetQuery('Estado2')->setQuery('estado_id: 19');

?>
<form>
	<input placeholder="buscar" value="<?php echo $q ?>" name="q"/>
	<select name="limit">
		<option value="<?php echo $limit ?>"> <?php echo $limit ?>  </option>
		<option value="10">10 </option>
		<option value="20">20 </option>
		<option value="40">40 </option>
		<option value="60">60 </option>
		<option value="100">100 </option>
		<option value="200">200 </option>
	</select>	

	<br>

	<input type="submit"/>
</form>
<?php

// this executes the query and returns the result
$resultset = $client->select($query);
// display the total number of documents found by solr
echo 'NumFound: '.$resultset->getNumFound();
// show documents using the resultset iterator




echo '<hr/>Facet counts for field "sucursal_id":<br/>';
$facet = $resultset->getFacetSet()->getFacet('Sucursal');
foreach($facet as $value => $count) {
    echo $value . ' [' . $count . ']<br/>';
}

echo '<hr/>Facet counts for field "estado_id":<br/>';
$facet = $resultset->getFacetSet()->getFacet('Estado');
foreach($facet as $value => $count) {
    echo $value . ' [' . $count . ']<br/>';
}

echo '<hr/>Facet counts for field "status":<br/>';
$facet = $resultset->getFacetSet()->getFacet('Status');
foreach($facet as $value => $count) {
    echo $value . ' [' . $count . ']<br/>';
}

echo '<hr/>Facet counts for field "municipio_id":<br/>';
$facet = $resultset->getFacetSet()->getFacet('Municipio');
foreach($facet as $value => $count) {
	if($count > 0)
    echo $value . ' [' . $count . ']<br/>';
}





$headers = true;
echo '<hr/><table>';
foreach ($resultset as $document) {
    

if( $headers ){
	echo '<tr>';
	foreach($document AS $field => $value)
    {

    	echo '<th>' . $field . '</th>';
    }
    echo '</tr>';
    $headers = false;
}
echo '<tr>';
    	
    // the documents are also iterable, to get all fields
    foreach($document AS $field => $value)
    {	


        // // this converts multivalue fields to a comma-separated string
        // if(is_array($value)) $value = implode(', ', $value);
        // if($headers ){
        // 	echo '<tr><th>' . $field . '</th></tr>';
        // 	$headers = false;
        // }else{
        // 	echo '</tr><tr>';

        // }
        

        echo '<td>' . $value . '</td>';
    }
     echo '</tr>';
   
}

 echo '</table>';


// get a suggester query instance
$query = $client->createSuggester();
$query->setQuery('ga da a'); //multiple terms
$query->setDictionary('suggest');
$query->setOnlyMorePopular(true);
$query->setCount(10);


// this executes the query and returns the result
$resultset = $client->suggester($query);
echo '<b>Query:</b> '.$query->getQuery().'<hr/>';
// display results for each term
foreach ($resultset as $term => $termResult) {
    echo '<h3>' . $term . '</h3>';
    echo 'NumFound: '.$termResult->getNumFound().'<br/>';
    echo 'StartOffset: '.$termResult->getStartOffset().'<br/>';
    echo 'EndOffset: '.$termResult->getEndOffset().'<br/>';
    echo 'Suggestions:<br/>';
    foreach($termResult as $result){
        echo '- '.$result.'<br/>';
    }
    echo '<hr/>';
}
// display collation
echo 'Collation: '.$resultset->getCollation();


?>