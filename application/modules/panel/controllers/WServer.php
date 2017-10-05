<?php


// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
// use namespace
use Restserver\Libraries\REST_Controller;
/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */

class WServer extends REST_Controller{
	
	public function __construct(){
		parent::__construct();
		
	}

	/**
	public function index_get(){
		

		$this->load->model('comun/DBSpace');
		//$this->load->model('kernel/KCargador');
		//$Beneficiario = $this->KCargador->ConsultarBeneficiario($this->get('id'));
		$sCon = "SELECT cedula,nombres,apellidos FROM beneficiario  WHERE status_id=201 LIMIT 100";
		$rs = $this->DBSpace->consultar($sCon);
	
		$lst = array();
		$i = 1;
		foreach ($rs->rs as $c => $v) {
			$lst[] = (array)$v;
		}
		
		$this->response($lst);
		
	}
	**/

	public function index_get(){
		$r = "1";

		if ($r == "192.168.6.65") {
			$this->response(['acceso no permitido'],404);
			//return FALSE;
		}else{
			$this->load->model("fisico/MBeneficiario");
			$this->MBeneficiario->obtenerID($this->get('id'));
			$ano = explode('-', $this->MBeneficiario->fecha_ingreso);

			if($ano[0] < 2010) {
				$p = $this->CasoMenor2010($this->MBeneficiario->tiempo_servicio);
			}else{
				$p = $this->Casos($this->MBeneficiario->tiempo_servicio);
			}
			
			$rs = array('Persona' => [
				's_mensual' => $this->MBeneficiario->sueldo_global,
				's_pension_mensual' => ($this->MBeneficiario->sueldo_global * $p)/100,
				's_pension_integral' => ($this->MBeneficiario->sueldo_integral * $p)/100,
				'p_pension' => $p . '%',
				'f_ingreso' => $this->MBeneficiario->fecha_ingreso,
				't_servicio' => $this->MBeneficiario->tiempo_servicio,
				't_servicio_aux' => $this->MBeneficiario->tiempo_servicio_aux,
				'a_depositada' => $this->MBeneficiario->Calculo['asignacion_depositada_aux'],
				'a_generada' => $this->MBeneficiario->Calculo['asignacion_diferencia_aux'],
				'REST' => $_SERVER['REMOTE_ADDR']
			]);
			
			$this->response($rs);	
		}
		
	}

	public function AccessForbiden(){
		$this->response(['acceso no permitido'],404);
	}

	public function Casos($t){
		$v = 0;
		switch ($t) {
			case 15:
				$v = 50;
				break;
			case 16:
				$v = 52;
				break;
			case 17:
				$v = 54;
				break;
			case 18:
				$v = 56;
				break;
			case 19:
				$v = 59;
				break;
			case 20:
				$v = 62;
				break;
			case 21:
				$v = 65;
				break;
			case 22:
				$v = 68;
				break;
			case 23:
				$v = 72;
				break;
			case 24:
				$v = 76;
				break;
			case 25:
				$v = 80;
				break;
			case 26:
				$v = 84;
				break;
			case 27:
				$v = 89;
				break;
			case 28:
				$v = 94;
				break;
			case 29:
				$v = 99;
				break;
			case 30:
				$v = 100;
				break;
			default:
				if ($t>30) $v = 100;
				# code...
				break;
		}

		return $v;

			
	}

	public function CasoMenor2010($t){
		$v = 0;
		switch ($t) {
			case 15:
				$v = 60;
				break;
			case 16:
				$v = 63;
				break;
			case 17:
				$v = 66;
				break;
			case 18:
				$v = 69;
				break;
			case 19:
				$v = 72;
				break;
			case 20:
				$v = 75;
				break;
			case 21:
				$v = 80;
				break;
			case 22:
				$v = 84;
				break;
			case 23:
				$v = 88;
				break;
			case 24:
				$v = 92;
				break;
			case 25:
				$v = 99;
				break;
			
			default:
				if ($t>25) $v = 100;
				# code...
				break;
		}

		return $v;
	}


	public function book_get(){
		$this->response([
	'returned from delete:' => $this->get('id'),
	]);
	}
		
}