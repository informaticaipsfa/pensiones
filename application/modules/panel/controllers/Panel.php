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
		$this->load->model('logico/MCurl');

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

	public function beneficiario(){
		$this->load->model('beneficiario/MHistorialMovimiento');
		$data['Movimientos'] = $this->MHistorialMovimiento->listarTodo();

		$this->load->view("menu/beneficiario/beneficiario", $data);
	}
	public function pensiones(){
		$this->load->view("menu/beneficiario/finiquito");
	}
	public function medidajudicial(){
		$this->load->model('beneficiario/MEstado');
		$this->load->model('beneficiario/MParentesco');
		$this->load->model('beneficiario/MFormaPago');

		$data['Estado'] = $this->MEstado->listar();
		$data['Parentesco'] = $this->MParentesco->listar();
		$data['FormaPago'] = $this->MFormaPago->listar();
		$this->load->view("menu/beneficiario/medidajudicial", $data);
	}

	function ObtenerEstados(){
		$arr['url'] = 'http://192.168.6.45:8080/devel/api/estado';
		$api = $this->MCurl->Cargar_API($arr);
		$estado = $api['obj'];
		print_r($estado);
	}

	public function actualizar(){
		$this->load->view("menu/beneficiario/actualizarbeneficiario");
	}

	/**
	*	---------------------------------------------
	*	FIN DE LOS REPORTES GENERALES DEL SISTEMA
	*	---------------------------------------------
	*/

	public function consultarBeneficiario($cedula = '', $fecha = ''){
		//header('Content-Type: application/json');
		$this->load->model('beneficiario/MBeneficiario');
		$this->load->model('beneficiario/MHistorialMovimiento');

		$this->MBeneficiario->obtenerID($cedula, $fecha);


		//print_r( $Militar->Pension->DatoFinanciero);
		// print_r($this->MBeneficiario);
		// $this->load->model('beneficiario/MOrdenPago'); //MedidaJudicial
		// $this->MBeneficiario->HistorialOrdenPagos = $this->MOrdenPago->listarPorCedula($cedula);
		//$this->MBeneficiario->HistorialDetalleMovimiento = $this->MHistorialMovimiento->listarDetalle($cedula);

		echo json_encode($this->MBeneficiario);
	}

	public function consultarBeneficiarioJudicial($cedula = '', $fecha = ''){
		$this->load->model('beneficiario/MBeneficiario');
		$this->load->model('beneficiario/MMedidaJudicial');

		$this->MBeneficiario->obtenerID($cedula, $fecha);
		$this->MBeneficiario->MedidaJudicial = $this->MMedidaJudicial->listarTodo($cedula);
		echo json_encode($this->MBeneficiario);
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

		$this->load->model('kernel/KCargador');
		$data['id'] = 53; //Directiva


 		$this->KCargador->IniciarLote($data, $firma, "SSSIFANB");

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
