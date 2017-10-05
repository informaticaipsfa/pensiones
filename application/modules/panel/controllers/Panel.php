<?php
defined('BASEPATH') OR exit('No direct script access allowed');

date_default_timezone_set ( 'America/Caracas' );
define ('__CONTROLADOR', 'panel');
class Panel extends MY_Controller {

	var $_DIRECTIVA = array();

	/**
	* CONSTRUCTOR DEL PANEL
	*
	*/
	function __construct(){
		parent::__construct();
		$this->load->helper('url');
		$this->load->library('session');		
		//if(!isset($_SESSION['usuario']))$this->salir();
	}


	/**
	* 	----------------------------------
	*	Sección de la GUI
	* 	----------------------------------
	*/
	public function index(){
		$this->load->view("view_home");
	}

	public function Limpieza(){
		echo "Hola Mundo";
	}






	/**
	 * Generar Indices para procesos de lotes (Activos)
	 *
	 * Creación de tablas para los cruce en el esquema space como
	 * tablacruce permite ser indexada para evaluar la tabla movimiento
	 * tipos de movimiento [3,31,32] dando como resultado del crosstab
	 * cedula | Deposito AA | Deposito Dia Adicionales | Deposito Garantias
	 *
	 * -------------------------------------------------------------------
	 *	INICIANDO PROCESOS APORTE DE CAPITAL
	 * -------------------------------------------------------------------
	 *
	 * @return	void
	 */ 


	public function GenerarSueldos(){
		//ini_set('memory_limit', '1024M');
		header('Content-Type: application/json');
		$this->load->model('kernel/KSensor');
		$fecha = date('d/m/Y H:i:s');
		$firma = md5($fecha);
		//$data = json_decode($_POST['data']);
		//print_r($data);
		
		$this->load->model('kernel/KCargador');	
		$data['id'] = 53; //Directiva
		//$data['fe'] = "2016-01-31";
		//$data['estado_id'] = 203;
		//$data['sit'] = 203;
		//$data['com'] = 99;
		//$data['gra'] = 99;
		//$data['fde'] = "2016-01-01";
		//$data['fha'] = "2016-01-31";
		

 		$this->KCargador->IniciarLote($data, $firma, "SSSIFANB");	
 		//$this->KCargador->IniciarLote((object)$data, '2016-01-01', $firma, $_SESSION['usuario']);	
 		/**
 		$mnt = $this->KCargador->Resultado['l'] - 1;
		$json = array(
			'd' => $data,
			'm' => "Fecha y Hora del Servidor: " . $fecha . 
					"\nFirma del Archivo: " . 	$firma .  
					"\nCantidad de Registros: " . $mnt  .
					"\nMonto Total de las Garantias: " . $this->KCargador->Resultado['g'] .
					"\nMonto Total de Dias Adicionales: " . $this->KCargador->Resultado['d'] .
					"\nPeso del Archivo: " . $this->KCargador->Resultado['p'] . " " . $this->KCargador->Resultado['f'] . "\n" .
					$this->KSensor->Duracion() . "... ",
			'z' => $firma .".zip",
			'json' => $this->KCargador->Resultado
		);
		echo json_encode($json);
		**/
		
	}
	public function GenerarCalculoAporteCapitalEstudiar(){
		//ini_set('memory_limit', '1024M');
		echo "<pre>";
		$this->load->model('kernel/KSensor');
		$fecha = date('d/m/Y H:i:s');
		$firma = md5($fecha); //PID

		$this->load->model('kernel/KCargador');			
 		$this->KCargador->IniciarLoteEstudiar(48, '2017-03-01', $firma, $_SESSION['usuario'], 100);	
 		//$mnt = $this->KCargador->Resultado['l'] - 1;		
	}

	public function ConsultarGrupos(){
		header('Content-Type: application/json');
		$this->load->model('kernel/KSensor');
		$fecha = date('d/m/Y H:i:s');
		$firma = md5($fecha);

		$this->load->model('kernel/KCargador');		
		
		$json = json_decode($_POST['data']);
 		$lst = $this->KCargador->ConsultarGrupos($json);	

 		echo json_encode($lst);

	}



	function LoteGarantiaDiasAdicionales($archivo = ''){
		header('Content-Type: application/json');
		$data = json_decode($_POST['data']);
		if($archivo == '' || $data->tipo < 0 || $data->tipo > 3){
			echo 'Está intentando acceder a un área restringida.';
		}else{
			$this->load->model("kernel/KCargador");
			$respuesta = $this->KCargador->GarantiasDiasAdicionales($archivo, $data->tipo, $data->porc);	
			echo json_encode($this->KCargador->Resultado);
		}
		
	}


	


	

	public function salir(){
		redirect('panel/Login/salir');
	}


}
