<?php

/**
*
*/
class KAsignaciones extends CI_Model{


	function __construct(){
		parent::__construct();
		if(!isset($this->Dbpace)) $this->load->model('comun/Dbpace');
    }

	
	public function Cargar( $map = array() ){	
		
        $query = "SELECT cedula, conc, fnxc, tipo, familiar  from space.asig_deduc WHERE fini >= '" . $map["fechainicio"]  . "' AND ffin <= '" . $map["fechafin"] . "'";
		
		$obj = $this->Dbpace->consultar($query);
		$rs = $obj->rs;
        $arr = array();
		foreach ($rs as $c => $v) {
			$clave = $v->cedula;
			
            $asignacion = array(				
				'tipo' => $v->tipo,	//Descripcion de la medidaa
				'fnxc' => $v->fnxc, // Formula monto
			);
			if ( $v->familiar != "" ){
				$clave = $v->cedula . "|" . $v->familiar;
			}
			
			$arr[$clave][$v->conc] = $asignacion;
        }
		return $arr;
	}


	public function Ejecutar($sb = 0.00, $estatus = 0, $fnx = ''){
		$aguinaldos = 0;
		$bono_recreacional = 0;
		$sueldo_mensual = $sb;

		if ($fnx != '') {
			try {
				$sueldo_base = $sb;
				eval('$valor = ' . $fnx);
			} catch (Throwable $t) {
				$valor = 0;
			}			
			//print_r($sueldo_mensual);
			return round($valor,2);
		}else{
			return 0;
		}


		
	}

	public function Suspender($id = '', $estatus = 0){
		if ($id != ''){
			$sModificar = 'UPDATE space.medidajudicial SET status_id = ' . $estatus . '  WHERE id=' . $id;
			$this->Dbpace->consultar($sModificar);
		}
	}
}
