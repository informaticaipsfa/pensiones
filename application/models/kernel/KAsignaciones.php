<?php

/**
*
*/
class KAsginaciones extends CI_Model{


	function __construct(){
		parent::__construct();
		if(!isset($this->Dbpace)) $this->load->model('comun/Dbpace');
    }

	
	public function Cargar($tipoNomina = ""){
	
        $query = "SELECT cedula, conc, fnxc, tipo  from space.asig_deduc";
        $obj = $this->Dbpace->consultar($query);

		$rs = $obj->rs;

		foreach ($rs as $c => $v) {
            $medida = array(
				'cedula' => $v->cedula, //Cedula del titula de la medida
				'nomb' => $v->nomb,	//Descripcion de la medidaa
				'pare' => $v->pare, //Parentesco
				'cben' => $v->cben, //Cedula Beneficiario
				'bene' => $v->bene, //Nombre Beneficiario
                'caut' => $v->caut, //Cedula Autorizado
				'auto' => $v->auto, //Nombre del autorizado
				'tpag' => $v->tpag, 
				'inst' => $v->inst, // Institucion bancaria
				'tcue' => $v->tcue, // Tipo de cuenta
				'ncue' => $v->ncue, // Numero de Cuenta
				'fnxm' => $v->fnxm, // Formula monto
            );
            $arr[$v->cedula][] = $medida;
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
