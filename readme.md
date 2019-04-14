include 'vendor/autoload.php';
include "src/AlmPick.php";

$pick = new AlmPick(['load-cached' => false, 'sort' => 'DESC']);
$data = $pick->load();

dump(\Alm\AlmArray::head($data));
