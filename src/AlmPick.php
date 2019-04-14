<?php

use Alm\AlmArray;

class AlmPick
{

    private $pick = array(
        'pick3' => 'http://www.flalottery.com/exptkt/p3.htm',
        'pick4' => 'http://www.flalottery.com/exptkt/p4.htm'
    );

    private $behavior = array(
        'load-cached' => false,
        'sort' => 'ASC'
    );

    public function __construct($behavior = [])
    {
        if (AlmArray::get($behavior, 'load-cached'))
            $this->behavior['load-cached'] = $behavior['load-cached'];

        if (AlmArray::get($behavior, 'sort'))
            $this->behavior['sort'] = $behavior['sort'];
    }

    /**
     * Guarda el array en la ubicacion especificada como json (hash)
     * @param $filename
     */
    public function dumpJson($filename){
        $data = $this->load();
        dump('Saving json to: '. $filename);
        AlmArray::saveToFile($data, $filename);
    }

    /**
     * Convierte el array a [ ['fecha' => 'xxx', 'dia' => 'xxx', 'noche'], ['fecha' => 'xxx', 'dia' => 'xxx', 'noche'] ]
     * @param $data
     * @return array
     */
    private function toFlattern($data){

        if ($this->getBehavior('load-cached'))
            return $this->toFlatternCached();

        $result = [];
        foreach ($data as $fecha => $tiros){
            $result[] = array(
                'fecha' => $fecha,
                'dia' => isset($tiros['M']) ? $tiros['M'] : null,
                'noche' => $tiros['E'],
                'noche_centena' => $tiros['E'][0],
                'noche_fijo' => $tiros['E'][1].$tiros['E'][2],
                'noche_corrido1' => $tiros['E'][3].$tiros['E'][4],
                'noche_corrido2' => $tiros['E'][5].$tiros['E'][6]
            );
        }

        AlmArray::saveToFile($result, 'resources/flattern.json');

        return $result;
    }

    /**
     * Devuelve un array con todos los numeros cargados desde el servidor oficial.
     * Desde el primer dia hasta hoy.
     *
     * @return array
     */
    public function load(){

        $data3 = $this->loadPick('pick3');
        $data4 = $this->loadPick('pick4');

        dump('Merging results');

        ///--- aqui se necesita organizar los resultados porque si no es una mierda todo

        $result = [];
        foreach ($data3 as $key => $value){

            if (isset($value['E']) && isset($data4[$key]['E']))
                $result[$key]['E'] = $value['E'] . $data4[$key]['E'];

            if (isset($value['M']) && isset($data4[$key]['M']))
                $result[$key]['M'] = $value['M'] . $data4[$key]['M'];
        }

        unset($data3, $data4);

        dump('Creating Flattern array...');
        $result = $this->toFlattern($result);

        dump('Sorting Results');
        $result = $this->sort($result, $this->getBehavior('sort'));

        return $result;
    }

    private function loadPick($pick){

        $data = $this->extract($pick);
        AlmArray::saveToFile($data, sprintf('resources/matches.%s.json', $pick));

        $data2 = $this->buildArray($data, $pick);
        AlmArray::saveToFile($data2, sprintf('resources/build.%s.json', $pick));

        return $data2;
    }

    /**
     * Extrae un array feo de pick3.com
     * @return mixed
     */
    private function extract($pick){
        dump('Extracting '. $pick);

        if ($this->getBehavior('load-cached'))
            return $this->extractCached($pick);

        $data = file_get_contents($this->pick[$pick]);
        $data = preg_replace('/\&nbsp;/', '', $data);
        preg_match_all('/\>(\d+\/*\d*\/*\d*|\&nbsp;M|\&nbsp;E|E|M)\</', $data, $matches);

        return $matches[1];
    }

    private function buildArray($data, $pick){
        dump('Building  '. $pick);
        $result = [];

        if ($this->getBehavior('load-cached'))
            return $this->buildCached($pick);

        $i = 0;
        while ($i <= count($data) - 1){

            if ($this->isDate($data[$i])){

                try{

                    $result[$data[$i]][$data[$i + 1]] = ($pick == 'pick3')
                        ? $data[$i + 2] . $data[$i + 3]. $data[$i + 4]
                        : $data[$i + 2] . $data[$i + 3]. $data[$i + 4]. $data[$i + 5];

                } catch (\Exception $ex){
                    dump($ex);
                    die();
                }

            }

            $i++;
        }

        return $result;
    }

    private function sort($data, $sorting = 'ASC'){

        if ($this->getBehavior('load-cached'))
            return $this->sortCached();

        $left = ($sorting == 'DESC') ? 'a' : 'b';
        $right = ($sorting == 'DESC') ? 'b' : 'a';

        usort($data, function($a, $b) use ($left, $right) {
            return
                date_create_from_format('m/d/y H:i:s', $$right['fecha'].' 00:00:00')->getTimestamp()
                <=>
                date_create_from_format('m/d/y H:i:s', $$left['fecha'].' 00:00:00')->getTimestamp();
        });

        AlmArray::saveToFile($data, 'resources/sorted.json');

        return $data;
    }

    /**
     * Determina si el item es una fecha
     * @param $item
     * @return bool
     */
    private function isDate($item){
        preg_match('/\d+\/\d+\/\d+/', $item, $match);
        return count($match) > 0;
    }

    private function getBehavior($name){
        return AlmArray::get($this->behavior, $name, false);
    }

    //----------------------------CACHED STUFF ------

    private function extractCached($pick){
        return AlmArray::loadFromFile(sprintf('resources/matches.%s.json', $pick));
    }

    private function buildCached($pick){
        return AlmArray::loadFromFile(sprintf('resources/build.%s.json', $pick));
    }

    private function toFlatternCached(){
        return AlmArray::loadFromFile('resources/flattern.json');
    }

    private function sortCached(){
        return AlmArray::loadFromFile('resources/sorted.json');
    }


}