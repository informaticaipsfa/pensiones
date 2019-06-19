<?php

/**
*
*/
class KArchivos extends CI_Model{


	function __construct(){
		parent::__construct();
		if(!isset($this->Dbpace)) $this->load->model('comun/Dbpace');
    }

	
	public function Cargar($map = array() ){
		$arr = array();
		$donde = '';
		$tipo = 1;
		
		if ( $map["nombre"] == 'FCP' ){
			$query = "SELECT llav, cedu, fami, conc, mont, tipo from space.nomina_archivo WHERE fami != '' ;"; // AND fami = '533320' --WHERE fech BETWEEN '2019-04-01' AND '2019-04-30'";
		}else{
			$query = "SELECT llav, cedu, fami, conc, mont, tipo from space.nomina_archivo;"; // --WHERE fech BETWEEN '2019-04-01' AND '2019-04-30'";			
		}
        $obj = $this->Dbpace->consultar($query);

		$rs = $obj->rs;

		foreach ($rs as $c => $v) {
			$resultado = 0;        
			if($v->tipo == 1){
				$resultado = $v->mont;
			}else  if($v->tipo >= 2){				
				$resultado = $v->mont * -1 ;
			}
			$cedula = $v->cedu;
			if($v->fami != '')$cedula = $v->fami;

            $arr[$v->conc][$cedula] = $resultado;
        }
		return $arr;
	}


	public function Ejecutar($cedula = '', $concepto = '', $arr){		
		$monto = isset($arr[$concepto][$cedula])?$arr[$concepto][$cedula]:0;
		
        return $monto;
	}

	/**
	 * Cargar Datos de Asignaciones y Deducciones 
	 * 
	 */
	public function CargarAD(){
		$query = "SELECT cedula, conc, fnxc, tipo  from space.descuentos ";
	}



}
