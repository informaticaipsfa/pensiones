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
		ini_set('memory_limit', '3024M');
		$arr = array();
		$donde = '';
		$tipo = 1;
		
		$seleccionar = $this->SeleccionarConceptos($map['Concepto']);
		if ( $map["tipo"] == 'FCP' ){
			$query = "SELECT llav, cedu, fami, conc, mont, tipo from space.nomina_archivo 
				WHERE fami != '' AND  proc BETWEEN '" . $map["fechainicio"]  . "' 
				AND '" . $map["fechafin"]  . "' " . $seleccionar ;
		}else{
			$query = "SELECT llav, cedu, fami, conc, mont, tipo from space.nomina_archivo WHERE proc BETWEEN '" . $map["fechainicio"]  . "' AND '" . $map["fechafin"]  . "' "  . $seleccionar;			
		}
		//print_r( $query );

    $obj = $this->Dbpace->consultar($query);
		$rs = $obj->rs;

		foreach ($rs as $c => $v) {
			$resultado = 0;        
			if($v->tipo < 3){
				$resultado = $v->mont;
			}else  if($v->tipo > 2){				
				$resultado = $v->mont * -1 ;
			}
			$cedula = $v->cedu;
			if($v->fami != '')$cedula = $v->cedu . $v->fami;

            $arr[$v->conc][$cedula] = $resultado;
        }
		return $arr;
	}


	public function Ejecutar($cedula = '', $concepto = '', $arr, $familiar = ''){	
		
		//print_r( $cedula . ' -- ' . $arr[$concepto][$cedula]);
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

	public function SeleccionarConceptos( $Conceptos = array() ){
		$cadena = ' AND conc IN ( ';
		$coma = '';
		$cant = count($Conceptos);
		for ($i= 0; $i < $cant; $i++){
			if($i >= 1)$coma = ",";
			$cadena .= $coma . "'" . $Conceptos[$i]['codigo'] . "'";
		}
		return $cadena . ' ) ';
	}


}
