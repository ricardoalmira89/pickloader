include 'vendor/autoload.php';
include "src/AlmPick.php";

$pick = new AlmPick(['load-cached' => false, 'sort' => 'DESC']);
$data = $pick->load();

dump(\Alm\AlmArray::head($data));

//------
$pick->dumpCsv('resources/pick.csv', array(
    'fecha', 'noche_fijo_b1'
));


//--- Los campos son estos:
$fields = ['fecha', 
    'dia', 
    'dia_centena',
    'dia_fijo', 
    'dia_corrido', 
    'dia_corrido1', 
    'dia_corrido2', 
    'noche', 
    'noche_centena', 
    'noche_centena',
    'noche_fijo',
    'noche_fijo_b1',
    'noche_fijo_b2',
    'noche_corrido1', 
    'noche_corrido1_b1',
    'noche_corrido1_b2',
    'noche_corrido2',
    'noche_corrido2_b1', 
    'noche_corrido2_b2'
];
